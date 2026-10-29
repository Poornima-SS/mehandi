<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
require_catalog();

if (current_user()) {
    redirect('artists.php');
}

$errors = [];
$values = ['name' => '', 'email' => '', 'phone' => ''];
$next = safe_next((string) ($_GET['next'] ?? $_POST['next'] ?? ''));

if (is_post()) {
    if (!csrf_valid()) {
        flash('error', 'The form expired. Please try again.');
        redirect('register.php' . build_query(['next' => $next === 'artists.php' ? null : $next]));
    }

    $values['name'] = trim((string) ($_POST['name'] ?? ''));
    $values['email'] = strtolower(trim((string) ($_POST['email'] ?? '')));
    $values['phone'] = trim((string) ($_POST['phone'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['confirm'] ?? '');

    if (text_length($values['name']) < 2 || text_length($values['name']) > 100) {
        $errors[] = 'Enter your name.';
    }
    if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL) || text_length($values['email']) > 150) {
        $errors[] = 'Enter a valid email address.';
    }
    if ($values['phone'] !== '' && !preg_match('/^[0-9+\-\s]{8,20}$/', $values['phone'])) {
        $errors[] = 'Enter a valid phone number, or leave it blank.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Use a password of at least 6 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'The two passwords do not match.';
    }

    if (!$errors) {
        try {
            $stmt = db()->prepare(
                'INSERT INTO users (name, email, password_hash, phone) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([
                $values['name'],
                $values['email'],
                password_hash($password, PASSWORD_DEFAULT),
                $values['phone'] !== '' ? $values['phone'] : null,
            ]);
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) db()->lastInsertId();
            flash('success', 'Account created. You can select an artist now.');
            redirect($next);
        } catch (PDOException $e) {
            if ((string) $e->getCode() === '23000') {
                $errors[] = 'An account with this email already exists. Log in instead.';
            } else {
                throw $e;
            }
        }
    }
}

$pageTitle = 'Create account';
require __DIR__ . '/includes/header.php';
?>
<section class="auth-shell">
  <div class="auth-card">
    <p class="eyebrow">Account</p>
    <h1>Create an account</h1>
    <p class="form-note">Save your artist selection against this login.</p>
    <?php if ($errors): ?>
      <ul class="error-list">
        <?php foreach ($errors as $error): ?>
          <li><?= e($error) ?></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
    <form class="form-stack" method="post" action="register.php">
      <?= csrf_field() ?>
      <input type="hidden" name="next" value="<?= e($next) ?>">
      <label class="field">
        Name
        <input type="text" name="name" value="<?= e($values['name']) ?>" autocomplete="name" required maxlength="100">
      </label>
      <label class="field">
        Email
        <input type="email" name="email" value="<?= e($values['email']) ?>" autocomplete="email" required maxlength="150">
      </label>
      <label class="field">
        Phone <span class="form-note">optional</span>
        <input type="tel" name="phone" value="<?= e($values['phone']) ?>" autocomplete="tel" maxlength="20">
      </label>
      <label class="field">
        Password
        <input type="password" name="password" autocomplete="new-password" required minlength="6">
      </label>
      <label class="field">
        Confirm password
        <input type="password" name="confirm" autocomplete="new-password" required minlength="6">
      </label>
      <button class="button button-block" type="submit">Create account</button>
    </form>
    <p class="form-note">Already registered? <a href="login.php?next=<?= e(rawurlencode($next)) ?>">Log in</a></p>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
