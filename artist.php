<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
require_catalog();

$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare(
    'SELECT id, name, city, experience_years, specialty, bio, rating, phone, initials, accent
     FROM artists WHERE id = ?'
);
$stmt->execute([$id]);
$artist = $stmt->fetch();

if (!$artist) {
    http_response_code(404);
    $pageTitle = 'Artist not found';
    require __DIR__ . '/includes/header.php';
    echo '<section class="wrap page-head"><h1>That artist is not on the list.</h1><p><a href="artists.php">Back to artists</a></p></section>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$designs = db()->prepare(
    'SELECT id, title, category, price, duration_hours, description
     FROM designs WHERE artist_id = ? ORDER BY price ASC'
);
$designs->execute([$id]);
$designs = $designs->fetchAll();

$category = requested_category();
$budget = max(0, (int) ($_GET['budget'] ?? 0));
$city = trim((string) ($_GET['city'] ?? ''));
$sort = ($_GET['sort'] ?? '') === 'rating' ? 'rating' : null;
$back = 'artists.php' . build_query([
    'category' => $category,
    'budget' => $budget,
    'city' => $city,
    'sort' => $sort,
]);

$pageTitle = $artist['name'];
require __DIR__ . '/includes/header.php';
?>
<section class="page-head wrap">
  <p class="eyebrow"><a href="<?= e($back) ?>">Artists</a></p>
  <h1><?= e($artist['name']) ?></h1>
</section>

<section class="section">
  <div class="wrap profile">
    <aside class="profile-side">
      <span class="medallion" style="--accent: <?= e(accent($artist['accent'])) ?>"><?= e($artist['initials']) ?></span>
      <h2><?= e($artist['specialty']) ?></h2>
      <p><?= e($artist['bio']) ?></p>
      <ul class="stat-list">
        <li><span>City</span><strong><?= e($artist['city']) ?></strong></li>
        <li><span>Experience</span><strong><?= (int) $artist['experience_years'] ?> years</strong></li>
        <li><span>Rating</span><strong><?= e(number_format((float) $artist['rating'], 1)) ?></strong></li>
        <li><span>Phone</span><strong><?= e($artist['phone']) ?></strong></li>
      </ul>
    </aside>
    <div>
      <?php if ($budget > 0 || $category !== ''): ?>
        <p class="filter-note">
          Designs outside
          <?php if ($category !== ''): ?><?= e($category) ?><?php endif; ?>
          <?php if ($budget > 0): ?><?= $category !== '' ? ' and ' : '' ?>your maximum of <?= inr($budget) ?><?php endif; ?>
          are marked. You can still open one if you want to raise the amount.
        </p>
      <?php endif; ?>
      <div class="design-menu">
        <?php foreach ($designs as $design): ?>
          <?php
            $fitsBudget = $budget <= 0 || (int) $design['price'] <= $budget;
            $fitsCategory = $category === '' || $design['category'] === $category;
            $fits = $fitsBudget && $fitsCategory;
          ?>
          <article class="artist-card<?= $fits ? '' : ' is-dim' ?>">
            <div class="design-row" style="border-top: 0; padding-top: 0;">
              <div class="design-copy">
                <h3><?= e($design['title']) ?></h3>
                <span class="tag"><?= e($design['category']) ?></span>
                <p><?= e($design['description']) ?> About <?= e(hours($design['duration_hours'])) ?>.</p>
                <?php if (!$fits): ?>
                  <p>Outside your current requirements.</p>
                <?php endif; ?>
              </div>
              <div class="design-actions">
                <span class="price"><?= inr((int) $design['price']) ?></span>
                <a class="button button-small" href="<?= e(book_link((int) $design['id'])) ?>">Select</a>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
