<?php
require_once 'config.php';

// --- Helper Functions for Telegram API ---

/**
 * Sends a text message to a chat.
 */
function sendTelegramMessage($chatId, $text, $token) {
    $data = [
        'chat_id' => $chatId,
        'text' => $text,
    ];
    return executeCurl("sendMessage", $data, $token);
}

/**
 * Forwards a message from one chat to another.
 */
function forwardTelegramMessage($chatId, $fromChatId, $messageId, $token) {
    $data = [
        'chat_id' => $chatId,
        'from_chat_id' => $fromChatId,
        'message_id' => $messageId,
    ];
    return executeCurl("forwardMessage", $data, $token);
}

/**
 * Executes a cURL request to the Telegram Bot API.
 */
function executeCurl($method, $data, $token) {
    $url = "https://api.telegram.org/bot" . $token . "/" . $method;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}


// --- Main Webhook Logic ---

// Get the bot ID from the webhook URL, e.g., webhook.php?bot_id=1
$bot_id = filter_input(INPUT_GET, 'bot_id', FILTER_VALIDATE_INT);
if (!$bot_id) {
    http_response_code(400);
    die("Bot ID is missing or invalid.");
}

// Fetch the bot's token and owner's chat ID from the database
$stmt = $conn->prepare("SELECT token, owner_chat_id FROM bots WHERE id = ?");
$stmt->bind_param("i", $bot_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    http_response_code(404);
    die("Bot not found.");
}
$bot = $result->fetch_assoc();
$bot_token = $bot['token'];
// Use the owner_chat_id from the database, or fall back to the one in config.php
$owner_chat_id = $bot['owner_chat_id'] ?: TELEGRAM_OWNER_ID; 


// Get the incoming update from Telegram
$update = json_decode(file_get_contents('php://input'), true);
if (!$update) {
    exit;
}

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
            sendTelegramMessage($buyer_chat_id, $reply_text, $bot_token);
        }
    } 
    // Scenario 2: A potential buyer is messaging the bot
    else {
        $buyer_chat_id = $chat_id;
        $message_id = $message['message_id'];

        // If it's the /start command, send a welcome message first
        if ($text === '/start') {
            $welcome_message = "Thanks for your interest in this username! Please leave your message here, and the owner will get back to you soon.";
            sendTelegramMessage($buyer_chat_id, $welcome_message, $bot_token);
        }

        // Forward the buyer's message to the owner for review
        forwardTelegramMessage($owner_chat_id, $buyer_chat_id, $message_id, $bot_token);
    }
}

$conn->close();
?>
