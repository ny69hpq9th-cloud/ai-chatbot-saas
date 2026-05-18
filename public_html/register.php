<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';

if (auth_user()) redirect('/admin/');

$error = '';
$plan  = $_GET['plan'] ?? 'starter';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['_csrf'] ?? '')) { $error = 'Invalid request.'; goto render; }

    require_once __DIR__ . '/../includes/auth.php';
    try {
        $user = Auth::register(
            $_POST['email'] ?? '',
            $_POST['password'] ?? '',
            $_POST['name'] ?? ''
        );
        login_user($user);
        redirect('/admin/');
    } catch (RuntimeException $e) {
        $error = $e->getMessage();
    }
}

render:
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create account — <?= APP_NAME ?></title>
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="auth-page">
<div class="auth-box">
  <a href="/" class="brand"><?= APP_NAME ?></a>
  <h2>Start your free trial</h2>
  <p class="sub">14 days free, no credit card required</p>

  <?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST">
    <?= csrf_field() ?>
    <input type="hidden" name="plan" value="<?= htmlspecialchars($plan) ?>">
    <label>Full name
      <input type="text" name="name" required autocomplete="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
    </label>
    <label>Email address
      <input type="email" name="email" required autocomplete="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
    </label>
    <label>Password
      <input type="password" name="password" required minlength="8" autocomplete="new-password">
    </label>
    <button type="submit" class="btn btn-primary btn-block">Create account</button>
  </form>
  <p class="auth-alt">Already have an account? <a href="/login.php">Sign in</a></p>
</div>
</body>
</html>
