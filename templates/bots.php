<?php /** @var array $bots */ ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Telegram Bot Usernames</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>
<div class="container">
    <header>
        <h1>Telegram Bot Usernames for Sale</h1>
        <a href="/">Home</a>
    </header>

    <?php if (empty($bots)): ?>
        <p>No bots listed yet.</p>
    <?php endif; ?>

    <?php foreach ($bots as $bot): ?>
        <div class="card">
            <div style="display:flex; justify-content: space-between; align-items:center;">
                <div>
                    <h3>@<?= sanitize($bot['username']); ?></h3>
                    <div class="status <?= sanitize($bot['status']); ?>"><?= sanitize($bot['status']); ?></div>
                    <?php if (!empty($bot['min_price'])): ?>
                        <div>Min price: <?= sanitize($bot['min_price']); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($bot['notes'])): ?>
                        <p><?= nl2br(sanitize($bot['notes'])); ?></p>
                    <?php endif; ?>
                </div>
                <div>
                    <a href="/bots/<?= sanitize($bot['username']); ?>">View &amp; bid →</a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <footer>
        <small>Bids are forwarded to the owner via Telegram.</small>
    </footer>
</div>
</body>
</html>
