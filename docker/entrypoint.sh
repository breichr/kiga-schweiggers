#!/bin/sh
# Bereitet die Volumes vor und startet dann Apache.
set -e
DATA=/var/www/html/data
UP=/var/www/html/uploads

mkdir -p "$DATA/backups" "$UP"

# Erster Start mit leerem Volume: Startinhalte hineinkopieren
if [ ! -f "$DATA/content.json" ]; then
  echo "[kiga] Keine Inhalte gefunden – Startinhalte werden angelegt."
  cp /usr/src/kiga-seed/content.json "$DATA/content.json"
fi

# Zugriffsschutz-Dateien sicherstellen (auch wenn das Volume leer war)
printf 'Require all denied\n' > "$DATA/.htaccess"
cat > "$UP/.htaccess" <<'HT'
<FilesMatch "\.(?i:php\d?|phtml|phar|pl|py|cgi|sh)$">
  Require all denied
</FilesMatch>
php_flag engine off
Options -Indexes -ExecCGI
HT

chown -R www-data:www-data "$DATA" "$UP"

exec docker-php-entrypoint "$@"
