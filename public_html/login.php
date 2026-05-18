<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';

// Auto-login via remember cookie
if (!auth_user() && !empty($_COOKIE['remember_token'])) {
    $rawToken = $_COOKIE['remember_token'];
    $hashed   = hash('sha256', $rawToken);
    $dbUser   = DB::find('SELECT * FROM users WHERE remember_token = ?', [$hashed]);
    if ($dbUser) {
        login_user($dbUser);
        redirect($_GET['redirect'] ?? '/admin/');
    }
    // Invalid cookie — clear it
    setcookie('remember_token', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
}

if (auth_user()) redirect('/admin/');

$error  = '';
$email  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['_csrf'] ?? '')) { $error = 'Invalid request.'; goto render; }

    require_once __DIR__ . '/../includes/auth.php';
    $email  = trim($_POST['email'] ?? '');
    $user   = Auth::attempt($email, $_POST['password'] ?? '');

    if ($user) {
        login_user($user);

        // Remember me — store hashed token in DB + cookie (30 days)
        if (!empty($_POST['remember'])) {
            $token  = generate_token(32);
            $hashed = hash('sha256', $token);
            DB::update('users', ['remember_token' => $hashed], 'id = ?', [$user['id']]);
            setcookie('remember_token', $token, time() + 60 * 60 * 24 * 30, '/', '', isset($_SERVER['HTTPS']), true);
        }

        redirect($_GET['redirect'] ?? '/admin/');
    }
    $error = 'Invalid email or password.';
}

render:
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign in — <?= APP_NAME ?></title>
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
    <h1>Welcome back</h1>
    <p class="auth-sub">Sign in to your dashboard</p>

    <?php if ($error): ?>
      <div class="auth-errors">
        <div class="auth-error-item"><span class="err-icon">!</span><?= htmlspecialchars($error) ?></div>
      </div>
    <?php endif; ?>

    <?php if (!empty($_GET['reset'])): ?>
      <div class="auth-success-msg">
        <span>✓</span> Password updated successfully. Please sign in.
      </div>
    <?php endif; ?>

    <form method="POST" id="login-form" novalidate>
      <?= csrf_field() ?>

      <div class="field">
        <label for="email">Email address</label>
        <input type="email" id="email" name="email" required autocomplete="email"
               placeholder="you@company.com"
               value="<?= htmlspecialchars($email) ?>">
      </div>

      <div class="field">
        <label for="password">
          Password
          <a href="/forgot-password.php" class="label-link">Forgot password?</a>
        </label>
        <div class="input-wrap">
          <input type="password" id="password" name="password" required autocomplete="current-password" placeholder="Your password">
          <button type="button" class="toggle-pw" data-target="password" aria-label="Show password">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
      </div>

      <div class="field field--checkbox">
        <label class="checkbox-label">
          <input type="checkbox" name="remember" id="remember" value="1">
          <span class="checkbox-custom"></span>
          Keep me signed in for 30 days
        </label>
      </div>

      <button type="submit" class="btn btn-primary btn-block">
        <span>Sign in</span>
        <svg class="btn-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
      </button>
    </form>

    <p class="auth-alt">No account yet? <a href="/register.php">Start free trial →</a></p>
  </div>
</div>

<script src="/assets/js/auth.js"></script>
</body>
</html>
