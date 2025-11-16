<?php

/**
 * Sends a text message to a chat.
 */
function sendTelegramMessage($chatId, $text) {
    $data = [
        'chat_id' => $chatId,
        'text' => $text,
    ];
    return executeCurl("sendMessage", $data);
}

/**
 * Forwards a message from one chat to another.
 */
function forwardTelegramMessage($chatId, $fromChatId, $messageId) {
    $data = [
        'chat_id' => $chatId,
        'from_chat_id' => $fromChatId,
        'message_id' => $messageId,
    ];
    return executeCurl("forwardMessage", $data);
}

/**
 * Executes a cURL request to the Telegram Bot API.
 */
function executeCurl($method, $data) {
    $token = $_ENV['TELEGRAM_BOT_TOKEN'];
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
