#!/bin/sh
# Bereitet die Volumes vor und startet dann Apache.
set -e
DATA="${KIGA_DATA_DIR:-/var/www/data}"
UP=/var/www/html/uploads

mkdir -p "$DATA/backups" "$DATA/sessions" "$UP"

# Erster Start mit leerem Volume: Startinhalte hineinkopieren
if [ ! -f "$DATA/content.json" ]; then
  echo "[kiga] Keine Inhalte gefunden – Startinhalte werden angelegt."
  cp /usr/src/kiga-seed/content.json "$DATA/content.json"
fi

# Passwort zurücksetzen über die Umgebungsvariable KIGA_RESET_PASSWORD=1 in Coolify
if [ "${KIGA_RESET_PASSWORD:-0}" = "1" ] && [ -f "$DATA/config.php" ]; then
  echo "[kiga] KIGA_RESET_PASSWORD=1 – Passwort wurde gelöscht. /admin aufrufen und neu festlegen, danach die Variable wieder entfernen."
  rm -f "$DATA/config.php" "$DATA/.login-versuche"
fi

chown -R www-data:www-data "$DATA" "$UP"
chmod 0750 "$DATA"

exec docker-php-entrypoint "$@"
