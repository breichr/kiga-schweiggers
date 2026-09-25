# Kindergarten Schweiggers – Image für Coolify (oder jeden Docker-Host)
FROM php:8.3-apache

# PHP-Erweiterungen für die Fotobearbeitung (GD mit JPEG/WebP, EXIF zum Drehen von Handyfotos)
RUN apt-get update \
 && apt-get install -y --no-install-recommends libjpeg62-turbo-dev libpng-dev libwebp-dev \
 && docker-php-ext-configure gd --with-jpeg --with-webp \
 && docker-php-ext-install -j"$(nproc)" gd exif \
 && rm -rf /var/lib/apt/lists/*

ENV TZ=Europe/Vienna \
    KIGA_DATA_DIR=/var/www/data

# Apache und PHP konfigurieren
COPY docker/apache.conf /etc/apache2/conf-enabled/kiga.conf
COPY docker/php.ini "$PHP_INI_DIR/conf.d/kiga.ini"
COPY --chmod=0755 docker/entrypoint.sh /usr/local/bin/kiga-entrypoint
RUN a2enmod headers expires remoteip \
 && sed -ri 's/%h /%a /g' /etc/apache2/apache2.conf \
 && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Website-Code. Die Startinhalte liegen getrennt und werden beim ersten Start
# in das (leere) Daten-Volume kopiert – außerhalb des Web-Verzeichnisses.
# Der Code gehört root, nur Daten und Fotos sind für den Webserver beschreibbar.
COPY . /var/www/html/
RUN mkdir -p /usr/src/kiga-seed /var/www/data \
 && mv /var/www/html/data/content.json /usr/src/kiga-seed/content.json \
 && rm -rf /var/www/html/data /var/www/html/docker \
 && chown www-data:www-data /var/www/data \
 && chmod 0755 /var/www/html

VOLUME ["/var/www/data", "/var/www/html/uploads"]
EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
  CMD curl -fsS http://127.0.0.1/health.php || exit 1

ENTRYPOINT ["kiga-entrypoint"]
CMD ["apache2-foreground"]
