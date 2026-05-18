<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';

if (auth_user()) redirect('/admin/');

$token  = trim($_GET['token'] ?? '');
$hashed = hash('sha256', $token);
$reset  = $token ? DB::find(
    'SELECT * FROM password_resets WHERE token_hash = ? AND expires_at > NOW()',
    [$hashed]
) : null;

$error  = '';
$done   = false;

if (!$reset && !$done) {
    $error = 'This reset link is invalid or has expired.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $reset) {
    if (!verify_csrf($_POST['_csrf'] ?? '')) { $error = 'Invalid request.'; goto render; }

    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['password_confirm'] ?? '';

    if (mb_strlen($password) < 8)           { $error = 'Password must be at least 8 characters.'; goto render; }
    if (!preg_match('/[0-9]/', $password))  { $error = 'Password must contain at least one number.'; goto render; }
    if ($password !== $confirm)             { $error = 'Passwords do not match.'; goto render; }

    require_once __DIR__ . '/../includes/auth.php';
    if (Auth::resetPassword($token, $password)) {
        $done = true;
    } else {
        $error = 'Reset failed. The link may have expired.';
    }
}

render:
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset password — <?= APP_NAME ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/style.css">
<link rel="stylesheet" href="/assets/css/auth.css">
</head>
<body class="auth-page">

<div class="auth-wrap auth-wrap--centered">
  <div class="auth-form-wrap auth-card">
    <a href="/" class="auth-logo auth-logo--center"><?= APP_NAME ?></a>

    <?php if ($done): ?>
      <div class="auth-sent-state">
        <div class="sent-icon">✅</div>
        <h1>Password updated!</h1>
        <p class="auth-sub">Your new password has been saved. You can now sign in.</p>
        <a href="/login.php?reset=1" class="btn btn-primary btn-block" style="margin-top:2rem;">Sign in →</a>
      </div>
    <?php else: ?>
      <h1>Set new password</h1>
      <p class="auth-sub">Choose a strong password for your account.</p>

      <?php if ($error): ?>
        <div class="auth-errors">
          <div class="auth-error-item"><span class="err-icon">!</span><?= htmlspecialchars($error) ?></div>
        </div>
      <?php endif; ?>

      <?php if ($reset): ?>
      <form method="POST" novalidate>
        <?= csrf_field() ?>
        <div class="field">
          <label for="password">New password</label>
          <div class="input-wrap">
            <input type="password" id="password" name="password" required minlength="8" placeholder="Min. 8 characters + number">
            <button type="button" class="toggle-pw" data-target="password" aria-label="Show">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
          <div class="pw-strength">
            <div class="pw-bar"><div class="pw-fill" id="pw-fill"></div></div>
            <span id="pw-label"></span>
          </div>
        </div>
        <div class="field">
          <label for="password_confirm">Confirm new password</label>
          <div class="input-wrap">
            <input type="password" id="password_confirm" name="password_confirm" required placeholder="Repeat password">
            <button type="button" class="toggle-pw" data-target="password_confirm" aria-label="Show">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Update password</button>
      </form>
      <?php endif; ?>
      <p class="auth-alt"><a href="/login.php">← Back to sign in</a></p>
    <?php endif; ?>
  </div>
</div>

<script src="/assets/js/auth.js"></script>
</body>
</html>
