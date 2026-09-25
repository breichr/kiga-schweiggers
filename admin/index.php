<?php
declare(strict_types=1);
require __DIR__ . '/auth.php';

$schema  = schema();
$msg     = '';
$msgType = 'ok';
$cfg     = config();
$action  = $_POST['aktion'] ?? '';

/* ---------- Ersteinrichtung ---------- */
if (!$cfg) {
    if ($action === 'einrichten') {
        $p1 = (string) ($_POST['pw1'] ?? '');
        $p2 = (string) ($_POST['pw2'] ?? '');
        if (u_len($p1) < 10) {
            $msg = 'Das Passwort muss mindestens 10 Zeichen lang sein.'; $msgType = 'err';
        } elseif ($p1 !== $p2) {
            $msg = 'Die beiden Passwörter stimmen nicht überein.'; $msgType = 'err';
        } elseif (!write_config($p1)) {
            $msg = 'Das Passwort konnte nicht gespeichert werden. Bitte die Schreibrechte des Ordners „data“ prüfen.'; $msgType = 'err';
        } else {
            session_regenerate_id(true);
            $_SESSION['ok'] = true; $_SESSION['last'] = time();
            header('Location: ./?willkommen=1'); exit;
        }
    }
    render_page('Einrichtung', function () use ($msg, $msgType) { ?>
      <div class="login-box">
        <h1>Willkommen!</h1>
        <p>Legen Sie ein Passwort für die Verwaltung der Website fest. Alle, die Inhalte bearbeiten, verwenden dieses Passwort.</p>
        <?php flash($msg, $msgType); ?>
        <form method="post">
          <input type="hidden" name="aktion" value="einrichten">
          <label>Neues Passwort (mindestens 10 Zeichen)<input type="password" name="pw1" required minlength="10" autocomplete="new-password"></label>
          <label>Passwort wiederholen<input type="password" name="pw2" required minlength="10" autocomplete="new-password"></label>
          <button class="btn btn-primary">Passwort speichern</button>
        </form>
      </div>
    <?php }, false);
    exit;
}

/* ---------- Anmeldung ---------- */
if (isset($_GET['abmelden'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: ./'); exit;
}

if (!logged_in()) {
    if ($action === 'anmelden') {
        if (login_blocked()) {
            $msg = 'Zu viele Fehlversuche. Bitte in 10 Minuten noch einmal probieren.'; $msgType = 'err';
        } elseif (password_verify((string) ($_POST['pw'] ?? ''), $cfg['hash'])) {
            login_reset();
            session_regenerate_id(true);
            $_SESSION['ok'] = true; $_SESSION['last'] = time();
            header('Location: ./'); exit;
        } else {
            login_failed();
            usleep(800000);
            $msg = 'Das Passwort ist nicht richtig.'; $msgType = 'err';
        }
    }
    render_page('Anmelden', function () use ($msg, $msgType) { ?>
      <div class="login-box">
        <h1>Website bearbeiten</h1>
        <?php flash($msg, $msgType); ?>
        <form method="post">
          <input type="hidden" name="aktion" value="anmelden">
          <label>Passwort<input type="password" name="pw" required autofocus autocomplete="current-password"></label>
          <button class="btn btn-primary">Anmelden</button>
        </form>
        <p class="small"><a href="../">Zurück zur Website</a></p>
      </div>
    <?php }, false);
    exit;
}
$_SESSION['last'] = time();

/* ---------- Speichern ---------- */
$bereich = $_GET['bereich'] ?? '';
$content = load_content();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!check_csrf()) {
        $msg = 'Die Seite war zu lange offen. Bitte noch einmal speichern.'; $msgType = 'err';
    } elseif ($action === 'speichern' && isset($schema[$bereich])) {
        $content[$bereich] = parse_section($schema[$bereich], $_POST);
        if (save_content($content)) {
            header('Location: ./?bereich=' . urlencode($bereich) . '&gespeichert=1'); exit;
        }
        $msg = 'Speichern hat nicht geklappt. Bitte die Schreibrechte des Ordners „data“ prüfen.'; $msgType = 'err';
    } elseif ($action === 'wiederherstellen') {
        $file = basename((string) ($_POST['datei'] ?? ''));
        $path = BACKUP_DIR . '/' . $file;
        if (preg_match('/^inhalt-[\d_-]+\.json$/', $file) && is_file($path)) {
            $data = json_decode((string) file_get_contents($path), true);
            if (is_array($data) && save_content($data)) {
                header('Location: ./?bereich=sicherung&wiederhergestellt=1'); exit;
            }
        }
        $msg = 'Diese Sicherung konnte nicht wiederhergestellt werden.'; $msgType = 'err';
    } elseif ($action === 'passwort') {
        $p1 = (string) ($_POST['pw1'] ?? '');
        if (!password_verify((string) ($_POST['alt'] ?? ''), $cfg['hash'])) {
            $msg = 'Das bisherige Passwort ist nicht richtig.'; $msgType = 'err';
        } elseif (u_len($p1) < 10 || $p1 !== ($_POST['pw2'] ?? '')) {
            $msg = 'Das neue Passwort muss mindestens 10 Zeichen haben und zweimal gleich eingegeben werden.'; $msgType = 'err';
        } elseif (write_config($p1)) {
            $msg = 'Das Passwort wurde geändert.';
        }
    }
}
if (isset($_GET['gespeichert'])) {
    $msg = 'Gespeichert. Die Änderungen sind jetzt auf der Website sichtbar.';
}
if (isset($_GET['wiederhergestellt'])) {
    $msg = 'Die ältere Version wurde wiederhergestellt.';
}

/* ---------- Seiten ---------- */
if (isset($schema[$bereich])) {
    $sec = $schema[$bereich];
    render_page($sec['title'], function () use ($sec, $bereich, $content, $msg, $msgType) {
        $data = $content[$bereich] ?? [];
        ?>
        <p class="back"><a href="./">Alle Bereiche</a></p>
        <h1><?= e($sec['title']) ?></h1>
        <p class="help"><?= e($sec['help']) ?></p>
        <?php flash($msg, $msgType); ?>
        <form method="post" class="editor" id="editor">
          <input type="hidden" name="aktion" value="speichern">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
          <?php foreach ($sec['fields'] ?? [] as $fd) { render_field($fd, 'f[' . $fd['key'] . ']', $data[$fd['key']] ?? ''); } ?>

          <?php if (!empty($sec['list'])): $l = $sec['list']; $items = $data[$l['key']] ?? []; ?>
            <div class="list" data-prepend="<?= !empty($l['prepend']) ? '1' : '0' ?>">
              <div class="list-head">
                <h2>Einträge <span class="count">(<?= count($items) ?>)</span></h2>
                <button type="button" class="btn btn-add" data-add><?= e($l['add']) ?></button>
              </div>
              <div class="items">
                <?php foreach ($items as $i => $item) { render_item($l, (string) $i, $item, false); } ?>
              </div>
              <?php if (!$items): ?><p class="empty">Noch nichts eingetragen. Klicken Sie auf „<?= e($l['add']) ?>“.</p><?php endif; ?>
              <template><?php render_item($l, '__IDX__', [], true); ?></template>
            </div>
          <?php endif; ?>

          <div class="savebar">
            <span class="dirty-hint" hidden>Sie haben ungespeicherte Änderungen.</span>
            <button class="btn btn-primary">Änderungen speichern</button>
          </div>
        </form>
        <?php
    });
    exit;
}

if ($bereich === 'sicherung') {
    render_page('Sicherungen & Passwort', function () use ($msg, $msgType) {
        $files = glob(BACKUP_DIR . '/inhalt-*.json') ?: [];
        rsort($files);
        ?>
        <p class="back"><a href="./">Alle Bereiche</a></p>
        <h1>Sicherungen &amp; Passwort</h1>
        <?php flash($msg, $msgType); ?>
        <section class="panel">
          <h2>Ältere Version zurückholen</h2>
          <p class="help">Bei jedem Speichern wird automatisch eine Sicherung angelegt (die letzten <?= MAX_BACKUPS ?>). Wenn etwas versehentlich gelöscht wurde, können Sie hier den Stand von vorher zurückholen.</p>
          <?php if (!$files): ?><p class="empty">Noch keine Sicherungen vorhanden.</p><?php endif; ?>
          <ul class="backups">
            <?php foreach ($files as $fl): $b = basename($fl);
              preg_match('/inhalt-(\d{4})-(\d\d)-(\d\d)_(\d\d)-(\d\d)/', $b, $m); ?>
              <li>
                <span>Stand vom <?= e("$m[3].$m[2].$m[1], $m[4]:$m[5] Uhr") ?></span>
                <form method="post" onsubmit="return confirm('Wirklich diesen älteren Stand wiederherstellen? Der aktuelle Stand wird vorher ebenfalls gesichert.')">
                  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                  <input type="hidden" name="aktion" value="wiederherstellen">
                  <input type="hidden" name="datei" value="<?= e($b) ?>">
                  <button class="btn btn-small">Wiederherstellen</button>
                </form>
              </li>
            <?php endforeach; ?>
          </ul>
        </section>
        <section class="panel">
          <h2>Passwort ändern</h2>
          <form method="post" class="narrow">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="aktion" value="passwort">
            <label>Bisheriges Passwort<input type="password" name="alt" required autocomplete="current-password"></label>
            <label>Neues Passwort (mindestens 10 Zeichen)<input type="password" name="pw1" required minlength="10" autocomplete="new-password"></label>
            <label>Neues Passwort wiederholen<input type="password" name="pw2" required minlength="10" autocomplete="new-password"></label>
            <button class="btn btn-primary">Passwort ändern</button>
          </form>
        </section>
        <?php
    });
    exit;
}

// Übersicht
render_page('Übersicht', function () use ($schema, $content) {
    $hints = [
        'aktuelles' => 'Neuigkeiten schreiben', 'termine' => 'Termine eintragen', 'galerie' => 'Fotos hochladen',
        'allgemein' => 'Begrüßung, Hinweis, Kontakt', 'zeiten' => 'Zeiten und Kosten', 'gruppen' => 'Wer arbeitet in welcher Gruppe',
        'konzept' => 'Wie wir arbeiten, Zitate', 'feste' => 'Jahreskreis und Projekte', 'geschichte' => 'Chronik seit 1896', 'rechtliches' => 'Pflichtangaben der Gemeinde',
    ];
    ?>
    <h1>Was möchten Sie bearbeiten?</h1>
    <?php if (isset($_GET['willkommen'])): ?>
      <div class="flash ok">Das Passwort ist gespeichert. Wählen Sie unten einen Bereich aus. Die Anleitung (ANLEITUNG.md) erklärt alles Schritt für Schritt.</div>
    <?php endif; ?>
    <h2 class="group-title">Häufig</h2>
    <div class="tiles">
      <?php foreach (['aktuelles', 'termine', 'galerie', 'allgemein'] as $key): ?>
        <a class="tile tile-big" href="?bereich=<?= $key ?>"><strong><?= e($schema[$key]['title']) ?></strong><span><?= e($hints[$key] ?? '') ?></span></a>
      <?php endforeach; ?>
    </div>
    <h2 class="group-title">Weitere Bereiche</h2>
    <div class="tiles">
      <?php foreach ($schema as $key => $sec): if (in_array($key, ['aktuelles', 'termine', 'galerie', 'allgemein'], true)) continue; ?>
        <a class="tile" href="?bereich=<?= $key ?>"><strong><?= e($sec['title']) ?></strong><span><?= e($hints[$key] ?? '') ?></span></a>
      <?php endforeach; ?>
      <a class="tile" href="?bereich=sicherung"><strong>Sicherungen &amp; Passwort</strong><span>Ältere Version zurückholen</span></a>
    </div>
    <?php
});

/* ========== Hilfsfunktionen für die Darstellung ========== */

function flash(string $msg, string $type): void
{
    if ($msg !== '') {
        echo '<div class="flash ' . e($type) . '" role="status">' . e($msg) . '</div>';
    }
}

function render_page(string $title, callable $body, bool $chrome = true): void
{
    $v = @filemtime(__DIR__ . '/admin.css') ?: 1;
    ?><!doctype html>
<html lang="de-AT"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title><?= e($title) ?> – Verwaltung</title>
<link rel="stylesheet" href="admin.css?v=<?= $v ?>">
</head><body class="<?= $chrome ? '' : 'bare' ?>">
<?php if ($chrome): ?>
<header class="bar"><div class="bar-inner">
  <a href="./" class="bar-title">Website-Verwaltung</a>
  <nav><a href="../" target="_blank" rel="noopener">Website ansehen</a><a href="?abmelden=1">Abmelden</a></nav>
</div></header>
<?php endif; ?>
<main class="main"><?php $body(); ?></main>
<?php if ($chrome): ?><script>window.CSRF = <?= json_encode(csrf_token()) ?>;</script><script src="admin.js?v=<?= $v ?>"></script><?php endif; ?>
</body></html>
<?php
}

function render_field(array $fd, string $name, $value, bool $isNew = false): void
{
    $id  = 'f' . substr(md5($name), 0, 10);
    $val = (string) $value;
    if ($isNew && ($fd['default'] ?? '') === 'today') {
        $val = date('Y-m-d');
    }
    echo '<div class="field field-' . e($fd['type']) . '">';
    echo '<label for="' . $id . '">' . e($fd['label']) . '</label>';
    if (!empty($fd['help'])) {
        echo '<p class="field-help">' . e($fd['help']) . '</p>';
    }
    switch ($fd['type']) {
        case 'textarea':
            echo '<textarea id="' . $id . '" name="' . e($name) . '" rows="' . (int) ($fd['rows'] ?? 4) . '">' . e($val) . '</textarea>';
            break;
        case 'date':
            echo '<input id="' . $id . '" type="date" name="' . e($name) . '" value="' . e($val) . '">';
            break;
        case 'select':
            echo '<select id="' . $id . '" name="' . e($name) . '">';
            foreach ($fd['options'] as $k => $label) {
                echo '<option value="' . e((string) $k) . '"' . ($k === $val ? ' selected' : '') . '>' . e($label) . '</option>';
            }
            echo '</select>';
            break;
        case 'image':
            echo '<div class="img-field' . ($val ? ' has-img' : '') . '">';
            echo '<input type="hidden" name="' . e($name) . '" value="' . e($val) . '">';
            echo '<div class="img-preview">' . ($val ? '<img src="../' . e($val) . '" alt="">' : '<span>Kein Bild</span>') . '</div>';
            echo '<div class="img-actions">';
            echo '<label class="btn btn-small btn-file">Foto auswählen<input id="' . $id . '" type="file" accept="image/jpeg,image/png,image/webp,image/gif" data-upload></label>';
            echo '<button type="button" class="btn btn-small btn-ghost" data-remove-img>Bild entfernen</button>';
            echo '<span class="img-status" aria-live="polite"></span>';
            echo '</div></div>';
            break;
        default:
            echo '<input id="' . $id . '" type="text" name="' . e($name) . '" value="' . e($val) . '">';
    }
    echo '</div>';
}

function render_item(array $l, string $idx, array $item, bool $isNew): void
{
    $title = trim((string) ($item[$l['title_field']] ?? ''));
    $title = preg_replace('/\s+/', ' ', $title); if (u_len($title) > 70) { $title = u_cut($title, 69) . '…'; }
    ?>
    <details class="item" <?= $isNew ? 'open' : '' ?>>
      <summary>
        <span class="item-title" data-title-from="<?= e($l['title_field']) ?>"><?= $title !== '' ? e($title) : 'Neuer ' . e($l['label']) ?></span>
        <?php if (!empty($item['datum'])): ?><span class="item-date"><?= e(date_de($item['datum'])) ?></span><?php endif; ?>
      </summary>
      <div class="item-body">
        <?php foreach ($l['fields'] as $fd) { render_field($fd, 'l[' . $idx . '][' . $fd['key'] . ']', $item[$fd['key']] ?? '', $isNew); } ?>
        <div class="item-tools">
          <button type="button" class="btn btn-small btn-ghost" data-move="-1">Nach oben</button>
          <button type="button" class="btn btn-small btn-ghost" data-move="1">Nach unten</button>
          <button type="button" class="btn btn-small btn-danger" data-delete><?= e($l['label']) ?> löschen</button>
        </div>
      </div>
    </details>
    <?php
}

/* ========== Eingaben prüfen und übernehmen ========== */

function clean_value(array $fd, $v): string
{
    $v = is_string($v) ? str_replace("\r", '', $v) : '';
    switch ($fd['type']) {
        case 'date':
            return preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : '';
        case 'select':
            return array_key_exists($v, $fd['options']) ? $v : (string) array_key_first($fd['options']);
        case 'image':
            return preg_match('~^uploads/[A-Za-z0-9._-]+\.(jpe?g|png|webp|gif)$~', $v) ? $v : '';
        case 'textarea':
            return trim(u_cut($v, 20000));
        default:
            return trim(preg_replace('/\s+/', ' ', u_cut($v, 500)));
    }
}

function parse_section(array $sec, array $post): array
{
    $out = [];
    foreach ($sec['fields'] ?? [] as $fd) {
        $out[$fd['key']] = clean_value($fd, $post['f'][$fd['key']] ?? '');
    }
    if (!empty($sec['list'])) {
        $l = $sec['list'];
        $items = [];
        foreach ((array) ($post['l'] ?? []) as $raw) {
            if (!is_array($raw)) {
                continue;
            }
            $item = [];
            $hasContent = false;
            foreach ($l['fields'] as $fd) {
                $item[$fd['key']] = clean_value($fd, $raw[$fd['key']] ?? '');
                if ($item[$fd['key']] !== '' && !in_array($fd['type'], ['select', 'date'], true)) {
                    $hasContent = true;
                }
            }
            if ($hasContent) {
                $items[] = $item;
            }
        }
        $out[$l['key']] = $items;
    }
    return $out;
}
