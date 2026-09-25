# Kindergarten Schweiggers – Image für Coolify (oder jeden Docker-Host)
FROM php:8.3-apache

# PHP-Erweiterungen für die Fotobearbeitung (GD mit JPEG/WebP, EXIF zum Drehen von Handyfotos)
RUN apt-get update \
 && apt-get install -y --no-install-recommends libjpeg62-turbo-dev libpng-dev libwebp-dev \
 && docker-php-ext-configure gd --with-jpeg --with-webp \
 && docker-php-ext-install -j"$(nproc)" gd exif \
 && rm -rf /var/lib/apt/lists/*

# Apache: .htaccess erlauben, Header/Expires, echte Besucher-IP hinter dem Coolify-Proxy
RUN a2enmod headers expires remoteip \
 && sed -ri 's/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf \
 && printf 'ServerName localhost\nServerTokens Prod\nServerSignature Off\nRemoteIPHeader X-Forwarded-For\nRemoteIPInternalProxy 10.0.0.0/8 172.16.0.0/12 192.168.0.0/16\n' \
      > /etc/apache2/conf-enabled/kiga.conf \
 && sed -ri 's/%h /%a /g' /etc/apache2/apache2.conf

# PHP-Einstellungen
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
 && printf 'upload_max_filesize=16M\npost_max_size=20M\nmemory_limit=256M\nexpose_php=Off\nsession.cookie_httponly=1\nsession.use_strict_mode=1\ndate.timezone=Europe/Vienna\n' \
      > "$PHP_INI_DIR/conf.d/kiga.ini"

ENV TZ=Europe/Vienna

COPY --chown=www-data:www-data . /var/www/html/

# Startinhalte getrennt ablegen: Beim ersten Start werden sie in das (leere) Volume kopiert
RUN mkdir -p /usr/src/kiga-seed \
 && cp /var/www/html/data/content.json /usr/src/kiga-seed/content.json \
 && install -m 0755 /var/www/html/docker/entrypoint.sh /usr/local/bin/kiga-entrypoint \
 && rm -rf /var/www/html/docker /var/www/html/Dockerfile /var/www/html/docker-compose.yaml /var/www/html/.dockerignore /var/www/html/.gitignore

VOLUME ["/var/www/html/data", "/var/www/html/uploads"]
EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
  CMD php -r 'exit(@file_get_contents("http://127.0.0.1/health.php") === "ok" ? 0 : 1);'

ENTRYPOINT ["kiga-entrypoint"]
CMD ["apache2-foreground"]
