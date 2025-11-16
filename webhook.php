<?php
require_once 'config.php';
require_once 'telegram_helpers.php';

// --- Logging ---
// Basic logging to a file for debugging purposes.
$log_file = 'webhook_log.txt';
function log_message($message) {
    global $log_file;
    file_put_contents($log_file, date('Y-m-d H:i:s') . ' - ' . $message . "\n", FILE_APPEND);
}

// Get the incoming update from Telegram
$update = json_decode(file_get_contents('php://input'), true);
if (!$update) {
    exit;
}

// Log the entire update
log_message("Received update: " . json_encode($update, JSON_PRETTY_PRINT));


// --- Main Webhook Logic ---

// Get the bot ID from the webhook URL, e.g., webhook.php?bot_id=1
$bot_id = filter_input(INPUT_GET, 'bot_id', FILTER_VALIDATE_INT);
if (!$bot_id) {
    http_response_code(400);
    $error_message = "Bot ID is missing or invalid.";
    log_message($error_message);
    die($error_message);
}

// Fetch the bot's owner's chat ID from the database
$stmt = $conn->prepare("SELECT owner_chat_id FROM bots WHERE id = ?");
$stmt->bind_param("i", $bot_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    http_response_code(404);
    $error_message = "Bot not found for ID: " . $bot_id;
    log_message($error_message);
    die($error_message);
}
$bot = $result->fetch_assoc();
// Use the owner_chat_id from the database, or fall back to the one in config.php
$owner_chat_id = $bot['owner_chat_id'] ?: TELEGRAM_OWNER_ID; 


// Check for a message
if (isset($update['message'])) {
    $message = $update['message'];
    $chat_id = $message['chat']['id'];
    $text = isset($message['text']) ? $message['text'] : '';

    // Scenario 1: The owner is replying to a forwarded message
    if (isset($message['reply_to_message']) && $chat_id == $owner_chat_id) {
        $original_message = $message['reply_to_message'];
        
        // Check if it's a reply to a forwarded message
        if (isset($original_message['forward_from'])) {
            $buyer_chat_id = $original_message['forward_from']['id'];
            $reply_text = $text;

            // Send the owner's reply back to the buyer
            log_message("Replying to buyer ($buyer_chat_id): $reply_text");
            sendTelegramMessage($buyer_chat_id, $reply_text);
        }
    } 
    // Scenario 2: A potential buyer is messaging the bot
    else {
        $buyer_chat_id = $chat_id;
        $message_id = $message['message_id'];

        // If it's the /start command, send a welcome message first
        if ($text === '/start') {
            $welcome_message = "Thanks for your interest in this username! Please leave your message here, and the owner will get back to you soon.";
            log_message("Sending welcome message to: $buyer_chat_id");
            sendTelegramMessage($buyer_chat_id, $welcome_message);
        }

        // Forward the buyer's message to the owner for review
        log_message("Forwarding message from $buyer_chat_id to owner $owner_chat_id");
        forwardTelegramMessage($owner_chat_id, $buyer_chat_id, $message_id);
    }
}

$conn->close();
?>
