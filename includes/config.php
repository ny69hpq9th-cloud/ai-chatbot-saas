<?php
declare(strict_types=1);

// ── Environment ──────────────────────────────────────────────────────────────
$_env = [];
if (file_exists(__DIR__ . '/../.env')) {
    foreach (file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        $_env[trim($k)] = trim($v, " \t\n\r\0\x0B\"'");
    }
}
function env(string $key, mixed $default = null): mixed {
    global $_env;
    return $_env[$key] ?? getenv($key) ?: $default;
}

// ── App ───────────────────────────────────────────────────────────────────────
define('APP_NAME',    env('APP_NAME', 'AI Chatbot Platform'));
define('APP_URL',     rtrim(env('APP_URL', 'https://yourdomain.com'), '/'));
define('APP_ENV',     env('APP_ENV', 'production'));
define('APP_DEBUG',   env('APP_DEBUG', 'false') === 'true');
define('APP_KEY',     env('APP_KEY', ''));
define('APP_VERSION', '1.0.0');

// ── Database ──────────────────────────────────────────────────────────────────
define('DB_HOST',     env('DB_HOST', 'localhost'));
define('DB_PORT',     (int) env('DB_PORT', '3306'));
define('DB_NAME',     env('DB_NAME', 'ai_chatbot'));
define('DB_USER',     env('DB_USER', 'root'));
define('DB_PASS',     env('DB_PASS', ''));
define('DB_CHARSET',  'utf8mb4');

// ── Stripe ────────────────────────────────────────────────────────────────────
define('STRIPE_PUBLIC_KEY',    env('STRIPE_PUBLIC_KEY', ''));
define('STRIPE_SECRET_KEY',    env('STRIPE_SECRET_KEY', ''));
define('STRIPE_WEBHOOK_SECRET', env('STRIPE_WEBHOOK_SECRET', ''));

define('STRIPE_PRICE_STARTER',    env('STRIPE_PRICE_STARTER', ''));
define('STRIPE_PRICE_PRO',        env('STRIPE_PRICE_PRO', ''));
define('STRIPE_PRICE_BUSINESS',   env('STRIPE_PRICE_BUSINESS', ''));
define('STRIPE_PRICE_ENTERPRISE', env('STRIPE_PRICE_ENTERPRISE', ''));
define('STRIPE_PRICE_AGENCY',     env('STRIPE_PRICE_AGENCY', ''));

// ── Claude / Anthropic ────────────────────────────────────────────────────────
define('ANTHROPIC_API_KEY',     env('ANTHROPIC_API_KEY', ''));
define('ANTHROPIC_API_URL',     'https://api.anthropic.com/v1');
define('ANTHROPIC_API_VERSION', '2023-06-01');

// ── Plan limits ───────────────────────────────────────────────────────────────
define('PLAN_LIMITS', [
    'starter'    => ['chatbots' => 1,   'messages_per_month' => 500,   'tokens_per_month' => 100_000],
    'pro'        => ['chatbots' => 5,   'messages_per_month' => 5_000,  'tokens_per_month' => 1_000_000],
    'business'   => ['chatbots' => 20,  'messages_per_month' => 25_000, 'tokens_per_month' => 5_000_000],
    'enterprise' => ['chatbots' => 999,  'messages_per_month' => 999_999,  'tokens_per_month' => 999_999_999],
    'agency'     => ['chatbots' => 9999, 'messages_per_month' => 9_999_999,'tokens_per_month' => 999_999_999],
]);

// ── Session ───────────────────────────────────────────────────────────────────
define('SESSION_NAME',     'aichat_session');
define('SESSION_LIFETIME', 60 * 60 * 24 * 30); // 30 days

// ── Email ─────────────────────────────────────────────────────────────────────
define('MAIL_HOST',       env('MAIL_HOST', 'smtp.mailtrap.io'));
define('MAIL_PORT',       (int) env('MAIL_PORT', '587'));
define('MAIL_USERNAME',   env('MAIL_USERNAME', ''));
define('MAIL_PASSWORD',   env('MAIL_PASSWORD', ''));
define('MAIL_FROM',       env('MAIL_FROM', 'noreply@yourdomain.com'));
define('MAIL_FROM_NAME',  env('MAIL_FROM_NAME', APP_NAME));

// ── Paths ─────────────────────────────────────────────────────────────────────
define('ROOT_PATH',   dirname(__DIR__));
define('PUBLIC_PATH', ROOT_PATH . '/public_html');
define('ADMIN_PATH',  ROOT_PATH . '/admin');

// ── Error handling ────────────────────────────────────────────────────────────
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', ROOT_PATH . '/logs/error.log');
}

date_default_timezone_set('UTC');
