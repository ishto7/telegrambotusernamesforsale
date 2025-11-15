<?php
declare(strict_types=1);

require __DIR__ . '/../src/app.php';

[$config, $pdo] = bootstrap();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

if ($path === '/health') {
    respondJson(200, ['status' => 'ok']);
    exit;
}

if ($path === '/webhook' && $method === 'POST') {
    handleWebhook($config, $pdo);
    exit;
}

if ($path === '/bids' && $method === 'POST') {
    handleBidSubmission($config, $pdo);
    exit;
}

// Static assets (very small footprint; add more as needed)
if ($path === '/style.css') {
    header('Content-Type: text/css');
    echo file_get_contents(__DIR__ . '/../templates/style.css');
    exit;
}

if ($path === '/' || $path === '/bots') {
    renderBotsPage($config, $pdo);
    exit;
}

if (preg_match('#^/bots/@?([A-Za-z0-9_]+)$#', $path, $matches)) {
    $username = $matches[1];
    renderBotDetailPage($config, $pdo, $username);
    exit;
}

http_response_code(404);
echo 'Not Found';

function handleWebhook(array $config, PDO $pdo): void
{
    $raw = file_get_contents('php://input');
    $update = json_decode($raw, true);
    if (!is_array($update)) {
        respondJson(400, ['error' => 'Invalid update']);
        return;
    }

    $message = $update['message'] ?? null;
    if (!$message) {
        respondJson(200, ['status' => 'ignored']);
        return;
    }

    $chatId = (string)($message['chat']['id'] ?? '');
    $fromUsername = $message['from']['username'] ?? '';
    $text = trim($message['text'] ?? '');
    $isOwner = !empty($config['owner_chat_id']) && (string)$config['owner_chat_id'] === $chatId;

    if ($text === '/start') {
        sendTelegramMessage($config, $chatId, $config['fixed_reply']);
        recordMessage($pdo, 'outbound', $chatId, null, $config['fixed_reply'], false);
        respondJson(200, ['status' => 'ok']);
        return;
    }

    recordMessage($pdo, 'inbound', $chatId, null, $text, $isOwner);

    if ($isOwner) {
        // Owner can forward using: reply <chat_id> message
        if (preg_match('/^reply\s+(\-?\d+)\s+(.+)/i', $text, $match)) {
            $targetChatId = $match[1];
            $ownerMsg = $match[2];
            sendTelegramMessage($config, $targetChatId, $ownerMsg);
            recordMessage($pdo, 'outbound', $targetChatId, null, $ownerMsg, true);
            sendTelegramMessage($config, $chatId, "Sent to $targetChatId");
        } else {
            sendTelegramMessage(
                $config,
                $chatId,
                "Use: reply <chat_id> <message> to respond to buyers."
            );
        }
    } else {
        // Acknowledge buyer and notify owner
        sendTelegramMessage($config, $chatId, $config['fixed_reply']);
        if (!empty($config['owner_chat_id'])) {
            $ownerText = "New message from @" . ($fromUsername ?: 'unknown') . " (chat $chatId):\n\n" . $text .
                "\n\nReply with: reply $chatId <message>";
            sendTelegramMessage($config, $config['owner_chat_id'], $ownerText);
            recordMessage($pdo, 'outbound', (string)$config['owner_chat_id'], null, $ownerText, true);
        }
    }

    respondJson(200, ['status' => 'ok']);
}

function handleBidSubmission(array $config, PDO $pdo): void
{
    $name = trim($_POST['name'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $amountRaw = trim($_POST['amount'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $botId = intval($_POST['bot_id'] ?? 0);

    $bot = $botId ? fetchBotById($pdo, $botId) : null;
    if (!$bot) {
        respondJson(400, ['error' => 'Bot not found']);
        return;
    }

    if ($name === '' || $contact === '') {
        respondJson(422, ['error' => 'Name and contact are required']);
        return;
    }

    $amount = $amountRaw !== '' ? intval($amountRaw) : null;
    insertBid($pdo, (int)$bot['id'], $name, $contact, $amount, $message ?: null);

    $payload = "New bid for @" . $bot['username'] . "\n"
        . "Name: $name\n"
        . "Contact: $contact\n"
        . ($amount ? "Offer: $amount\n" : '')
        . ($message ? "Message: $message\n" : '');

    if (!empty($config['owner_chat_id'])) {
        sendTelegramMessage($config, $config['owner_chat_id'], $payload);
        recordMessage($pdo, 'outbound', (string)$config['owner_chat_id'], $bot['username'], $payload, true);
    }

    // Fallback redirect for browsers
    if (!empty($_SERVER['HTTP_REFERER'])) {
        redirect($_SERVER['HTTP_REFERER']);
    }

    respondJson(200, ['status' => 'ok']);
}

function renderBotsPage(array $config, PDO $pdo): void
{
    $bots = fetchBots($pdo);
    include __DIR__ . '/../templates/bots.php';
}

function renderBotDetailPage(array $config, PDO $pdo, string $username): void
{
    $bot = fetchBotByUsername($pdo, $username);
    if (!$bot) {
        http_response_code(404);
        echo 'Bot not found';
        return;
    }

    $bids = fetchBidsForBot($pdo, (int)$bot['id']);
    include __DIR__ . '/../templates/bot_detail.php';
}
