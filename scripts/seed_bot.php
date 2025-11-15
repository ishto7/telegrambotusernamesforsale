<?php
declare(strict_types=1);

require __DIR__ . '/../src/app.php';

[$config, $pdo] = bootstrap();

if ($argc < 2) {
    echo "Usage: php scripts/seed_bot.php <username> [status] [min_price]\n";
    exit(1);
}

[$script, $username, $status, $minPrice] = array_pad($argv, 4, null);
$username = ltrim($username, '@');
$status = $status ?: 'active';
$minPrice = $minPrice !== null ? (int)$minPrice : null;

$stmt = $pdo->prepare('INSERT INTO bots (username, status, min_price) VALUES (:u, :s, :m)');
try {
    $stmt->execute(['u' => $username, 's' => $status, 'm' => $minPrice]);
    echo "Added @$username\n";
} catch (Throwable $e) {
    echo "Failed to insert: " . $e->getMessage() . "\n";
    exit(1);
}
