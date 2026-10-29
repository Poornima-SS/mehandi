<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
require_catalog();

$category = requested_category();
$city = trim((string) ($_GET['city'] ?? ''));
$budget = max(0, (int) ($_GET['budget'] ?? 0));
$sort = ($_GET['sort'] ?? '') === 'rating' ? 'rating' : 'price';

$cities = db()->query('SELECT DISTINCT city FROM artists ORDER BY city')->fetchAll(PDO::FETCH_COLUMN);
if (!in_array($city, $cities, true)) {
    $city = '';
}

$sql = 'SELECT a.id AS artist_id, a.name, a.city, a.experience_years, a.specialty,
               a.rating, a.initials, a.accent,
               d.id AS design_id, d.title, d.category, d.price, d.duration_hours
        FROM artists a
        INNER JOIN designs d ON d.artist_id = a.id
        WHERE 1 = 1';
$params = [];
if ($category !== '') {
    $sql .= ' AND d.category = ?';
    $params[] = $category;
}
if ($budget > 0) {
    $sql .= ' AND d.price <= ?';
    $params[] = $budget;
}
if ($city !== '') {
    $sql .= ' AND a.city = ?';
    $params[] = $city;
}
$sql .= ' ORDER BY d.price ASC, a.rating DESC';

$stmt = db()->prepare($sql);
$stmt->execute($params);

$groups = [];
foreach ($stmt as $row) {
    $id = (int) $row['artist_id'];
    if (!isset($groups[$id])) {
        $groups[$id] = [
            'artist' => $row,
            'designs' => [],
            'min_price' => (int) $row['price'],
        ];
    }
    $groups[$id]['designs'][] = $row;
    $groups[$id]['min_price'] = min($groups[$id]['min_price'], (int) $row['price']);
}

$groups = array_values($groups);
usort($groups, function (array $a, array $b) use ($sort): int {
    if ($sort === 'rating') {
        return ((float) $b['artist']['rating']) <=> ((float) $a['artist']['rating']);
    }
    return $a['min_price'] <=> $b['min_price'];
});

$artistCount = count($groups);
$summary = $artistCount === 1 ? '1 artist' : $artistCount . ' artists';
if ($category !== '') {
    $summary .= ' for ' . $category . ' mehandi';
} else {
    $summary .= ' with open designs';
}
if ($city !== '') {
    $summary .= ' in ' . $city;
}
if ($budget > 0) {
    $summary .= ' within ' . inr($budget);
}

$budgetLabel = $budget > 0 ? inr($budget) : 'Any amount';
$pageTitle = 'Artists';
require __DIR__ . '/includes/header.php';
?>
<section class="page-head wrap">
  <p class="eyebrow">Find an artist</p>
  <h1>Select by design and amount</h1>
  <p>These are sample artists, including two in Jaipur who both offer bridal work at different prices. Set the style and the most you want to spend.</p>
</section>

<section class="section">
  <div class="wrap">
    <form class="filters card" method="get" action="artists.php" data-filters>
      <label class="field">
        Design
        <select name="category">
          <option value="">All designs</option>
          <?php foreach (design_categories() as $option): ?>
            <option value="<?= e($option) ?>"<?= selected($category, $option) ?>><?= e($option) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="field">
        City
        <select name="city">
          <option value="">All cities</option>
          <?php foreach ($cities as $option): ?>
            <option value="<?= e($option) ?>"<?= selected($city, $option) ?>><?= e($option) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="field">
        <span>Maximum amount <strong data-budget-label><?= e($budgetLabel) ?></strong></span>
        <span class="budget-inputs">
          <input type="range" min="0" max="20000" step="500" value="<?= $budget ?>" data-budget-range aria-label="Maximum amount">
          <input type="number" name="budget" min="0" step="100" placeholder="Any amount" value="<?= $budget > 0 ? (int) $budget : '' ?>" data-budget-number>
        </span>
      </label>
      <label class="field">
        Sort
        <select name="sort">
          <option value="price"<?= selected($sort, 'price') ?>>Lowest amount</option>
          <option value="rating"<?= selected($sort, 'rating') ?>>Highest rating</option>
        </select>
      </label>
      <button class="button" type="submit">Show artists</button>
      <a class="button button-ghost" href="artists.php">Clear</a>
    </form>

    <div class="chips">
      <a class="chip<?= $budget === 1500 && $category === '' && $city === '' ? ' is-on' : '' ?>" href="artists.php?budget=1500">Under ₹1,500</a>
      <a class="chip<?= $budget === 3000 && $category === '' && $city === '' ? ' is-on' : '' ?>" href="artists.php?budget=3000">Under ₹3,000</a>
      <a class="chip<?= $category === 'Bridal' && $budget === 0 && $city === '' ? ' is-on' : '' ?>" href="artists.php?category=Bridal">Bridal designs</a>
      <a class="chip<?= $category === 'Arabic' && $budget === 3000 && $city === '' ? ' is-on' : '' ?>" href="artists.php?category=Arabic&amp;budget=3000">Arabic under ₹3,000</a>
      <a class="chip<?= $city === 'Jaipur' && $category === 'Bridal' ? ' is-on' : '' ?>" href="artists.php?city=Jaipur&amp;category=Bridal">Jaipur bridal</a>
    </div>

    <p class="results"><?= e($summary) ?></p>

    <?php if (!$groups): ?>
      <div class="empty">
        <h2>No artists match these requirements.</h2>
        <p>Try a higher amount, or switch to another design.</p>
        <p><a class="button" href="artists.php">Clear filters</a></p>
      </div>
    <?php else: ?>
      <div class="artist-list">
        <?php foreach ($groups as $group): ?>
          <?php $artist = $group['artist']; ?>
          <article class="artist-card">
            <header class="artist-card-head">
              <div class="identity">
                <span class="medallion" style="--accent: <?= e(accent($artist['accent'])) ?>"><?= e($artist['initials']) ?></span>
                <div>
                  <h2><a href="<?= e(append_query('artist.php?id=' . (int) $artist['artist_id'], ['category' => $category, 'budget' => $budget, 'city' => $city, 'sort' => $sort === 'rating' ? 'rating' : null])) ?>"><?= e($artist['name']) ?></a></h2>
                  <p class="meta"><?= e($artist['city']) ?> · <?= (int) $artist['experience_years'] ?> years · <?= e($artist['specialty']) ?></p>
                </div>
              </div>
              <p class="rating"><span aria-hidden="true">★</span> <?= e(number_format((float) $artist['rating'], 1)) ?></p>
            </header>
            <ul class="design-list">
              <?php foreach ($group['designs'] as $design): ?>
                <li class="design-row">
                  <div class="design-copy">
                    <strong><?= e($design['title']) ?></strong>
                    <span class="tag"><?= e($design['category']) ?></span>
                    <span> · <?= e(hours($design['duration_hours'])) ?></span>
                  </div>
                  <div class="design-actions">
                    <span class="price"><?= inr((int) $design['price']) ?></span>
                    <a class="button button-small" href="<?= e(book_link((int) $design['design_id'])) ?>">Select</a>
                  </div>
                </li>
              <?php endforeach; ?>
            </ul>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
