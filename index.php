<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
require_catalog();

$stats = db()->query(
    'SELECT
        (SELECT COUNT(*) FROM artists) AS artists,
        (SELECT COUNT(*) FROM designs) AS designs,
        (SELECT MIN(price) FROM designs) AS lowest'
)->fetch();

$featured = db()->query(
    'SELECT a.id, a.name, a.city, a.specialty, a.rating, a.initials, a.accent,
            MIN(d.price) AS starting_price
     FROM artists a
     JOIN designs d ON d.artist_id = a.id
     GROUP BY a.id, a.name, a.city, a.specialty, a.rating, a.initials, a.accent
     ORDER BY a.rating DESC, starting_price ASC
     LIMIT 4'
)->fetchAll();

$user = current_user();
$pageTitle = 'Home';
require __DIR__ . '/includes/header.php';
?>
<section class="hero">
  <div class="wrap hero-grid">
    <div>
      <p class="eyebrow">Mehandi Design</p>
      <h1>Choose a mehandi artist by the design you want and the amount you can spend.</h1>
      <p class="lede">Browse sample artists, filter by style and budget, then select the person whose design fits the occasion.</p>
      <div class="hero-actions">
        <a class="button" href="artists.php">Browse artists</a>
        <?php if (!$user): ?>
          <a class="button button-ghost" href="register.php">Create an account</a>
        <?php else: ?>
          <a class="button button-ghost" href="bookings.php">My selections</a>
        <?php endif; ?>
      </div>
      <div class="style-links">
        <?php foreach (design_categories() as $category): ?>
          <a href="artists.php?category=<?= e(rawurlencode($category)) ?>"><?= e($category) ?></a>
        <?php endforeach; ?>
      </div>
    </div>
    <aside class="hero-panel">
      <p>What the amount covers</p>
      <ul>
        <li><span>Minimal wrist</span><strong>from <?= inr((int) $stats['lowest']) ?></strong></li>
        <li><span>Arabic and festive</span><strong>₹1,400 – ₹4,500</strong></li>
        <li><span>Bridal packages</span><strong>₹5,500 – ₹18,000</strong></li>
      </ul>
    </aside>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <ul class="stats">
      <li><strong><?= (int) $stats['artists'] ?></strong><span>sample artists</span></li>
      <li><strong><?= (int) $stats['designs'] ?></strong><span>designs with prices</span></li>
      <li><strong><?= inr((int) $stats['lowest']) ?></strong><span>lowest amount</span></li>
    </ul>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <div class="section-head">
      <p class="eyebrow">How it works</p>
      <h2>Three steps to a booking</h2>
    </div>
    <ol class="steps">
      <li class="step">
        <span>1</span>
        <h3>Create an account</h3>
        <p>Log in before you select an artist. You can browse the list before that.</p>
      </li>
      <li class="step">
        <span>2</span>
        <h3>Set design and amount</h3>
        <p>Pick a mehandi style and the most you want to spend. Only matching artists stay on the list.</p>
      </li>
      <li class="step">
        <span>3</span>
        <h3>Select the artist</h3>
        <p>Open a design, check the price, and save the booking under My selections.</p>
      </li>
    </ol>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <div class="section-head">
      <p class="eyebrow">Sample studio</p>
      <h2>Artists to compare</h2>
      <p>Two Jaipur artists both take bridal work, at different amounts, so you can compare like for like.</p>
    </div>
    <div class="featured-grid">
      <?php foreach ($featured as $artist): ?>
        <a class="mini-card" href="artist.php?id=<?= (int) $artist['id'] ?>">
          <span class="medallion" style="--accent: <?= e(accent($artist['accent'])) ?>"><?= e($artist['initials']) ?></span>
          <h3><?= e($artist['name']) ?></h3>
          <p class="meta"><?= e($artist['city']) ?> · <?= e($artist['specialty']) ?></p>
          <p class="price">From <?= inr((int) $artist['starting_price']) ?></p>
          <p class="rating"><span aria-hidden="true">★</span> <?= e(number_format((float) $artist['rating'], 1)) ?></p>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
