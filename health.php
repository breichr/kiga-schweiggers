<?php
// Für den Healthcheck von Coolify/Docker: prüft, ob Inhalte lesbar und speicherbar sind.
header('Content-Type: text/plain');
header('Cache-Control: no-store');
$d = __DIR__ . '/data';
$ok = is_readable($d . '/content.json') && is_writable($d) && is_writable(__DIR__ . '/uploads');
http_response_code($ok ? 200 : 503);
echo $ok ? 'ok' : 'fehler';
