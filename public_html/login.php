<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';

if (auth_user()) redirect('/admin/');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['_csrf'] ?? '')) { $error = 'Invalid request.'; goto render; }
    require_once __DIR__ . '/../includes/auth.php';
    $user = Auth::attempt($_POST['email'] ?? '', $_POST['password'] ?? '');
    if ($user) {
        login_user($user);
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
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="auth-page">
<div class="auth-box">
  <a href="/" class="brand"><?= APP_NAME ?></a>
  <h2>Welcome back</h2>

  <?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST">
    <?= csrf_field() ?>
    <label>Email address
      <input type="email" name="email" required autocomplete="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
    </label>
    <label>Password
      <input type="password" name="password" required autocomplete="current-password">
    </label>
    <button type="submit" class="btn btn-primary btn-block">Sign in</button>
  </form>
  <p class="auth-alt"><a href="/forgot-password.php">Forgot password?</a></p>
  <p class="auth-alt">No account? <a href="/register.php">Create one free</a></p>
</div>
</body>
</html>
