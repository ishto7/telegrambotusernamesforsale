# Telegram Bot Username Marketplace

This project allows you to host a simple website to showcase and sell your collection of Telegram bot usernames. It includes a bidding system and a message relay to connect you with potential buyers.

## Setup Instructions

### 1. Database Setup

1.  Create a new MySQL database for this project.
2.  Import the `database.sql` file to create the necessary `bots` and `bids` tables.

### 2. Project Configuration

1.  Open the `config.php` file and fill in your database connection details:
    *   `DB_HOST`: Your database host (usually `localhost`).
    *   `DB_USERNAME`: Your database username.
    *   `DB_PASSWORD`: Your database password.
    *   `DB_NAME`: The name of the database you created in the previous step.
2.  You also need to set your personal Telegram User ID in `TELEGRAM_OWNER_ID`. You can get your User ID by messaging the `@userinfobot` on Telegram.

### 3. Adding Your Bots

For each bot username you want to list on your marketplace, you need to add a new row to the `bots` table in your database.

*   `username`: The bot's username (without the `@`).
*   `token`: The API token for the bot, which you can get from `@BotFather` on Telegram.
*   `owner_chat_id`: (Optional) If you want messages for a specific bot to go to a different Telegram chat, you can enter that chat's ID here. Otherwise, it will default to the `TELEGRAM_OWNER_ID` from your `config.php`.

### 4. Setting Up the Webhooks

For the message relay system to work, you need to tell Telegram where to send incoming messages for each of your bots. You do this by setting up a webhook.

For each bot you've added to the database, you need to run the following command by visiting the URL in your browser. Make sure to replace the placeholders:

`https://api.telegram.org/bot<YOUR_BOT_TOKEN>/setWebhook?url=https://<YOUR_WEBSITE_DOMAIN>/webhook.php?bot_id=<BOT_ID_FROM_DATABASE>`

*   `<YOUR_BOT_TOKEN>`: The API token of the bot you are setting the webhook for.
*   `<YOUR_WEBSITE_DOMAIN>`: The domain where you have uploaded these project files.
*   `<BOT_ID_FROM_DATABASE>`: The `id` of the bot from the `bots` table in your database.

### 5. Notification Bot for Bids

When a user submits a bid on the website, a notification is sent to you on Telegram. This is handled by the `submit_bid.php` script.

*   In the `submit_bid.php` file, you will find a placeholder `YOUR_NOTIFICATION_BOT_TOKEN`.
*   It is recommended to create a separate bot for sending these notifications. Once you have its API token, replace the placeholder with the actual token.

---

Once you have completed these steps, your marketplace should be fully functional. Visitors can view your listed bot usernames, submit bids, and contact you directly by messaging your bots.
