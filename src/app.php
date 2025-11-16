<?php
declare(strict_types=1);

/**
 * Lightweight helpers for routing, persistence, and Telegram messaging.
 */

if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool
    {
        return $needle === '' || strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}

if (!function_exists('str_contains')) {
    function str_contains(string $haystack, string $needle): bool
    {
        return $needle === '' || strpos($haystack, $needle) !== false;
    }
}

function loadEnvFile(string $path): void
{
    if (!is_file($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if ($key === '') {
            continue;
        }
        // Do not override existing env values.
        if (getenv($key) === false) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

function bootstrap(): array
{
    loadEnvFile(__DIR__ . '/../.env');

    $config = [
        'db' => [
            'host' => getenv('DB_HOST') ?: '127.0.0.1',
            'port' => getenv('DB_PORT') ?: '3306',
            'database' => getenv('DB_DATABASE') ?: '',
            'username' => getenv('DB_USERNAME') ?: '',
            'password' => getenv('DB_PASSWORD') ?: '',
            'charset' => getenv('DB_CHARSET') ?: 'utf8mb4',
            'collation' => getenv('DB_COLLATION') ?: 'utf8mb4_unicode_ci',
        ],
        'telegram_bot_token' => getenv('TELEGRAM_BOT_TOKEN') ?: '',
        'owner_chat_id' => getenv('OWNER_CHAT_ID') ?: '',
        'fixed_reply' => getenv('FIXED_REPLY') ?: "Thanks for reaching out. The owner will see your message and respond here.",
        'app_url' => getenv('APP_URL') ?: '',
    ];

    $pdo = initDatabase($config['db']);

    return [$config, $pdo];
}

function initDatabase(array $dbConfig): PDO
{
    if (empty($dbConfig['database'])) {
        throw new RuntimeException('DB_DATABASE is required');
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        $dbConfig['host'] ?: '127.0.0.1',
        $dbConfig['port'] ?: '3306',
        $dbConfig['database'],
        $dbConfig['charset'] ?: 'utf8mb4'
    );

    $pdo = new PDO(
        $dsn,
        $dbConfig['username'] ?? '',
        $dbConfig['password'] ?? '',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    $charset = $dbConfig['charset'] ?: 'utf8mb4';
    $collation = $dbConfig['collation'] ?: 'utf8mb4_unicode_ci';

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS bots (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(255) UNIQUE NOT NULL,
            status VARCHAR(50) NOT NULL DEFAULT 'active',
            min_price INT NULL,
            notes TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE {$collation}"
    );
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS bids (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            bot_id INT UNSIGNED NOT NULL,
            bidder_name VARCHAR(255) NOT NULL,
            contact VARCHAR(255) NOT NULL,
            amount INT NULL,
            message TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_bids_bots FOREIGN KEY(bot_id) REFERENCES bots(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE {$collation}"
    );
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS messages (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            direction VARCHAR(10) NOT NULL,
            chat_id VARCHAR(255) NOT NULL,
            bot_username VARCHAR(255) NULL,
            text TEXT NOT NULL,
            is_owner TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE {$collation}"
    );

    return $pdo;
}

function sanitize(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function fetchBots(PDO $pdo): array
{
    $stmt = $pdo->query('SELECT * FROM bots ORDER BY status ASC, username ASC');
    return $stmt->fetchAll() ?: [];
}

function fetchBotByUsername(PDO $pdo, string $username): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM bots WHERE username = :username');
    $stmt->execute(['username' => ltrim($username, '@')]);
    $bot = $stmt->fetch(PDO::FETCH_ASSOC);
    return $bot ?: null;
}

function fetchBotById(PDO $pdo, int $botId): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM bots WHERE id = :id');
    $stmt->execute(['id' => $botId]);
    $bot = $stmt->fetch(PDO::FETCH_ASSOC);
    return $bot ?: null;
}

function insertBid(PDO $pdo, int $botId, string $name, string $contact, ?int $amount, ?string $message): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO bids (bot_id, bidder_name, contact, amount, message) VALUES (:bot_id, :name, :contact, :amount, :message)'
    );
    $stmt->execute([
        'bot_id' => $botId,
        'name' => $name,
        'contact' => $contact,
        'amount' => $amount,
        'message' => $message,
    ]);
}

function fetchBidsForBot(PDO $pdo, int $botId): array
{
    $stmt = $pdo->prepare('SELECT * FROM bids WHERE bot_id = :bot_id ORDER BY created_at DESC');
    $stmt->execute(['bot_id' => $botId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function recordMessage(PDO $pdo, string $direction, string $chatId, ?string $botUsername, string $text, bool $isOwner): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO messages (direction, chat_id, bot_username, text, is_owner) VALUES (:direction, :chat_id, :bot_username, :text, :is_owner)'
    );
    $stmt->execute([
        'direction' => $direction,
        'chat_id' => $chatId,
        'bot_username' => $botUsername,
        'text' => $text,
        'is_owner' => $isOwner ? 1 : 0,
    ]);
}

function sendTelegramMessage(array $config, string $chatId, string $text): void
{
    if (empty($config['telegram_bot_token'])) {
        return;
    }

    $payload = [
        'chat_id' => $chatId,
        'text' => $text,
        'parse_mode' => 'Markdown',
    ];
    $url = 'https://api.telegram.org/bot' . $config['telegram_bot_token'] . '/sendMessage';

    $options = [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 10,
    ];

    $ch = curl_init();
    curl_setopt_array($ch, $options);
    curl_exec($ch);
    curl_close($ch);
}

function respondJson(int $status, array $payload): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($payload);
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    http_response_code(302);
    exit;
}
