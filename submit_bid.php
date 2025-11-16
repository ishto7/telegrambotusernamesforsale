<?php
require_once 'config.php';
require_once 'telegram_helpers.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $bot_id = filter_input(INPUT_POST, 'bot_id', FILTER_VALIDATE_INT);
    $bid_amount = filter_input(INPUT_POST, 'bid_amount', FILTER_VALIDATE_FLOAT);
    $bidder_telegram_username = filter_input(INPUT_POST, 'bidder_telegram_username', FILTER_SANITIZE_STRING);

    if ($bot_id && $bid_amount && $bidder_telegram_username) {
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $user_agent = $_SERVER['HTTP_USER_AGENT'];

        // Prepare and bind
        $stmt = $conn->prepare("INSERT INTO bids (bot_id, bidder_telegram_username, bid_amount, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isdss", $bot_id, $bidder_telegram_username, $bid_amount, $ip_address, $user_agent);

        if ($stmt->execute()) {
            // Get bot username for notification
            $bot_result = $conn->query("SELECT username FROM bots WHERE id = " . $bot_id);
            $bot_username = $bot_result->fetch_assoc()['username'];

            // Prepare notification message
            $message = "New Bid Notification!\n\n" .
                       "Bot Username: @" . $bot_username . "\n" .
                       "Bid Amount: $" . $bid_amount . "\n" .
                       "Bidder's Telegram: " . $bidder_telegram_username . "\n\n" .
                       "Bidder's Info:\n" .
                       "IP Address: " . $ip_address . "\n" .
                       "User Agent: " . $user_agent;
            
            // Send notification to the project owner
            sendTelegramMessage(TELEGRAM_OWNER_ID, $message);

            echo "<h1>Bid Submitted Successfully!</h1>";
            echo "<p>Thank you for your bid. The owner has been notified.</p>";
            echo '<a href="index.php">Back to Marketplace</a>';

        } else {
            echo "<h1>Error</h1>";
            echo "<p>Something went wrong. Please try again.</p>";
        }
        $stmt->close();
    } else {
        echo "<h1>Invalid Input</h1>";
        echo "<p>Please fill out all fields correctly.</p>";
    }
    $conn->close();
}
?>
