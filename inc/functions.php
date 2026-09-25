<?php
/**
 * Kindergarten Schweiggers – kleine, datenbankfreie Website-Verwaltung.
 * Alle Inhalte liegen in data/content.json. Bilder liegen in uploads/.
 */

declare(strict_types=1);

const ROOT = __DIR__ . '/..';

/*
 * Inhalte, Passwort und Sicherungen. Auf normalem Webspace liegt der Ordner im Projekt.
 * Im Docker-Image (Coolify) zeigt KIGA_DATA_DIR auf ein Volume außerhalb
 * des Web-Verzeichnisses, damit diese Dateien nie abrufbar sind.
 */
define('DATA_DIR', rtrim(getenv('KIGA_DATA_DIR') ?: ROOT . '/data', '/'));
const DATA_FILE   = DATA_DIR . '/content.json';
const CONFIG_FILE = DATA_DIR . '/config.php';
const BACKUP_DIR  = DATA_DIR . '/backups';
const LOGIN_FILE  = DATA_DIR . '/.login-versuche';
const UPLOAD_DIR  = ROOT . '/uploads'; // wird als /uploads/… ausgeliefert
const MAX_BACKUPS = 30;

/* ---------- Inhalte ---------- */

function load_content(): array
{
    if (!is_file(DATA_FILE)) {
        return [];
    }
    $data = json_decode((string) file_get_contents(DATA_FILE), true);
    return is_array($data) ? $data : [];
}

function save_content(array $data): bool
{
    if (!is_dir(BACKUP_DIR)) {
        @mkdir(BACKUP_DIR, 0775, true);
    }
    if (is_file(DATA_FILE)) {
        @copy(DATA_FILE, BACKUP_DIR . '/inhalt-' . date('Y-m-d_H-i-s') . '.json');
        $old = glob(BACKUP_DIR . '/inhalt-*.json') ?: [];
        sort($old);
        while (count($old) > MAX_BACKUPS) {
            @unlink(array_shift($old));
        }
    }
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $tmp  = DATA_FILE . '.tmp';
    if ($json === false || file_put_contents($tmp, $json, LOCK_EX) === false) {
        return false;
    }
    return rename($tmp, DATA_FILE);
}

/* ---------- Ausgabe-Helfer ---------- */

function e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** E-Mail-Adresse escapen, mit Umbruchstelle nach dem „@“ (fürs Handy). */
function e_email(?string $s): string
{
    return str_replace('@', '@<wbr>', e($s));
}

/** Gruppenname escapen, mit Trennhilfe vor „…gruppe“/„…betreuung“ (Nachmittags-betreuung). */
function e_name(?string $s): string
{
    return preg_replace('/(?<=\w)(gruppe|betreuung)\b/u', '&shy;$1', e($s));
}

/**
 * Einfacher Text → HTML.
 * Leerzeile = neuer Absatz, Zeilenumbruch bleibt erhalten,
 * **fett**, Zeilen mit "- " werden zur Aufzählung, Links und E-Mails werden klickbar.
 */
function format_text(?string $text): string
{
    $text = trim(str_replace("\r", '', (string) $text));
    if ($text === '') {
        return '';
    }
    $out = '';
    foreach (preg_split('/\n{2,}/', $text) as $block) {
        $lines = explode("\n", $block);
        $isList = count(array_filter($lines, fn($l) => preg_match('/^\s*[-•]\s+/', $l))) === count($lines);
        if ($isList) {
            $out .= '<ul>';
            foreach ($lines as $l) {
                $out .= '<li>' . inline_format(preg_replace('/^\s*[-•]\s+/', '', $l)) . '</li>';
            }
            $out .= '</ul>';
        } else {
            $out .= '<p>' . implode('<br>', array_map('inline_format', $lines)) . '</p>';
        }
    }
    return $out;
}

function inline_format(string $line): string
{
    $s = e($line);
    $s = preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', $s);
    $s = preg_replace('~(https?://[^\s<]+[^\s<.,;:!?)])~u', '<a href="$1" rel="noopener">$1</a>', $s);
    $s = preg_replace('/(?<![\w.\/@])([\w.+-]+@[\w-]+\.[\w.-]+[a-z])/iu', '<a href="mailto:$1">$1</a>', $s);
    return $s;
}

/** Zeilen wie "Name – Rolle" in [Name, Rolle] zerlegen. */
function split_lines(?string $text): array
{
    $rows = [];
    foreach (explode("\n", str_replace("\r", '', (string) $text)) as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        $parts = preg_split('/\s+[–—-]\s+/u', $line, 2);
        $rows[] = [trim($parts[0]), trim($parts[1] ?? '')];
    }
    return $rows;
}

function img_url(?string $path): string
{
    return $path ? e($path) : '';
}

function date_de(?string $iso, bool $weekday = false): string
{
    if (!$iso || !($t = strtotime($iso))) {
        return '';
    }
    $months = ['Jänner', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];
    $days   = ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'];
    $s = date('j', $t) . '. ' . $months[(int) date('n', $t) - 1] . ' ' . date('Y', $t);
    return $weekday ? $days[(int) date('w', $t)] . ', ' . $s : $s;
}

function tel_link(string $tel): string
{
    $digits = preg_replace('/[^\d+]/', '', $tel);
    if (str_starts_with($digits, '0')) {
        $digits = '+43' . substr($digits, 1);
    }
    return 'tel:' . $digits;
}

/* ---------- Gruppensymbole (Garderobenzeichen) ---------- */

const SYMBOLS = [
    'sonne'         => 'Sonne',
    'regenbogen'    => 'Regenbogen',
    'schmetterling' => 'Schmetterling',
    'kaefer'        => 'Marienkäfer',
    'stern'         => 'Stern',
    'blume'         => 'Blume',
    'mond'          => 'Mond',
    'herz'          => 'Herz',
];

const COLORS = [
    'gelb'    => ['Sonnengelb', '#F2B632'],
    'blau'    => ['Himmelblau', '#3F78D1'],
    'magenta' => ['Fliederrosa', '#B8528F'],
    'rot'     => ['Käferrot', '#D2453A'],
    'gruen'   => ['Waldgrün', '#2F7A52'],
    'orange'  => ['Orange', '#E57A2E'],
    'tuerkis' => ['Türkis', '#1E8C8C'],
];

function color_hex(?string $key): string
{
    return COLORS[$key ?? ''][1] ?? COLORS['gruen'][1];
}

function symbol_svg(?string $name): string
{
    $s = 'fill="currentColor"';
    switch ($name) {
        case 'sonne':
            $rays = '';
            for ($i = 0; $i < 8; $i++) {
                $rays .= '<rect x="30" y="3" width="4" height="11" rx="2" transform="rotate(' . ($i * 45) . ' 32 32)"/>';
            }
            return '<svg viewBox="0 0 64 64" aria-hidden="true" ' . $s . '><circle cx="32" cy="32" r="12"/>' . $rays . '</svg>';
        case 'regenbogen':
            return '<svg viewBox="0 0 64 64" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="5" stroke-linecap="round"><path d="M8 46a24 24 0 0 1 48 0"/><path d="M17 46a15 15 0 0 1 30 0" opacity=".7"/><path d="M26 46a6 6 0 0 1 12 0" opacity=".45"/></svg>';
        case 'schmetterling':
            return '<svg viewBox="0 0 64 64" aria-hidden="true" ' . $s . '><path d="M31 30C25 14 8 10 8 22c0 8 9 11 18 10-8 3-14 9-10 16 4 6 12 0 15-10z"/><path d="M33 30c6-16 23-20 23-8 0 8-9 11-18 10 8 3 14 9 10 16-4 6-12 0-15-10z"/><rect x="30.5" y="20" width="3" height="26" rx="1.5"/></svg>';
        case 'kaefer':
            return '<svg viewBox="0 0 64 64" aria-hidden="true" ' . $s . '><circle cx="32" cy="17" r="7"/><path d="M32 22c-12 0-19 9-19 20s8 16 19 16 19-5 19-16-7-20-19-20z"/><g fill="#fff" opacity=".85"><circle cx="24" cy="35" r="3.2"/><circle cx="40" cy="35" r="3.2"/><circle cx="25" cy="47" r="2.8"/><circle cx="39" cy="47" r="2.8"/><rect x="31" y="23" width="2" height="34"/></g></svg>';
        case 'stern':
            return '<svg viewBox="0 0 64 64" aria-hidden="true" ' . $s . '><path d="M32 6l7.6 16.3 17.9 2.1-13.2 12.2 3.5 17.6L32 45.4 16.2 54.2l3.5-17.6L6.5 24.4l17.9-2.1z" stroke="currentColor" stroke-width="3" stroke-linejoin="round"/></svg>';
        case 'blume':
            $p = '';
            for ($i = 0; $i < 6; $i++) {
                $p .= '<ellipse cx="32" cy="17" rx="7" ry="11" transform="rotate(' . ($i * 60) . ' 32 32)"/>';
            }
            return '<svg viewBox="0 0 64 64" aria-hidden="true" ' . $s . '>' . $p . '<circle cx="32" cy="32" r="7" fill="#fff" opacity=".85"/></svg>';
        case 'mond':
            return '<svg viewBox="0 0 64 64" aria-hidden="true" ' . $s . '><path d="M40 8a24 24 0 1 0 16 38A20 20 0 0 1 40 8z"/></svg>';
        case 'herz':
            return '<svg viewBox="0 0 64 64" aria-hidden="true" ' . $s . '><path d="M32 54S8 40 8 23c0-8 6-13 12-13 5 0 9 3 12 7 3-4 7-7 12-7 6 0 12 5 12 13 0 17-24 31-24 31z"/></svg>';
        default:
            return '<svg viewBox="0 0 64 64" aria-hidden="true" ' . $s . '><circle cx="32" cy="32" r="18"/></svg>';
    }
}

/* ---------- Schema: was die Verwaltung bearbeiten kann ---------- */

function schema(): array
{
    $symbols = SYMBOLS;
    $colors  = array_map(fn($c) => $c[0], COLORS);

    return [
        'allgemein' => [
            'title'  => 'Startseite & Kontakt',
            'help'   => 'Name, Begrüßung, Titelbild und Kontaktdaten. Diese Angaben erscheinen ganz oben und in der Fußzeile.',
            'fields' => [
                ['key' => 'hinweis', 'type' => 'textarea', 'label' => 'Wichtiger Hinweis (optional)', 'help' => 'Erscheint als farbiger Balken ganz oben, z. B. „Am 2. Mai bleibt der Kindergarten geschlossen.“ Leer lassen, wenn es nichts Besonderes gibt.', 'rows' => 2],
                ['key' => 'name', 'type' => 'text', 'label' => 'Name des Kindergartens'],
                ['key' => 'untertitel', 'type' => 'text', 'label' => 'Kurzer Untertitel'],
                ['key' => 'willkommen', 'type' => 'textarea', 'label' => 'Begrüßungstext', 'rows' => 4],
                ['key' => 'titelbild', 'type' => 'image', 'label' => 'Titelbild'],
                ['key' => 'adresse', 'type' => 'textarea', 'label' => 'Adresse', 'rows' => 2],
                ['key' => 'telefon', 'type' => 'text', 'label' => 'Telefon'],
                ['key' => 'email', 'type' => 'text', 'label' => 'E-Mail'],
            ],
        ],
        'aktuelles' => [
            'title' => 'Aktuelles',
            'help'  => 'Neuigkeiten für Eltern. Die neuesten Beiträge (nach Datum) stehen auf der Website oben.',
            'list'  => [
                'key'   => 'beitraege', 'label' => 'Beitrag', 'add' => 'Neuen Beitrag hinzufügen', 'title_field' => 'titel', 'prepend' => true,
                'fields' => [
                    ['key' => 'datum', 'type' => 'date', 'label' => 'Datum', 'default' => 'today'],
                    ['key' => 'titel', 'type' => 'text', 'label' => 'Überschrift'],
                    ['key' => 'text', 'type' => 'textarea', 'label' => 'Text', 'rows' => 5],
                    ['key' => 'bild', 'type' => 'image', 'label' => 'Bild (optional)'],
                ],
            ],
        ],
        'termine' => [
            'title' => 'Termine',
            'help'  => 'Termine werden automatisch nach Datum sortiert. Vergangene Termine verschwinden von selbst von der Website.',
            'list'  => [
                'key' => 'eintraege', 'label' => 'Termin', 'add' => 'Neuen Termin hinzufügen', 'title_field' => 'titel', 'prepend' => true,
                'fields' => [
                    ['key' => 'datum', 'type' => 'date', 'label' => 'Datum'],
                    ['key' => 'titel', 'type' => 'text', 'label' => 'Was?'],
                    ['key' => 'info', 'type' => 'text', 'label' => 'Uhrzeit oder Zusatzinfo (optional)'],
                ],
            ],
        ],
        'zeiten' => [
            'title'  => 'Öffnungszeiten & Beiträge',
            'help'   => 'Öffnungszeiten, Betreuungsangebote und die Kosten.',
            'fields' => [
                ['key' => 'oeffnungszeiten', 'type' => 'textarea', 'label' => 'Öffnungszeiten', 'rows' => 2],
                ['key' => 'betreuung', 'type' => 'textarea', 'label' => 'Betreuung & Mittagessen', 'rows' => 4],
            ],
            'list' => [
                'key' => 'tarife', 'label' => 'Tarif', 'add' => 'Tarif hinzufügen', 'title_field' => 'bezeichnung',
                'fields' => [
                    ['key' => 'bezeichnung', 'type' => 'text', 'label' => 'Bezeichnung'],
                    ['key' => 'preis', 'type' => 'text', 'label' => 'Preis pro Monat'],
                ],
            ],
        ],
        'konzept' => [
            'title'  => 'Pädagogisches Konzept',
            'help'   => 'Wie wir arbeiten. Zitate erscheinen groß hervorgehoben.',
            'fields' => [
                ['key' => 'einleitung', 'type' => 'textarea', 'label' => 'Einleitung', 'rows' => 3],
                ['key' => 'text', 'type' => 'textarea', 'label' => 'Text', 'rows' => 8],
            ],
            'list' => [
                'key' => 'zitate', 'label' => 'Zitat', 'add' => 'Zitat hinzufügen', 'title_field' => 'zitat',
                'fields' => [
                    ['key' => 'zitat', 'type' => 'textarea', 'label' => 'Zitat', 'rows' => 2],
                    ['key' => 'autor', 'type' => 'text', 'label' => 'Von wem? (optional)'],
                ],
            ],
        ],
        'gruppen' => [
            'title'  => 'Gruppen & Team',
            'help'   => 'Pro Gruppe: Symbol, Farbe und die Personen. Schreiben Sie jede Person in eine eigene Zeile, z. B. „Eva Beck – Elementarpädagogin“.',
            'fields' => [
                ['key' => 'einleitung', 'type' => 'textarea', 'label' => 'Einleitung', 'rows' => 2],
                ['key' => 'teamfoto', 'type' => 'image', 'label' => 'Teamfoto'],
            ],
            'list' => [
                'key' => 'gruppen', 'label' => 'Gruppe', 'add' => 'Gruppe hinzufügen', 'title_field' => 'name',
                'fields' => [
                    ['key' => 'name', 'type' => 'text', 'label' => 'Name der Gruppe'],
                    ['key' => 'symbol', 'type' => 'select', 'label' => 'Symbol', 'options' => $symbols],
                    ['key' => 'farbe', 'type' => 'select', 'label' => 'Farbe', 'options' => $colors],
                    ['key' => 'personen', 'type' => 'textarea', 'label' => 'Personen (eine pro Zeile: Name – Aufgabe)', 'rows' => 4],
                ],
            ],
        ],
        'feste' => [
            'title'  => 'Feste & Projekte',
            'help'   => 'Die Feste im Jahreskreis und besondere Projekte.',
            'fields' => [
                ['key' => 'feste', 'type' => 'textarea', 'label' => 'Feste im Jahreslauf (eines pro Zeile)', 'rows' => 8],
            ],
            'list' => [
                'key' => 'projekte', 'label' => 'Projekt', 'add' => 'Projekt hinzufügen', 'title_field' => 'titel',
                'fields' => [
                    ['key' => 'titel', 'type' => 'text', 'label' => 'Titel'],
                    ['key' => 'text', 'type' => 'textarea', 'label' => 'Beschreibung', 'rows' => 6],
                    ['key' => 'bild', 'type' => 'image', 'label' => 'Bild 1 (optional)'],
                    ['key' => 'bild2', 'type' => 'image', 'label' => 'Bild 2 (optional)'],
                    ['key' => 'bild3', 'type' => 'image', 'label' => 'Bild 3 (optional)'],
                ],
            ],
        ],
        'geschichte' => [
            'title'  => 'Geschichte',
            'help'   => 'Die Chronik des Kindergartens. Einträge werden in der hier gezeigten Reihenfolge angezeigt.',
            'fields' => [
                ['key' => 'einleitung', 'type' => 'textarea', 'label' => 'Einleitung', 'rows' => 3],
            ],
            'list' => [
                'key' => 'eintraege', 'label' => 'Eintrag', 'add' => 'Eintrag hinzufügen', 'title_field' => 'titel',
                'fields' => [
                    ['key' => 'jahr', 'type' => 'text', 'label' => 'Jahr'],
                    ['key' => 'titel', 'type' => 'text', 'label' => 'Überschrift'],
                    ['key' => 'text', 'type' => 'textarea', 'label' => 'Text', 'rows' => 5],
                    ['key' => 'bild', 'type' => 'image', 'label' => 'Bild (optional)'],
                    ['key' => 'bildtext', 'type' => 'text', 'label' => 'Bildunterschrift / Fotonachweis (optional)'],
                ],
            ],
        ],
        'galerie' => [
            'title' => 'Bilder',
            'help'  => 'Fotos für die Bildergalerie. Bitte nur Fotos verwenden, für die die Eltern zugestimmt haben.',
            'list'  => [
                'key' => 'bilder', 'label' => 'Foto', 'add' => 'Foto hinzufügen', 'title_field' => 'beschreibung', 'prepend' => true,
                'fields' => [
                    ['key' => 'bild', 'type' => 'image', 'label' => 'Foto'],
                    ['key' => 'beschreibung', 'type' => 'text', 'label' => 'Beschreibung (optional)'],
                ],
            ],
        ],
        'rechtliches' => [
            'title'  => 'Impressum & Datenschutz',
            'help'   => 'Gesetzlich vorgeschriebene Angaben. Bitte mit der Gemeinde abstimmen.',
            'fields' => [
                ['key' => 'impressum', 'type' => 'textarea', 'label' => 'Impressum', 'rows' => 10],
                ['key' => 'datenschutz', 'type' => 'textarea', 'label' => 'Datenschutzerklärung', 'rows' => 14],
            ],
        ],
    ];
}

/* ---------- Textlänge ohne mbstring-Erweiterung (nicht auf jedem Webspace vorhanden) ---------- */

function u_len(string $s): int
{
    return function_exists('mb_strlen') ? mb_strlen($s) : (int) preg_match_all('/./su', $s);
}

function u_cut(string $s, int $max): string
{
    if (u_len($s) <= $max) {
        return $s;
    }
    preg_match('/^.{0,' . $max . '}/su', $s, $m);
    return $m[0];
}
