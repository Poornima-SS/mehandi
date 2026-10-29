<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
require_catalog();

if (current_user()) {
    redirect('artists.php');
}

$errors = [];
$email = '';
$next = safe_next((string) ($_GET['next'] ?? $_POST['next'] ?? ''));

if (is_post()) {
    if (!csrf_valid()) {
        flash('error', 'The form expired. Please try again.');
        redirect(login_link($next));
    }

    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        $errors[] = 'Enter the email and password for your account.';
    } else {
        $stmt = db()->prepare('SELECT id, password_hash FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        if (!$row || !password_verify($password, $row['password_hash'])) {
            $errors[] = 'Email or password is incorrect.';
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $row['id'];
            flash('success', 'You are logged in. Select an artist when you are ready.');
            redirect($next);
        }
    }
}

$pageTitle = 'Log in';
require __DIR__ . '/includes/header.php';
?>
<section class="auth-shell">
  <div class="auth-card">
    <p class="eyebrow">Account</p>
    <h1>Log in</h1>
    <p class="form-note">Log in to select a mehandi artist. Browsing the list does not need an account.</p>
    <?php if ($errors): ?>
      <ul class="error-list">
        <?php foreach ($errors as $error): ?>
          <li><?= e($error) ?></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
    <form class="form-stack" method="post" action="login.php">
      <?= csrf_field() ?>
      <input type="hidden" name="next" value="<?= e($next) ?>">
      <label class="field">
        Email
        <input type="email" name="email" value="<?= e($email) ?>" autocomplete="email" required>
      </label>
      <label class="field">
        Password
        <input type="password" name="password" autocomplete="current-password" required>
      </label>
      <button class="button button-block" type="submit">Log in</button>
    </form>
    <p class="sample-login">Sample account: <strong>demo@mehandi.test</strong> / <strong>demo123</strong></p>
    <p class="form-note">New here? <a href="register.php?next=<?= e(rawurlencode($next)) ?>">Create an account</a></p>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
