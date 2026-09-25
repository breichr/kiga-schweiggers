<?php
// Für den Healthcheck von Coolify/Docker: prüft, ob Inhalte lesbar und speicherbar sind.
require __DIR__ . '/inc/functions.php';
header('Content-Type: text/plain');
header('Cache-Control: no-store');
$ok = is_readable(DATA_FILE) && is_writable(DATA_DIR) && is_writable(UPLOAD_DIR);
http_response_code($ok ? 200 : 503);
echo $ok ? 'ok' : 'fehler';
