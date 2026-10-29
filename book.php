<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
require_catalog();
require_login();

function load_design(int $id): ?array
{
    $stmt = db()->prepare(
        'SELECT d.id, d.artist_id, d.title, d.category, d.price, d.duration_hours, d.description,
                a.name AS artist_name, a.city, a.initials, a.accent, a.specialty
         FROM designs d
         JOIN artists a ON a.id = d.artist_id
         WHERE d.id = ?'
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

$requestedId = (int) ($_POST['design_id'] ?? $_GET['design_id'] ?? 0);
$design = load_design($requestedId);
if (!$design) {
    http_response_code(404);
    $pageTitle = 'Design not found';
    require __DIR__ . '/includes/header.php';
    echo '<section class="wrap page-head"><h1>That design is not available.</h1><p><a href="artists.php">Back to artists</a></p></section>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$siblings = db()->prepare(
    'SELECT id, title, category, price, duration_hours FROM designs WHERE artist_id = ? ORDER BY price ASC'
);
$siblings->execute([(int) $design['artist_id']]);
$siblings = $siblings->fetchAll();

$errors = [];
$values = [
    'design_id' => (int) $design['id'],
    'event_date' => (string) ($_POST['event_date'] ?? ''),
    'occasion' => (string) ($_POST['occasion'] ?? 'Wedding'),
    'notes' => trim((string) ($_POST['notes'] ?? '')),
];

if (is_post()) {
    if (!csrf_valid()) {
        flash('error', 'The form expired. Please try again.');
        redirect('book.php?design_id=' . (int) $design['id']);
    }

    $chosen = load_design((int) ($_POST['design_id'] ?? 0));
    if (!$chosen || (int) $chosen['artist_id'] !== (int) $design['artist_id']) {
        $errors[] = 'Choose a design from this artist.';
    } else {
        $design = $chosen;
        $values['design_id'] = (int) $chosen['id'];
    }

    $date = DateTime::createFromFormat('Y-m-d', $values['event_date']);
    $dateErrors = DateTime::getLastErrors();
    $dateBroken = $dateErrors !== false && (($dateErrors['warning_count'] ?? 0) > 0 || ($dateErrors['error_count'] ?? 0) > 0);
    if (!$date || $dateBroken || $date->format('Y-m-d') !== $values['event_date']) {
        $errors[] = 'Choose a valid event date.';
    } elseif ($date < new DateTime('today')) {
        $errors[] = 'The event date cannot be in the past.';
    }

    if (!in_array($values['occasion'], occasions(), true)) {
        $errors[] = 'Choose an occasion.';
    }
    if (text_length($values['notes']) > 500) {
        $errors[] = 'Keep the note under 500 characters.';
    }

    if (!$errors && isset($chosen) && $chosen) {
        $user = current_user();
        $insert = db()->prepare(
            'INSERT INTO bookings (user_id, artist_id, design_id, event_date, occasion, amount, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $insert->execute([
            (int) $user['id'],
            (int) $chosen['artist_id'],
            (int) $chosen['id'],
            $values['event_date'],
            $values['occasion'],
            (int) $chosen['price'],
            $values['notes'] !== '' ? $values['notes'] : null,
        ]);
        flash('success', 'Artist selected. ' . $chosen['artist_name'] . ' is booked for ' . inr((int) $chosen['price']) . '.');
        redirect('bookings.php');
    }
}

$pageTitle = 'Select artist';
require __DIR__ . '/includes/header.php';
?>
<section class="page-head wrap">
  <p class="eyebrow"><?= e($design['city']) ?></p>
  <h1>Select <?= e($design['artist_name']) ?></h1>
  <p>The amount comes from the design you pick. It is not typed in by hand, so the price stays the one listed for that mehandi.</p>
</section>

<section class="section">
  <div class="wrap book-layout">
    <form class="card" method="post" action="book.php?design_id=<?= (int) $design['id'] ?>" style="padding: 1.15rem;">
      <?= csrf_field() ?>
      <?php if ($errors): ?>
        <ul class="error-list">
          <?php foreach ($errors as $error): ?>
            <li><?= e($error) ?></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
      <label class="field">
        Design and amount
        <select name="design_id" data-design-select required>
          <?php foreach ($siblings as $option): ?>
            <option
              value="<?= (int) $option['id'] ?>"
              data-price="<?= (int) $option['price'] ?>"
              data-title="<?= e($option['title']) ?>"
              data-category="<?= e($option['category']) ?>"
              data-hours="<?= e(hours($option['duration_hours'])) ?>"
              <?= selected($values['design_id'], $option['id']) ?>
            ><?= e($option['title']) ?> — <?= inr((int) $option['price']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="field">
        Occasion
        <select name="occasion" required>
          <?php foreach (occasions() as $occasion): ?>
            <option value="<?= e($occasion) ?>"<?= selected($values['occasion'], $occasion) ?>><?= e($occasion) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="field">
        Event date
        <input type="date" name="event_date" required min="<?= e(date('Y-m-d')) ?>" value="<?= e($values['event_date']) ?>">
      </label>
      <label class="field">
        Notes
        <textarea name="notes" maxlength="500" placeholder="Both hands, guest mehandi, or the time you need the artist."><?= e($values['notes']) ?></textarea>
      </label>
      <button class="button" type="submit">Confirm selection</button>
    </form>
    <aside class="summary">
      <p class="eyebrow">Your requirements</p>
      <h2 data-summary-title><?= e($design['title']) ?></h2>
      <p data-summary-category><?= e($design['category']) ?> · <?= e($design['specialty']) ?></p>
      <p class="summary-amount" data-amount><?= inr((int) $design['price']) ?></p>
      <p class="form-note"><?= e($design['artist_name']) ?> · about <span data-summary-time><?= e(hours($design['duration_hours'])) ?></span></p>
    </aside>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
