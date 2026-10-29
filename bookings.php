<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
require_catalog();
require_login();

$user = current_user();

if (is_post()) {
    if (!csrf_valid()) {
        flash('error', 'The form expired. Please try again.');
        redirect('bookings.php');
    }
    if (($_POST['action'] ?? '') === 'cancel') {
        $stmt = db()->prepare(
            "UPDATE bookings SET status = 'cancelled' WHERE id = ? AND user_id = ? AND status = 'confirmed'"
        );
        $stmt->execute([(int) ($_POST['booking_id'] ?? 0), (int) $user['id']]);
        flash($stmt->rowCount() > 0 ? 'success' : 'error', $stmt->rowCount() > 0 ? 'Selection cancelled.' : 'That selection could not be cancelled.');
        redirect('bookings.php');
    }
}

$stmt = db()->prepare(
    'SELECT b.id, b.event_date, b.amount, b.notes, b.occasion, b.status, b.created_at,
            a.name AS artist_name, a.city, a.initials, a.accent,
            d.title AS design_title, d.category
     FROM bookings b
     JOIN artists a ON a.id = b.artist_id
     JOIN designs d ON d.id = b.design_id
     WHERE b.user_id = ?
     ORDER BY b.created_at DESC'
);
$stmt->execute([(int) $user['id']]);
$bookings = $stmt->fetchAll();

$pageTitle = 'My selections';
require __DIR__ . '/includes/header.php';
?>
<section class="page-head wrap">
  <p class="eyebrow"><?= e($user['name']) ?></p>
  <h1>My selections</h1>
  <p>Artists you have chosen, with the design and the amount for each booking.</p>
</section>

<section class="section">
  <div class="wrap">
    <?php if (!$bookings): ?>
      <div class="empty">
        <h2>You have not selected an artist yet.</h2>
        <p>Filter the list by design and amount, then choose one.</p>
        <p><a class="button" href="artists.php">Browse artists</a></p>
      </div>
    <?php else: ?>
      <div class="booking-list">
        <?php foreach ($bookings as $booking): ?>
          <article class="booking-card<?= $booking['status'] === 'cancelled' ? ' is-cancelled' : '' ?>">
            <div class="booking-top">
              <div class="identity">
                <span class="medallion" style="--accent: <?= e(accent($booking['accent'])) ?>"><?= e($booking['initials']) ?></span>
                <div>
                  <h2><?= e($booking['artist_name']) ?></h2>
                  <p class="meta"><?= e($booking['city']) ?> · <?= e($booking['design_title']) ?></p>
                </div>
              </div>
              <span class="status status-<?= $booking['status'] === 'confirmed' ? 'confirmed' : 'cancelled' ?>"><?= e($booking['status']) ?></span>
            </div>
            <ul class="stat-list">
              <li><span>Design</span><strong><?= e($booking['category']) ?></strong></li>
              <li><span>Amount</span><strong><?= inr((int) $booking['amount']) ?></strong></li>
              <li><span>Occasion</span><strong><?= e($booking['occasion']) ?></strong></li>
              <li><span>Event date</span><strong><?= e(date('d M Y', strtotime((string) $booking['event_date']))) ?></strong></li>
            </ul>
            <?php if ($booking['notes']): ?>
              <p><?= nl2br(e($booking['notes'])) ?></p>
            <?php endif; ?>
            <?php if ($booking['status'] === 'confirmed'): ?>
              <form class="booking-actions" method="post" action="bookings.php" onsubmit="return confirm('Cancel this selection?');">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="cancel">
                <input type="hidden" name="booking_id" value="<?= (int) $booking['id'] ?>">
                <button class="button button-ghost button-small" type="submit">Cancel selection</button>
              </form>
            <?php endif; ?>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
