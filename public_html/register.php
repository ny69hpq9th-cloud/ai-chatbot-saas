<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';

if (auth_user()) redirect('/admin/');

$plan   = $_GET['plan'] ?? 'starter';
$errors = [];
$values = ['name' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['_csrf'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
        goto render;
    }

    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['password_confirm'] ?? '';
    $values   = compact('name', 'email');

    // Frontend-style backend validation
    if (mb_strlen($name) < 2)        $errors[] = 'Full name must be at least 2 characters.';
    if (!validate_email($email))     $errors[] = 'Please enter a valid email address.';
    if (mb_strlen($password) < 8)   $errors[] = 'Password must be at least 8 characters.';
    if (!preg_match('/[0-9]/', $password)) $errors[] = 'Password must contain at least one number.';
    if ($password !== $confirm)      $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        require_once __DIR__ . '/../includes/auth.php';
        require_once __DIR__ . '/../includes/mailer.php';
        try {
            $user = Auth::register($email, $password, $name);
            login_user($user);
            Mailer::sendWelcome($email, $name);
            redirect('/admin/');
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }
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
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/style.css">
<link rel="stylesheet" href="/assets/css/auth.css">
</head>
<body class="auth-page">

<div class="auth-wrap">
  <!-- Left panel — branding -->
  <div class="auth-panel-left">
    <a href="/" class="auth-logo"><?= APP_NAME ?></a>
    <div class="auth-pitch">
      <h2>The fastest way to deploy AI on your website</h2>
      <ul class="auth-features">
        <li><span class="check">✓</span> 14-day free trial, no credit card</li>
        <li><span class="check">✓</span> Live in under 2 minutes</li>
        <li><span class="check">✓</span> Powered by Anthropic Claude</li>
        <li><span class="check">✓</span> Cancel anytime</li>
      </ul>
    </div>
    <div class="auth-orb auth-orb-1"></div>
    <div class="auth-orb auth-orb-2"></div>
  </div>

  <!-- Right panel — form -->
  <div class="auth-panel-right">
    <div class="auth-form-wrap">
      <div class="auth-step">Step 1 of 1</div>
      <h1>Create your account</h1>
      <p class="auth-sub">Start your free 14-day trial. No credit card required.</p>

      <?php if (!empty($errors)): ?>
        <div class="auth-errors">
          <?php foreach ($errors as $e): ?>
            <div class="auth-error-item">
              <span class="err-icon">!</span>
              <?= htmlspecialchars($e) ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <form method="POST" id="register-form" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="plan" value="<?= htmlspecialchars($plan) ?>">

        <div class="field">
          <label for="name">Full name</label>
          <input type="text" id="name" name="name" required autocomplete="name"
                 placeholder="John Smith"
                 value="<?= htmlspecialchars($values['name']) ?>"
                 class="<?= in_array('Full name must be at least 2 characters.', $errors) ? 'is-error' : '' ?>">
        </div>

        <div class="field">
          <label for="email">Work email</label>
          <input type="email" id="email" name="email" required autocomplete="email"
                 placeholder="john@company.com"
                 value="<?= htmlspecialchars($values['email']) ?>"
                 class="<?= in_array('Please enter a valid email address.', $errors) ? 'is-error' : '' ?>">
        </div>

        <div class="field">
          <label for="password">Password</label>
          <div class="input-wrap">
            <input type="password" id="password" name="password" required minlength="8"
                   autocomplete="new-password" placeholder="Min. 8 characters + number">
            <button type="button" class="toggle-pw" aria-label="Show password" data-target="password">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
          <div class="pw-strength" id="pw-strength">
            <div class="pw-bar"><div class="pw-fill" id="pw-fill"></div></div>
            <span id="pw-label"></span>
          </div>
        </div>

        <div class="field">
          <label for="password_confirm">Confirm password</label>
          <div class="input-wrap">
            <input type="password" id="password_confirm" name="password_confirm" required
                   autocomplete="new-password" placeholder="Repeat your password">
            <button type="button" class="toggle-pw" aria-label="Show password" data-target="password_confirm">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
        </div>

        <button type="submit" class="btn btn-primary btn-block" id="submit-btn">
          <span>Create account</span>
          <svg class="btn-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
        </button>
      </form>

      <p class="auth-alt">Already have an account? <a href="/login.php">Sign in →</a></p>
      <p class="auth-legal">By creating an account you agree to our <a href="/terms.php">Terms</a> and <a href="/privacy.php">Privacy Policy</a>.</p>
    </div>
  </div>
</div>

<script src="/assets/js/auth.js"></script>
</body>
</html>
