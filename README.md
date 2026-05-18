# AI Chatbot SaaS Platform

Deploy AI-powered chatbots on any website in minutes. Built on PHP + MySQL + Stripe + Anthropic Claude.

## Stack

| Layer    | Technology                        |
|----------|-----------------------------------|
| Backend  | PHP 8.1+                          |
| Database | MySQL 8.0+                        |
| AI       | Anthropic Claude (claude-sonnet-4-6) |
| Payments | Stripe (Subscriptions + Webhooks) |
| Frontend | Vanilla JS + CSS (no framework)   |

## Project structure

```
/public_html   Landing page, auth pages, embed widget (widget.js)
/api           REST API endpoints (router in index.php)
/admin         Client dashboard (sidebar layout)
/includes      config.php · db.php · helpers.php · auth.php
/database      schema.sql
/cron          Daily stats rollup
```

## Quick start

```bash
# 1. Clone & configure
cp .env.example .env
# Edit .env — set DB credentials, ANTHROPIC_API_KEY, Stripe keys

# 2. Create database
mysql -u root -p -e "CREATE DATABASE ai_chatbot CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p ai_chatbot < database/schema.sql

# 3. Point web server document root to /public_html
#    Enable mod_rewrite (Apache) or configure nginx rewrite rules

# 4. Register Stripe webhook endpoint:
#    https://yourdomain.com/api/billing/webhook
#    Events: customer.subscription.*
```

## Embed widget

Paste one line before `</body>`:

```html
<script src="https://yourdomain.com/widget.js" data-bot="YOUR_CHATBOT_UUID"></script>
```

## Subscription plans

| Plan       | Price  | Chatbots | Messages/mo |
|------------|--------|----------|-------------|
| Starter    | $9/mo  | 1        | 500         |
| Pro        | $29/mo | 5        | 5,000       |
| Business   | $79/mo | 20       | 25,000      |
| Enterprise | Custom | ∞        | ∞           |

All plans include a **14-day free trial**.

## API endpoints

| Method | Path                              | Auth     | Description           |
|--------|-----------------------------------|----------|-----------------------|
| POST   | /api/auth/register                | —        | Register              |
| POST   | /api/auth/login                   | —        | Login                 |
| GET    | /api/chatbots                     | Session  | List chatbots         |
| POST   | /api/chatbots                     | Session  | Create chatbot        |
| PUT    | /api/chatbots/:uuid               | Session  | Update chatbot        |
| DELETE | /api/chatbots/:uuid               | Session  | Delete chatbot        |
| POST   | /api/chat/:uuid/message           | Public   | Send message          |
| GET    | /api/chat/:uuid/history           | Public   | Get chat history      |
| GET    | /api/stats                        | Session  | Usage statistics      |
| POST   | /api/billing/checkout             | Session  | Stripe checkout       |
| POST   | /api/billing/portal               | Session  | Stripe billing portal |
| POST   | /api/billing/webhook              | Stripe   | Webhook handler       |
