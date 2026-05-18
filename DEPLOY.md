# Deployment Guide — AI Chatbot SaaS on Hostinger

## Requirements

| Requirement | Minimum |
|---|---|
| PHP | 8.1+ (8.2 recommended) |
| MySQL | 8.0+ |
| Apache | 2.4+ with mod_rewrite |
| PHP extensions | `pdo_mysql`, `curl`, `mbstring`, `json`, `openssl` |

---

## 1. Upload files to Hostinger

### Option A — Git clone (recommended, requires SSH)

```bash
# SSH into your Hostinger server
ssh u123456789@your-server.hostinger.com

# Navigate to account home
cd ~

# Clone the repo
git clone https://github.com/your-username/ai-chatbot-saas.git ai-chatbot-saas
```

### Option B — FTP / File Manager

1. Open **hPanel → File Manager** (or use FTP client, e.g. FileZilla)
2. Navigate to your account home (`/home/u{id}/`)
3. Create a folder called `ai-chatbot-saas`
4. Upload **all project files** into that folder

The resulting structure on the server must be:

```
/home/u{id}/ai-chatbot-saas/
├── api/
├── admin/
├── database/
├── includes/
├── cron/
├── logs/              ← create this empty directory
├── public_html/       ← this becomes the web root
│   ├── .htaccess
│   ├── index.php
│   ├── login.php
│   ├── widget.js
│   └── ...
├── .env               ← create from .env.example
├── .htaccess
└── ...
```

---

## 2. Set document root in hPanel

1. Go to **hPanel → Websites → Manage** (or **Domains → Manage**)
2. Click **File Manager settings** or **Advanced → Document Root**
3. Set the document root to:  
   ```
   /home/u{id}/ai-chatbot-saas/public_html
   ```
4. Save changes — Apache will now serve your site from `public_html/`

> **Why?** Sensitive directories (`includes/`, `api/`, `database/`, `.env`) live _above_ the web root and are never directly accessible by visitors.

---

## 3. Create the MySQL database

1. Open **hPanel → Databases → MySQL Databases**
2. Create a new database, e.g. `u123456789_aichat`
3. Create a database user and a strong password
4. Grant the user **All Privileges** on the database
5. Import the schema:
   - Go to **hPanel → phpMyAdmin**
   - Select your new database
   - Click **Import** → choose `database/schema.sql`
   - Click **Go**

---

## 4. Create and configure `.env`

On the server, copy the example file:

```bash
cp /home/u{id}/ai-chatbot-saas/.env.example /home/u{id}/ai-chatbot-saas/.env
```

Edit `.env` with your real values:

```dotenv
# ── App ───────────────────────────────────────────────────────────────────────
APP_NAME="AI Chatbot Platform"
APP_URL=https://yourdomain.com          # NO trailing slash, must be HTTPS
APP_ENV=production
APP_DEBUG=false
APP_KEY=<run: php -r "echo bin2hex(random_bytes(32));"> # 64-char random hex

# ── Database ──────────────────────────────────────────────────────────────────
DB_HOST=localhost
DB_PORT=3306
DB_NAME=u123456789_aichat              # database name from step 3
DB_USER=u123456789_aichatuser          # database user from step 3
DB_PASS=your_strong_password

# ── Anthropic / Claude ────────────────────────────────────────────────────────
ANTHROPIC_API_KEY=sk-ant-api03-...     # https://console.anthropic.com/settings/keys

# ── Stripe — use LIVE keys for production ─────────────────────────────────────
STRIPE_PUBLIC_KEY=pk_live_...          # Stripe Dashboard → Developers → API keys
STRIPE_SECRET_KEY=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...        # generated in step 6 below

# ── Stripe Price IDs (monthly recurring prices you created in Stripe) ─────────
STRIPE_PRICE_STARTER=price_...         # $9/mo
STRIPE_PRICE_PRO=price_...             # $29/mo
STRIPE_PRICE_BUSINESS=price_...        # $79/mo
STRIPE_PRICE_AGENCY=price_...          # $149/mo
STRIPE_PRICE_ENTERPRISE=price_...      # custom — leave empty if not used

# ── Mail (Hostinger SMTP or any SMTP provider) ─────────────────────────────────
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=587
MAIL_USERNAME=noreply@yourdomain.com
MAIL_PASSWORD=your_email_password
MAIL_FROM=noreply@yourdomain.com
MAIL_FROM_NAME="AI Chatbot Platform"
```

**Protect the file** (chmod via SSH or File Manager):

```bash
chmod 600 /home/u{id}/ai-chatbot-saas/.env
```

---

## 5. Create the logs directory

```bash
mkdir -p /home/u{id}/ai-chatbot-saas/logs
chmod 755 /home/u{id}/ai-chatbot-saas/logs
```

---

## 6. Configure Stripe Webhook

1. Open [Stripe Dashboard → Developers → Webhooks](https://dashboard.stripe.com/webhooks)
2. Click **Add endpoint**
3. Endpoint URL:
   ```
   https://yourdomain.com/api/billing/webhook
   ```
4. Select these events to listen for:
   - `customer.subscription.created`
   - `customer.subscription.updated`
   - `customer.subscription.deleted`
   - `invoice.payment_succeeded`
   - `invoice.payment_failed`
5. Click **Add endpoint**
6. On the webhook detail page, click **Reveal signing secret** → copy the `whsec_...` value
7. Paste it as `STRIPE_WEBHOOK_SECRET` in your `.env`

---

## 7. Enable mod_rewrite (if not already active)

On Hostinger the module is usually enabled. If URLs return 404:

1. In **hPanel → Advanced → PHP Configuration** — verify PHP version is 8.1+
2. Contact Hostinger support to confirm `mod_rewrite` is enabled for your plan
3. Check that both `.htaccess` files were uploaded correctly:
   - `/home/u{id}/ai-chatbot-saas/.htaccess`
   - `/home/u{id}/ai-chatbot-saas/public_html/.htaccess`

---

## 8. Run the installer

Open your browser and go to:

```
https://yourdomain.com/install.php
```

The installer will:
- Verify PHP version and required extensions
- Test the database connection
- Create all database tables from `schema.sql`
- Verify the Anthropic API key is set
- **Delete itself** after a successful install

> If the installer fails, fix the reported issue and refresh the page.

---

## 9. Set up the daily stats cron job

1. Go to **hPanel → Advanced → Cron Jobs**
2. Add a new cron job:
   - **Frequency**: Once a day (e.g. `0 3 * * *` — 3 AM UTC)
   - **Command**:
     ```
     /usr/local/bin/php /home/u{id}/ai-chatbot-saas/cron/daily_stats.php >> /home/u{id}/ai-chatbot-saas/logs/cron.log 2>&1
     ```

---

## 10. Create Stripe products & prices (if not done yet)

1. Open [Stripe Dashboard → Products](https://dashboard.stripe.com/products)
2. Create 4 products: **Starter**, **Pro**, **Business**, **Agency**
3. For each product, add a recurring price:
   | Product | Amount | Interval |
   |---|---|---|
   | Starter | $9.00 | Monthly |
   | Pro | $29.00 | Monthly |
   | Business | $79.00 | Monthly |
   | Agency | $149.00 | Monthly |
4. Copy each price ID (`price_...`) into the corresponding `STRIPE_PRICE_*` in `.env`

---

## 11. Post-deployment checklist

- [ ] `https://yourdomain.com` loads the landing page
- [ ] Registration and login work
- [ ] Admin dashboard accessible at `/admin`
- [ ] At least one chatbot can be created
- [ ] Widget loads on the test page: `https://yourdomain.com/widget-test.html`
- [ ] `/install.php` returns 404 (deleted after step 8)
- [ ] Stripe test purchase completes and subscription appears in admin
- [ ] Stripe webhook test event returns 200
- [ ] Daily stats cron job runs without errors

---

## File permissions reference

```bash
chmod 600 .env
chmod 644 .htaccess public_html/.htaccess
chmod 755 logs/
chmod -R 644 includes/ api/ admin/ public_html/
find includes/ api/ admin/ public_html/ -type d -exec chmod 755 {} \;
```

---

## Updating the app

```bash
cd /home/u{id}/ai-chatbot-saas
git pull origin main
# If schema changes are needed, run the new migration manually in phpMyAdmin
```
