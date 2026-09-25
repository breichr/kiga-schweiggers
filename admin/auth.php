<?php
declare(strict_types=1);
require __DIR__ . '/../inc/functions.php';

/** HTTPS erkennen – auch hinter einem Proxy wie Traefik/Caddy von Coolify. */
function is_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }
    $proto = strtolower(trim(explode(',', (string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))[0]));
    return $proto === 'https';
}

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => dirname($_SERVER['SCRIPT_NAME']) . '/',
    'secure'   => is_https(),
    'httponly' => true,
    'samesite' => 'Strict',
]);
session_name('kgadmin');
session_start();

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('Cache-Control: no-store');

function config(): ?array
{
    if (!is_file(CONFIG_FILE)) {
        return null;
    }
    $c = include CONFIG_FILE;
    return is_array($c) ? $c : null;
}

function write_config(string $password): bool
{
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $php  = "<?php\n// Automatisch erstellt – nicht von Hand bearbeiten.\nreturn " . var_export(['hash' => $hash], true) . ";\n";
    return file_put_contents(CONFIG_FILE, $php, LOCK_EX) !== false;
}

function logged_in(): bool
{
    return !empty($_SESSION['ok']) && ($_SESSION['last'] ?? 0) > time() - 4 * 3600;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function check_csrf(): bool
{
    $t = $_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF'] ?? '';
    return is_string($t) && hash_equals(csrf_token(), $t);
}

/** Einfache Sperre gegen Passwort-Raten: nach 5 Fehlversuchen 10 Minuten Pause. */
function login_blocked(): bool
{
    $f = ROOT . '/data/.login-versuche';
    $d = is_file($f) ? json_decode((string) file_get_contents($f), true) : null;
    return is_array($d) && ($d['n'] ?? 0) >= 5 && ($d['t'] ?? 0) > time() - 600;
}

function login_failed(): void
{
    $f = ROOT . '/data/.login-versuche';
    $d = is_file($f) ? json_decode((string) file_get_contents($f), true) : null;
    if (!is_array($d) || ($d['t'] ?? 0) < time() - 600) {
        $d = ['n' => 0];
    }
    $d['n']++;
    $d['t'] = time();
    file_put_contents($f, json_encode($d));
}

function login_reset(): void
{
    @unlink(ROOT . '/data/.login-versuche');
}
