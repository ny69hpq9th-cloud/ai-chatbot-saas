<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

$user   = require_auth();
$dbUser = DB::find('SELECT * FROM users WHERE id=?', [$user['id']]);
$msg    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['_csrf'] ?? '')) { $msg = 'error:Invalid request'; goto render; }

    if (!empty($_POST['new_password'])) {
        if (!verify_password($_POST['current_password'] ?? '', $dbUser['password_hash'])) {
            $msg = 'error:Current password is incorrect'; goto render;
        }
        DB::update('users', ['password_hash' => hash_password($_POST['new_password'])], 'id=?', [$user['id']]);
    }

    DB::update('users', [
        'full_name' => sanitize($_POST['name'] ?? $dbUser['full_name']),
        'company'   => sanitize($_POST['company'] ?? $dbUser['company']),
    ], 'id=?', [$user['id']]);

    $msg = 'success:Settings saved';
    $dbUser = DB::find('SELECT * FROM users WHERE id=?', [$user['id']]);
}

render:
[$msgType, $msgText] = $msg ? explode(':', $msg, 2) : ['', ''];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Settings — <?= APP_NAME ?></title>
<link rel="stylesheet" href="/admin/assets/css/admin.css">
</head>
<body>
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<main class="main">
  <?php include __DIR__ . '/partials/topbar.php'; ?>
  <div class="content">
    <div class="page-header"><h1>Settings</h1></div>

    <?php if ($msgText): ?>
      <div class="alert alert-<?= $msgType === 'success' ? 'success' : 'error' ?>"><?= htmlspecialchars($msgText) ?></div>
    <?php endif; ?>

    <div class="card">
      <form method="POST">
        <?= csrf_field() ?>
        <h3>Profile</h3>
        <div class="form-grid">
          <label>Full name <input type="text" name="name" value="<?= htmlspecialchars($dbUser['full_name']) ?>"></label>
          <label>Company <input type="text" name="company" value="<?= htmlspecialchars($dbUser['company']) ?>"></label>
          <label>Email <input type="email" value="<?= htmlspecialchars($dbUser['email']) ?>" disabled></label>
        </div>
        <h3 style="margin-top:2rem">Change password</h3>
        <div class="form-grid">
          <label>Current password <input type="password" name="current_password" autocomplete="current-password"></label>
          <label>New password <input type="password" name="new_password" minlength="8" autocomplete="new-password"></label>
        </div>
        <div class="form-footer">
          <button type="submit" class="btn btn-primary">Save changes</button>
        </div>
      </form>
    </div>
  </div>
</main>
<script src="/admin/assets/js/admin.js"></script>
</body>
</html>
