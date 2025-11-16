# Telegram Bot Usernames For Sale (PHP)

Lightweight PHP project that:
- Hosts a website listing bot usernames with a bid form per bot.
- Receives Telegram bot webhooks, replies with a fixed message, forwards buyer messages to the owner, and lets the owner reply back.
- Sends bids from the website to the owner on Telegram.

## Prerequisites
- PHP 8.0+ with MySQL PDO extension and cURL enabled.
- A MySQL database created for this app (tables are created automatically).

## Setup
1) Copy environment file:
```bash
cp .env.example .env
```
Fill in:
- `TELEGRAM_BOT_TOKEN`: Bot token from BotFather.
- `OWNER_CHAT_ID`: Your Telegram chat id to receive notifications and send replies.
- `APP_URL`: Public URL serving this app (e.g., `https://yourdomain.com`).
- `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`: MySQL connection.
- (Optional) `DB_CHARSET`, `DB_COLLATION`: MySQL charset/collation (defaults to utf8mb4).
- `FIXED_REPLY`: Message buyers see automatically.

2) Install dependencies (none beyond PHP) and ensure the MySQL database exists; tables are built automatically on first run.

3) Seed at least one bot:
```bash
php scripts/seed_bot.php coolbot active 1500
```

4) Run locally:
```bash
php -S 0.0.0.0:8000 -t public
```
Visit http://localhost:8000 to view bots and submit bids.

## Telegram Webhook
Set the webhook after the app is reachable via HTTPS:
```bash
curl -X POST "https://api.telegram.org/bot${TELEGRAM_BOT_TOKEN}/setWebhook" \
  -d "url=${APP_URL}/webhook"
```

Buyer flow:
- Any message → bot replies with `FIXED_REPLY`, stores it, and forwards to `OWNER_CHAT_ID`.

Owner flow:
- Send `reply <chat_id> <message>` to the bot to forward a message to that buyer.

## Endpoints
- `GET /bots` or `/`: List bots.
- `GET /bots/{username}`: Bot detail + bid form.
- `POST /bids`: Form submissions. Fields: `bot_id`, `name`, `contact`, `amount?`, `message?`.
- `POST /webhook`: Telegram webhook (expects JSON update).
- `GET /health`: Health check.

## Notes
- Tables are created on first request inside your configured MySQL database.
- Messages and bids are logged for transparency.
- Add simple auth/admin as needed before exposing management actions.
