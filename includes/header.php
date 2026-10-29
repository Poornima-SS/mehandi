<?php
declare(strict_types=1);

if (!defined('APP')) {
    http_response_code(403);
    exit;
}

$user = current_user();
$flash = take_flash();
$pageTitle = $pageTitle ?? APP_NAME;
$fullTitle = $pageTitle === APP_NAME ? APP_NAME : $pageTitle . ' · ' . APP_NAME;
$flashType = ($flash['type'] ?? '') === 'success' ? 'success' : 'error';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($fullTitle) ?></title>
  <meta name="description" content="Browse sample mehandi artists and choose one by design style and budget.">
  <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 48 48'%3E%3Ccircle cx='24' cy='24' r='22' fill='%238c3d2f'/%3E%3Cpath d='M24 8c6 6 8 10 8 16a8 8 0 1 1-16 0c0-2 2-6 8-16z' fill='%23f6efe4'/%3E%3C/svg%3E">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,560;9..144,650&family=Outfit:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <header class="site-header">
    <a class="brand" href="index.php">
      <span class="brand-mark" aria-hidden="true">
        <svg viewBox="0 0 48 48" width="36" height="36">
          <circle cx="24" cy="24" r="22" fill="none" stroke="currentColor" stroke-width="1.5"></circle>
          <path d="M24 8c6 6 8 10 8 16a8 8 0 1 1-16 0c0-2 2-6 8-16z" fill="currentColor"></path>
          <circle cx="24" cy="28" r="2.2" fill="#f6efe4"></circle>
        </svg>
      </span>
      <span>
        <strong>Mehandi Design</strong>
        <small>Artist finder</small>
      </span>
    </a>
    <button class="nav-toggle" type="button" data-nav-toggle aria-expanded="false" aria-controls="site-nav" aria-label="Open menu">
      <span></span><span></span><span></span>
    </button>
    <nav class="site-nav" id="site-nav" data-nav>
      <a class="<?= trim(nav_active(['index.php'])) ?>" href="index.php">Home</a>
      <a class="<?= trim(nav_active(['artists.php', 'artist.php', 'book.php'])) ?>" href="artists.php">Artists</a>
      <?php if ($user): ?>
        <a class="<?= trim(nav_active(['bookings.php'])) ?>" href="bookings.php">My selections</a>
        <a class="nav-user" href="bookings.php"><?= e($user['name']) ?></a>
        <form method="post" action="logout.php" class="inline-form">
          <?= csrf_field() ?>
          <button type="submit" class="link-button">Log out</button>
        </form>
      <?php else: ?>
        <a class="<?= trim(nav_active(['login.php'])) ?>" href="login.php">Log in</a>
        <a class="button button-small" href="register.php">Create account</a>
      <?php endif; ?>
    </nav>
  </header>
  <main>
    <?php if ($flash): ?>
      <div class="wrap">
        <div class="flash flash-<?= e($flashType) ?>" data-flash role="status">
          <p><?= e($flash['message']) ?></p>
          <button type="button" data-dismiss aria-label="Dismiss">×</button>
        </div>
      </div>
    <?php endif; ?>
