<?php
declare(strict_types=1);
require __DIR__ . '/auth.php';

header('Content-Type: application/json; charset=utf-8');

function fail(string $msg, int $code = 400): never
{
    http_response_code($code);
    echo json_encode(['fehler' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!logged_in()) {
    fail('Sie sind nicht mehr angemeldet. Bitte laden Sie die Seite neu und melden Sie sich an.', 401);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !check_csrf()) {
    fail('Die Anfrage war ungültig. Bitte laden Sie die Seite neu.');
}
$file = $_FILES['bild'] ?? null;
if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
    $err = $file['error'] ?? -1;
    fail($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE
        ? 'Das Foto ist zu groß. Bitte ein kleineres Foto wählen (höchstens ca. 15 MB).'
        : 'Das Foto konnte nicht hochgeladen werden. Bitte noch einmal versuchen.');
}

$info = @getimagesize($file['tmp_name']);
$types = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp', IMAGETYPE_GIF => 'gif'];
if (!$info || !isset($types[$info[2]])) {
    fail('Diese Datei ist kein Foto. Erlaubt sind JPG, PNG, WebP und GIF.');
}

if (!is_dir(UPLOAD_DIR)) {
    @mkdir(UPLOAD_DIR, 0775, true);
}
$base = date('Y-m') . '-' . bin2hex(random_bytes(6));
$maxW = 1800;

// Wenn möglich: Foto verkleinern, drehen (Handyfotos) und als JPG/WebP speichern.
// Dabei gehen auch GPS-Daten und andere Metadaten verloren – gewollt.
if (function_exists('imagecreatefromstring') && $info[2] !== IMAGETYPE_GIF) {
    $img = @imagecreatefromstring((string) file_get_contents($file['tmp_name']));
    if ($img) {
        if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
            $exif = @exif_read_data($file['tmp_name']);
            $rot = [3 => 180, 6 => -90, 8 => 90][$exif['Orientation'] ?? 1] ?? 0;
            if ($rot) {
                $img = imagerotate($img, $rot, 0);
            }
        }
        $w = imagesx($img);
        $h = imagesy($img);
        if ($w > $maxW) {
            $nh  = (int) round($h * $maxW / $w);
            $dst = imagecreatetruecolor($maxW, $nh);
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            imagecopyresampled($dst, $img, 0, 0, 0, 0, $maxW, $nh, $w, $h);
            imagedestroy($img);
            $img = $dst;
        }
        if ($info[2] === IMAGETYPE_PNG) {
            $name = $base . '.png';
            imagesavealpha($img, true);
            $ok = imagepng($img, UPLOAD_DIR . '/' . $name, 7);
        } else {
            $name = $base . '.jpg';
            $ok = imagejpeg($img, UPLOAD_DIR . '/' . $name, 82);
        }
        imagedestroy($img);
        if ($ok) {
            echo json_encode(['pfad' => 'uploads/' . $name]);
            exit;
        }
    }
}

// Ohne Bildbearbeitung: Datei unverändert übernehmen
$name = $base . '.' . $types[$info[2]];
if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . '/' . $name)) {
    fail('Das Foto konnte nicht gespeichert werden. Bitte die Schreibrechte des Ordners „uploads“ prüfen.', 500);
}
echo json_encode(['pfad' => 'uploads/' . $name]);
