<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Telegram Bot Username Marketplace</title>
    <style>
        body { font-family: sans-serif; }
        .container { max-width: 800px; margin: 0 auto; padding: 20px; }
        .bot-list { list-style: none; padding: 0; }
        .bot-item { margin-bottom: 20px; padding: 15px; border: 1px solid #ccc; border-radius: 5px; }
        .bot-item h2 { margin-top: 0; }
        .bid-form input { display: block; margin-bottom: 10px; padding: 8px; width: 100%; }
    </style>
</head>
<body>

    <div class="container">
        <h1>Available Telegram Bot Usernames</h1>
        <ul class="bot-list">
            <?php
            require_once 'config.php';
            $result = $conn->query("SELECT id, username FROM bots ORDER BY username ASC");
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
            ?>
                <li class="bot-item">
                    <h2>@<?php echo htmlspecialchars($row['username']); ?></h2>
                    <form action="submit_bid.php" method="post" class="bid-form">
                        <input type="hidden" name="bot_id" value="<?php echo $row['id']; ?>">
                        <input type="number" name="bid_amount" placeholder="Your Bid Amount (USD)" required>
                        <input type="text" name="bidder_telegram_username" placeholder="Your Telegram Username (e.g. @username)" required>
                        <button type="submit">Submit Bid</button>
                    </form>
                </li>
            <?php
                }
            } else {
                echo "<p>No bot usernames are currently available for sale.</p>";
            }
            $conn->close();
            ?>
        </ul>
    </div>

</body>
</html>
