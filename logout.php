<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

if (!is_post() || !csrf_valid()) {
    redirect('index.php');
}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $params['path'],
        'domain' => $params['domain'],
        'secure' => (bool) $params['secure'],
        'httponly' => (bool) $params['httponly'],
        'samesite' => $params['samesite'] ?? 'Lax',
    ]);
}
session_destroy();
header('Location: index.php');
exit;
