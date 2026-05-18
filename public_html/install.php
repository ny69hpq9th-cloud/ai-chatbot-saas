<?php
/**
 * AI Chatbot SaaS — Installation checker
 *
 * Verifies the environment, creates database tables, and deletes itself.
 * DELETE this file (or let it self-delete) after a successful install.
 *
 * Access: https://yourdomain.com/install.php
 */
declare(strict_types=1);

// ── Helpers ───────────────────────────────────────────────────────────────────
function check(string $label, bool $ok, string $detail = ''): array {
    return ['label' => $label, 'ok' => $ok, 'detail' => $detail];
}

$checks  = [];
$errors  = 0;
$rootDir = dirname(__DIR__);

// ── 1. PHP version ────────────────────────────────────────────────────────────
$phpOk = version_compare(PHP_VERSION, '8.1.0', '>=');
$checks[] = check('PHP ≥ 8.1', $phpOk, PHP_VERSION . ($phpOk ? '' : ' — upgrade required'));
if (!$phpOk) $errors++;

// ── 2. Required extensions ────────────────────────────────────────────────────
$requiredExt = ['pdo', 'pdo_mysql', 'curl', 'mbstring', 'json', 'openssl'];
foreach ($requiredExt as $ext) {
    $ok = extension_loaded($ext);
    $checks[] = check("Extension: $ext", $ok, $ok ? 'loaded' : 'MISSING — enable in php.ini');
    if (!$ok) $errors++;
}

// ── 3. Load config ────────────────────────────────────────────────────────────
$configOk = file_exists($rootDir . '/includes/config.php');
$checks[] = check('includes/config.php exists', $configOk);
if (!$configOk) {
    $errors++;
} else {
    require_once $rootDir . '/includes/config.php';

    // ── 4. .env loaded ────────────────────────────────────────────────────────
    $envOk = file_exists($rootDir . '/.env');
    $checks[] = check('.env file exists', $envOk, $envOk ? 'found' : 'MISSING — copy .env.example to .env and fill in values');
    if (!$envOk) $errors++;

    // ── 5. APP_KEY set ────────────────────────────────────────────────────────
    $appKeyOk = defined('APP_KEY') && strlen(APP_KEY) >= 32;
    $checks[] = check('APP_KEY is set (≥ 32 chars)', $appKeyOk, $appKeyOk ? 'ok' : 'Set a random 64-char hex string');
    if (!$appKeyOk) $errors++;

    // ── 6. APP_URL is HTTPS ───────────────────────────────────────────────────
    $urlOk = defined('APP_URL') && str_starts_with(APP_URL, 'https://');
    $checks[] = check('APP_URL starts with https://', $urlOk, APP_URL ?? 'not set');
    if (!$urlOk) $errors++;

    // ── 7. Database connection ────────────────────────────────────────────────
    require_once $rootDir . '/includes/db.php';
    try {
        DB::connect();
        $checks[] = check('Database connection', true, DB_HOST . ' / ' . DB_NAME);
    } catch (Throwable $e) {
        $checks[] = check('Database connection', false, $e->getMessage());
        $errors++;
    }

    // ── 8. Create tables from schema.sql ──────────────────────────────────────
    $schemaFile = $rootDir . '/database/schema.sql';
    $schemaOk   = false;
    $schemaMsg  = '';
    if (file_exists($schemaFile)) {
        try {
            $sql        = file_get_contents($schemaFile);
            $statements = array_filter(
                array_map('trim', explode(';', $sql)),
                fn($s) => $s !== '' && !str_starts_with(ltrim($s), '--')
            );
            $pdo = DB::connect();
            foreach ($statements as $stmt) {
                $pdo->exec($stmt);
            }
            $schemaOk  = true;
            $schemaMsg = count($statements) . ' statements executed';
        } catch (Throwable $e) {
            $schemaMsg = $e->getMessage();
            $errors++;
        }
    } else {
        $schemaMsg = 'database/schema.sql not found';
        $errors++;
    }
    $checks[] = check('Database tables created', $schemaOk, $schemaMsg);

    // ── 9. Verify expected tables exist ──────────────────────────────────────
    $tables    = ['users', 'chatbots', 'conversations', 'messages', 'subscriptions', 'knowledge_base'];
    $allTables = true;
    $missing   = [];
    try {
        foreach ($tables as $t) {
            $row = DB::find("SHOW TABLES LIKE '$t'");
            if (!$row) { $allTables = false; $missing[] = $t; }
        }
    } catch (Throwable) { $allTables = false; }
    $checks[] = check('All required tables present', $allTables, $allTables ? implode(', ', $tables) : 'Missing: ' . implode(', ', $missing));
    if (!$allTables) $errors++;

    // ── 10. Anthropic API key set ──────────────────────────────────────────────
    $anthropicOk = defined('ANTHROPIC_API_KEY') && str_starts_with(ANTHROPIC_API_KEY, 'sk-ant-');
    $checks[] = check('ANTHROPIC_API_KEY is set', $anthropicOk, $anthropicOk ? substr(ANTHROPIC_API_KEY, 0, 14) . '...' : 'Not set or invalid prefix');
    if (!$anthropicOk) $errors++;

    // ── 11. Stripe keys set ───────────────────────────────────────────────────
    $stripeSecretOk = defined('STRIPE_SECRET_KEY') && strlen(STRIPE_SECRET_KEY) > 10;
    $checks[] = check('STRIPE_SECRET_KEY is set', $stripeSecretOk, $stripeSecretOk ? substr(STRIPE_SECRET_KEY, 0, 8) . '...' : 'Not set');
    if (!$stripeSecretOk) $errors++;

    $stripeWebhookOk = defined('STRIPE_WEBHOOK_SECRET') && str_starts_with(STRIPE_WEBHOOK_SECRET, 'whsec_');
    $checks[] = check('STRIPE_WEBHOOK_SECRET is set', $stripeWebhookOk, $stripeWebhookOk ? 'ok' : 'Not set — needed for webhook verification');

    // ── 12. logs/ directory writable ──────────────────────────────────────────
    $logsDir     = $rootDir . '/logs';
    $logsDirOk   = is_dir($logsDir) && is_writable($logsDir);
    if (!is_dir($logsDir)) @mkdir($logsDir, 0755, true);
    $logsDirOk   = is_dir($logsDir) && is_writable($logsDir);
    $checks[] = check('logs/ directory is writable', $logsDirOk, $logsDirOk ? $logsDir : 'Create the logs/ directory and make it writable');
    if (!$logsDirOk) $errors++;

    // ── 13. mod_rewrite / .htaccess ───────────────────────────────────────────
    $htOk = file_exists($rootDir . '/public_html/.htaccess');
    $checks[] = check('public_html/.htaccess present', $htOk, $htOk ? 'found' : 'Missing — re-upload the file');
    if (!$htOk) $errors++;
}

// ── Self-delete on full success ───────────────────────────────────────────────
$selfDeleted = false;
if ($errors === 0) {
    $selfDeleted = @unlink(__FILE__);
}

// ── Render ────────────────────────────────────────────────────────────────────
$allOk  = $errors === 0;
$title  = $allOk ? '✅ Installation successful' : "❌ $errors check(s) failed";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Install — AI Chatbot SaaS</title>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: system-ui, -apple-system, sans-serif; background: #f8fafc; color: #1e293b; padding: 2rem 1rem; }
  .wrap { max-width: 680px; margin: 0 auto; }
  h1 { font-size: 1.5rem; font-weight: 700; margin-bottom: .25rem; }
  .sub { color: #64748b; font-size: .9rem; margin-bottom: 2rem; }
  .card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem; }
  table { width: 100%; border-collapse: collapse; }
  tr + tr td { border-top: 1px solid #f1f5f9; }
  td { padding: .6rem .25rem; font-size: .875rem; vertical-align: top; }
  td:first-child { width: 2rem; text-align: center; font-size: 1rem; }
  td:nth-child(2) { font-weight: 500; width: 50%; }
  td:last-child { color: #64748b; font-size: .8rem; }
  td.fail-label { color: #b91c1c; }
  td.fail-detail { color: #b91c1c; }
  .banner { padding: 1rem 1.5rem; border-radius: 10px; font-weight: 600; margin-bottom: 1.5rem; }
  .banner.ok  { background: #f0fdf4; border: 1px solid #bbf7d0; color: #15803d; }
  .banner.err { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; }
  .note { font-size: .8rem; color: #94a3b8; margin-top: 1.5rem; text-align: center; }
  a { color: #6366f1; }
</style>
</head>
<body>
<div class="wrap">
  <h1>AI Chatbot SaaS — Installer</h1>
  <p class="sub">Checks your environment and initialises the database.</p>

  <div class="banner <?= $allOk ? 'ok' : 'err' ?>">
    <?= htmlspecialchars($title) ?>
    <?php if ($allOk && $selfDeleted): ?> — this file has been deleted.<?php endif; ?>
  </div>

  <div class="card">
    <table>
      <?php foreach ($checks as $c): ?>
      <tr>
        <td><?= $c['ok'] ? '✅' : '❌' ?></td>
        <td class="<?= $c['ok'] ? '' : 'fail-label' ?>"><?= htmlspecialchars($c['label']) ?></td>
        <td class="<?= $c['ok'] ? '' : 'fail-detail' ?>"><?= htmlspecialchars($c['detail']) ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>

  <?php if ($allOk): ?>
    <div class="banner ok">
      🎉 Everything looks good! <a href="/">Go to the app →</a>
      <?php if (!$selfDeleted): ?>
        <br><strong>Security:</strong> Please delete <code>public_html/install.php</code> manually.
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="banner err">
      Fix the issues above, then <a href="install.php">reload this page</a>.
    </div>
  <?php endif; ?>

  <p class="note">
    This file is for one-time setup only.
    <?= $selfDeleted ? 'It has been automatically deleted.' : 'Delete it after a successful install.' ?>
  </p>
</div>
</body>
</html>
