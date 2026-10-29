<?php
declare(strict_types=1);

require __DIR__ . '/config.php';

$messages = [];
$ok = false;

try {
    $server = new PDO(
        'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $server->exec(
        'CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
    );

    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );

    $schema = file_get_contents(__DIR__ . '/sql/schema.sql');
    if ($schema === false) {
        throw new RuntimeException('Could not read sql/schema.sql.');
    }
    $schema = preg_replace('/--.*$/m', '', $schema) ?? $schema;
    foreach (array_filter(array_map('trim', explode(';', $schema))) as $statement) {
        $pdo->exec($statement);
    }

    $artistCount = (int) $pdo->query('SELECT COUNT(*) FROM artists')->fetchColumn();
    if ($artistCount === 0) {
        seed_artists($pdo);
        $messages[] = 'Loaded 8 sample mehandi artists and their designs.';
    } else {
        $messages[] = 'Artists are already in the database, so the sample list was left as it is.';
    }

    $email = 'demo@mehandi.test';
    $existing = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $existing->execute([$email]);
    if (!$existing->fetch()) {
        $insertUser = $pdo->prepare(
            'INSERT INTO users (name, email, password_hash, phone) VALUES (?, ?, ?, ?)'
        );
        $insertUser->execute([
            'Demo Guest',
            $email,
            password_hash('demo123', PASSWORD_DEFAULT),
            '+91 90000 00000',
        ]);
        $messages[] = 'Created the sample login demo@mehandi.test / demo123.';
    } else {
        $messages[] = 'Sample login is ready: demo@mehandi.test / demo123.';
    }

    $ok = true;
    array_unshift($messages, 'Database `' . DB_NAME . '` is ready.');
} catch (Throwable $e) {
    $messages[] = 'Setup could not finish. Start MySQL in XAMPP, then check the user and password in config.php.';
    $messages[] = $e->getMessage();
}

if (PHP_SAPI === 'cli') {
    foreach ($messages as $message) {
        echo $message . PHP_EOL;
    }
    exit($ok ? 0 : 1);
}

function seed_artists(PDO $pdo): void
{
    $artists = [
        [
            'Aisha Qureshi', 'Jaipur', 9, 'Bridal and traditional',
            'Aisha specialises in dense Rajasthani bridal work for weddings in Jaipur. Each design has its own price, so you can match the pattern to your budget.',
            4.8, '+91 98290 11001', 'AQ', '#8c3d2f',
            [
                ['Rajwada Bridal', 'Bridal', 12000, 6.0, 'Full hands and feet with peacock and lattice work for the wedding day.'],
                ['Peacock Palm', 'Traditional', 2500, 1.5, 'A single-palm traditional piece with a peacock at the centre.'],
                ['Karwa Chauth Moon', 'Festive', 1800, 1.0, 'Moon and floral work sized for a festival evening.'],
            ],
        ],
        [
            'Meera Desai', 'Ahmedabad', 6, 'Arabic and modern',
            'Meera keeps the line light and the shading soft, which suits Arabic trails and small modern sets. Her shorter designs sit at the lower end of the price list.',
            4.6, '+91 98790 22012', 'MD', '#3f5c45',
            [
                ['Gulf Arabic Trailing', 'Arabic', 2200, 1.5, 'Trailing Arabic vines from wrist to mid-arm.'],
                ['Minimal Wrist Band', 'Minimal', 900, 0.5, 'A fine bracelet band for a small gathering.'],
                ['Engagement Arabic Set', 'Arabic', 4500, 3.0, 'Both hands in matching Arabic work for an engagement.'],
            ],
        ],
        [
            'Fatima Begum', 'Hyderabad', 12, 'Traditional bridal',
            'Fatima has twelve years of wedding bookings across Hyderabad. She offers a full bridal package and smaller traditional pieces for guests.',
            4.9, '+91 98490 33023', 'FB', '#7a4e2d',
            [
                ['Nizam Bridal', 'Bridal', 15000, 7.0, 'Heavy bridal coverage for both hands and feet.'],
                ['Bangle Stack', 'Traditional', 2000, 1.5, 'Stacked bangle motifs around the wrist and palm edge.'],
                ['Eid Chand Flower', 'Festive', 1500, 1.0, 'Crescent and jasmine sprigs for Eid.'],
            ],
        ],
        [
            'Riya Malhotra', 'Delhi', 8, 'Portrait mehandi',
            'Riya draws portrait mehandi and detailed bridal work for Delhi functions. Portrait pieces take longer, so the amount is higher than a standard palm.',
            4.7, '+91 98100 44034', 'RM', '#6b3a4a',
            [
                ['Custom Portrait Palm', 'Portrait', 6000, 4.0, 'One portrait with a floral frame on the palm.'],
                ['Bridal Portrait Package', 'Bridal', 18000, 8.0, 'Bridal coverage that includes a portrait on each palm.'],
                ['Initial on the Wrist', 'Minimal', 1200, 0.75, 'A small initial with a thin leafy border.'],
            ],
        ],
        [
            'Zara Siddiqui', 'Lucknow', 7, 'Indo-Arabic',
            'Zara mixes Lucknowi flowers with Arabic shading. The same hand can be booked as a party design or a full bridal set.',
            4.5, '+91 98390 55045', 'ZS', '#2f4f5c',
            [
                ['Lucknowi Indo-Arabic', 'Arabic', 2800, 2.0, 'Shaded leaves with a Lucknowi flower centre.'],
                ['Festival Both Hands', 'Festive', 3200, 2.5, 'Matching festive work on both hands.'],
                ['Bridal Indo-Arabic', 'Bridal', 9500, 5.0, 'Bridal hands and feet in an Indo-Arabic layout.'],
            ],
        ],
        [
            'Ananya Iyer', 'Mumbai', 5, 'Fine-line minimal',
            'Ananya works in fine lines for Mumbai receptions and small ceremonies. Start here if the amount you want to spend is under two thousand.',
            4.4, '+91 98200 66056', 'AI', '#8a6232',
            [
                ['Fine-line Bracelet', 'Minimal', 700, 0.5, 'A thin bracelet that stops at the wrist.'],
                ['Negative-space Palm', 'Minimal', 1600, 1.0, 'Open palm with a few precise leaves.'],
                ['Reception Minimal Bridal', 'Bridal', 5500, 3.5, 'A lighter bridal set for the reception, not the main ceremony.'],
            ],
        ],
        [
            'Noor Jahan', 'Jaipur', 11, 'Bridal',
            'Noor is a second Jaipur bridal artist, so you can compare her prices with Aisha for the same kind of wedding work.',
            4.8, '+91 98290 77067', 'NJ', '#5c3d2e',
            [
                ['Sheesh Mahal Bridal', 'Bridal', 14000, 6.5, 'Palace-style bridal work with dense fill and a wide border.'],
                ['Rose Traditional Palm', 'Traditional', 2300, 1.5, 'Rose trail across one palm and wrist.'],
                ['Sangeet Arabic', 'Arabic', 3500, 2.0, 'Bold Arabic trails meant for a sangeet night.'],
            ],
        ],
        [
            'Kavya Nair', 'Kochi', 4, 'Festive and traditional',
            'Kavya books festival mornings and smaller weddings in Kochi. Her festive designs are priced for guests who want one hand done.',
            4.3, '+91 98470 88078', 'KN', '#3e5348',
            [
                ['Onam Floral', 'Festive', 1400, 1.0, 'Pookalam-inspired flowers on the back of one hand.'],
                ['Temple Border', 'Traditional', 2100, 1.5, 'A temple-border pattern from fingertip to wrist.'],
                ['Simple Bridal Hands', 'Bridal', 7000, 4.0, 'Both hands in a simpler bridal pattern, without feet.'],
            ],
        ],
    ];

    $artistStmt = $pdo->prepare(
        'INSERT INTO artists (name, city, experience_years, specialty, bio, rating, phone, initials, accent)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $designStmt = $pdo->prepare(
        'INSERT INTO designs (artist_id, title, category, price, duration_hours, description)
         VALUES (?, ?, ?, ?, ?, ?)'
    );

    $pdo->beginTransaction();
    try {
        foreach ($artists as $artist) {
            $artistStmt->execute([
                $artist[0], $artist[1], $artist[2], $artist[3], $artist[4],
                $artist[5], $artist[6], $artist[7], $artist[8],
            ]);
            $artistId = (int) $pdo->lastInsertId();
            foreach ($artist[9] as $design) {
                $designStmt->execute([
                    $artistId, $design[0], $design[1], $design[2], $design[3], $design[4],
                ]);
            }
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Setup · <?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?></title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <main class="auth-shell">
    <section class="auth-card">
      <p class="eyebrow">Setup</p>
      <h1><?= $ok ? 'Mehandi Design is ready.' : 'Setup did not finish.' ?></h1>
      <?php foreach ($messages as $message): ?>
        <p><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
      <?php endforeach; ?>
      <?php if ($ok): ?>
        <p><a class="button" href="index.php">Open the app</a></p>
      <?php endif; ?>
    </section>
  </main>
</body>
</html>
