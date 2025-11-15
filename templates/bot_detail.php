<?php /** @var array $bot */ ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@<?= sanitize($bot['username']); ?> - Bid</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>
<div class="container">
    <header>
        <h1>@<?= sanitize($bot['username']); ?></h1>
        <a href="/bots">← Back</a>
    </header>

    <div class="card">
        <div class="status <?= sanitize($bot['status']); ?>"><?= sanitize($bot['status']); ?></div>
        <?php if (!empty($bot['min_price'])): ?>
            <p>Minimum price: <?= sanitize($bot['min_price']); ?></p>
        <?php endif; ?>
        <?php if (!empty($bot['notes'])): ?>
            <p><?= nl2br(sanitize($bot['notes'])); ?></p>
        <?php endif; ?>
    </div>

    <div class="card">
        <h3>Submit your bid</h3>
        <form method="POST" action="/bids">
            <input type="hidden" name="bot_id" value="<?= sanitize($bot['id']); ?>">
            <label>
                Name
                <input type="text" name="name" required>
            </label>
            <label>
                Contact (telegram handle, email, etc.)
                <input type="text" name="contact" required>
            </label>
            <label>
                Offer amount (optional)
                <input type="number" name="amount" min="0" step="1">
            </label>
            <label>
                Message (optional)
                <textarea name="message" rows="3"></textarea>
            </label>
            <button type="submit">Send bid</button>
        </form>
    </div>

    <?php if (!empty($bids)): ?>
        <div class="card">
            <h3>Recent bids</h3>
            <?php foreach ($bids as $bid): ?>
                <div style="border-bottom:1px solid #e2e8f0; padding:8px 0;">
                    <strong><?= sanitize($bid['bidder_name']); ?></strong>
                    <div><small><?= sanitize($bid['contact']); ?></small></div>
                    <?php if (!empty($bid['amount'])): ?>
                        <div>Offer: <?= sanitize($bid['amount']); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($bid['message'])): ?>
                        <div><?= nl2br(sanitize($bid['message'])); ?></div>
                    <?php endif; ?>
                    <div><small><?= sanitize($bid['created_at']); ?></small></div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <footer>
        <small>Bids are relayed to the owner on Telegram.</small>
    </footer>
</div>
</body>
</html>
