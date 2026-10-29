<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

if (PHP_SAPI !== 'cli' && !headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        render_db_error();
        exit;
    }
}

function render_db_error(): void
{
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, "Cannot connect to MySQL. Start MySQL in XAMPP and check config.php.\n");
        exit(1);
    }

    http_response_code(503);
    $name = APP_NAME;
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>Setup needed · ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</title>';
    echo '<link rel="stylesheet" href="assets/css/style.css"></head><body>';
    echo '<main class="auth-shell"><section class="auth-card">';
    echo '<p class="eyebrow">Database</p><h1>MySQL is not connected.</h1>';
    echo '<p>Start MySQL from the XAMPP control panel, then run setup. The default XAMPP login is user <strong>root</strong> with an empty password, set in <code>config.php</code>.</p>';
    echo '<p><a class="button" href="install.php">Run setup</a></p>';
    echo '</section></main></body></html>';
    exit;
}

function catalog_ready(): bool
{
    try {
        db()->query('SELECT 1 FROM users LIMIT 1');
        db()->query('SELECT 1 FROM artists LIMIT 1');
        db()->query('SELECT 1 FROM designs LIMIT 1');
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

function require_catalog(): void
{
    if (catalog_ready()) {
        return;
    }

    $pageTitle = 'Setup needed';
    require __DIR__ . '/header.php';
    echo '<section class="wrap notice">';
    echo '<h1>Set up the database first.</h1>';
    echo '<p>This creates the Mehandi Design tables and loads the sample artists.</p>';
    echo '<p><a class="button" href="install.php">Run setup</a></p>';
    echo '</section>';
    require __DIR__ . '/footer.php';
    exit;
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
}

function text_length(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
}

function inr(int $amount): string
{
    return '₹' . number_format($amount);
}

function hours(float|string $value): string
{
    $hours = (float) $value;
    if ($hours < 1) {
        return (int) round($hours * 60) . ' min';
    }
    $formatted = rtrim(rtrim(number_format($hours, 1), '0'), '.');
    return $formatted . ($hours === 1.0 ? ' hr' : ' hrs');
}

function accent(string $value): string
{
    return preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? $value : '#8c3d2f';
}

function design_categories(): array
{
    return ['Bridal', 'Arabic', 'Traditional', 'Portrait', 'Minimal', 'Festive'];
}

function occasions(): array
{
    return ['Wedding', 'Engagement', 'Festival', 'Party', 'Casual'];
}

function requested_category(): string
{
    $category = trim((string) ($_GET['category'] ?? ''));
    return in_array($category, design_categories(), true) ? $category : '';
}

function build_query(array $params): string
{
    $clean = [];
    foreach ($params as $key => $value) {
        if ($value === null || $value === '' || $value === 0 || $value === '0') {
            continue;
        }
        $clean[$key] = $value;
    }
    return $clean ? '?' . http_build_query($clean) : '';
}

function append_query(string $path, array $params): string
{
    $query = build_query($params);
    if ($query === '') {
        return $path;
    }
    $separator = str_contains($path, '?') ? '&' : '?';
    return $path . $separator . substr($query, 1);
}

function selected(string|int $current, string|int $value): string
{
    return (string) $current === (string) $value ? ' selected' : '';
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function take_flash(): ?array
{
    if (empty($_SESSION['flash']) || !is_array($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf']) || !is_string($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_valid(): bool
{
    $sent = $_POST['csrf'] ?? '';
    $known = $_SESSION['csrf'] ?? '';
    return is_string($sent) && is_string($known) && $known !== '' && hash_equals($known, $sent);
}

function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    static $loaded = false;
    static $user = null;
    if ($loaded) {
        return $user;
    }
    $loaded = true;

    try {
        $stmt = db()->prepare('SELECT id, name, email, phone FROM users WHERE id = ?');
        $stmt->execute([(int) $_SESSION['user_id']]);
        $row = $stmt->fetch();
        $user = $row ?: null;
    } catch (PDOException $e) {
        $user = null;
    }

    if (!$user) {
        unset($_SESSION['user_id']);
    }

    return $user;
}

function safe_next(string $next): string
{
    $next = trim($next);
    if ($next === '' || strlen($next) > 200) {
        return 'artists.php';
    }
    if (preg_match('/^[a-z0-9_\-]+\.php(\?[a-z0-9_\-=&%\.\+]*)?$/i', $next)) {
        return $next;
    }
    return 'artists.php';
}

function login_link(string $next): string
{
    return 'login.php?next=' . rawurlencode(safe_next($next));
}

function book_link(int $designId): string
{
    $target = 'book.php?design_id=' . $designId;
    if (current_user()) {
        return $target;
    }
    return login_link($target);
}

function require_login(): void
{
    if (current_user()) {
        return;
    }

    $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
    $file = basename($path);
    $query = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY);
    $next = $file . ($query ? '?' . $query : '');

    flash('error', 'Log in to select an artist.');
    redirect(login_link($next));
}

function nav_active(array $files): string
{
    $current = basename($_SERVER['SCRIPT_NAME'] ?? '');
    return in_array($current, $files, true) ? ' is-active' : '';
}
