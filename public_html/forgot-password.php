<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';

if (auth_user()) redirect('/admin/');

$sent   = false;
$error  = '';
$email  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['_csrf'] ?? '')) { $error = 'Invalid request.'; goto render; }

    $email = trim($_POST['email'] ?? '');
    if (!validate_email($email)) { $error = 'Please enter a valid email address.'; goto render; }

    require_once __DIR__ . '/../includes/auth.php';
    require_once __DIR__ . '/../includes/db.php';
    require_once __DIR__ . '/../includes/mailer.php';

    // Always show success (prevents email enumeration)
    $user = DB::find('SELECT * FROM users WHERE email = ?', [sanitize_email($email)]);
    if ($user) {
        $token    = generate_token(32);
        $hashed   = hash('sha256', $token);
        DB::query(
            'REPLACE INTO password_resets (email, token_hash, expires_at) VALUES (?, ?, ?)',
            [$email, $hashed, date('Y-m-d H:i:s', strtotime('+1 hour'))]
        );
        $resetUrl = APP_URL . '/reset-password.php?token=' . $token;
        Mailer::sendPasswordReset($email, $user['full_name'], $resetUrl);
    }
    $sent = true;
}

render:
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot password — <?= APP_NAME ?></title>
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

    <?php if ($sent): ?>
      <div class="auth-sent-state">
        <div class="sent-icon">📨</div>
        <h1>Check your email</h1>
        <p class="auth-sub">If <strong><?= htmlspecialchars($email) ?></strong> is registered, we've sent a password reset link. Check your inbox — it expires in 1 hour.</p>
        <a href="/login.php" class="btn btn-outline btn-block" style="margin-top:2rem;">Back to sign in</a>
      </div>
    <?php else: ?>
      <h1>Forgot password?</h1>
      <p class="auth-sub">Enter your email and we'll send you a reset link.</p>

      <?php if ($error): ?>
        <div class="auth-errors">
          <div class="auth-error-item"><span class="err-icon">!</span><?= htmlspecialchars($error) ?></div>
        </div>
      <?php endif; ?>

      <form method="POST" novalidate>
        <?= csrf_field() ?>
        <div class="field">
          <label for="email">Email address</label>
          <input type="email" id="email" name="email" required autocomplete="email"
                 placeholder="you@company.com"
                 value="<?= htmlspecialchars($email) ?>">
        </div>
        <button type="submit" class="btn btn-primary btn-block">Send reset link</button>
      </form>
      <p class="auth-alt"><a href="/login.php">← Back to sign in</a></p>
    <?php endif; ?>
  </div>
</div>

</body>
</html>
