<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

$user = require_auth();
$sub  = DB::find('SELECT plan FROM subscriptions WHERE user_id=? AND status IN ("active","trialing") ORDER BY id DESC LIMIT 1', [$user['id']]);
$plan = $sub['plan'] ?? 'starter';

$editBot = null;
if (!empty($_GET['edit'])) {
    $editBot = DB::find('SELECT * FROM chatbots WHERE uuid=? AND user_id=?', [$_GET['edit'], $user['id']]);
}

$chatbots = DB::findAll('SELECT * FROM chatbots WHERE user_id=? ORDER BY created_at DESC', [$user['id']]);
$models   = ['claude-haiku-4-5-20251001','claude-sonnet-4-6','claude-opus-4-7'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Chatbots — <?= APP_NAME ?></title>
<link rel="stylesheet" href="/admin/assets/css/admin.css">
</head>
<body>
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<main class="main">
  <?php include __DIR__ . '/partials/topbar.php'; ?>
  <div class="content">
    <div class="page-header">
      <h1><?= $editBot ? 'Edit Chatbot' : 'Chatbots' ?></h1>
      <?php if (!$editBot): ?>
        <button class="btn btn-primary" onclick="document.getElementById('create-form').classList.toggle('hidden')">
          + New chatbot
        </button>
      <?php endif; ?>
    </div>

    <?php if ($editBot): ?>
    <!-- Edit form -->
    <div class="card">
      <form id="bot-form" data-uuid="<?= $editBot['uuid'] ?>">
        <div class="form-grid">
          <label>Name <input type="text" name="name" value="<?= htmlspecialchars($editBot['name']) ?>" required></label>
          <label>Model
            <select name="model">
              <?php foreach ($models as $m): ?>
                <option value="<?= $m ?>" <?= $editBot['model']===$m ? 'selected' : '' ?>><?= $m ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <label class="full">System prompt
            <textarea name="system_prompt" rows="5"><?= htmlspecialchars($editBot['system_prompt'] ?? '') ?></textarea>
          </label>
          <label>Welcome message
            <input type="text" name="welcome_message" value="<?= htmlspecialchars($editBot['welcome_message'] ?? '') ?>">
          </label>
          <label>Widget color
            <input type="color" name="widget_color" value="<?= htmlspecialchars($editBot['widget_color']) ?>">
          </label>
          <label>Temperature (0–1)
            <input type="number" name="temperature" min="0" max="1" step="0.1" value="<?= $editBot['temperature'] ?>">
          </label>
          <label>Max tokens
            <input type="number" name="max_tokens" min="64" max="4096" value="<?= $editBot['max_tokens'] ?>">
          </label>
          <label>Allowed domains (comma-separated)
            <input type="text" name="allowed_domains" value="<?= htmlspecialchars($editBot['allowed_domains'] ?? '') ?>">
          </label>
        </div>
        <div class="form-footer">
          <button type="submit" class="btn btn-primary">Save changes</button>
          <a href="/admin/chatbots.php" class="btn btn-ghost">Cancel</a>
        </div>
      </form>
    </div>
    <?php else: ?>

    <!-- Create form (hidden by default) -->
    <div id="create-form" class="card hidden">
      <h3>Create new chatbot</h3>
      <form id="create-bot-form">
        <div class="form-grid">
          <label>Name <input type="text" name="name" required placeholder="My support bot"></label>
          <label>Model
            <select name="model">
              <?php foreach ($models as $m): ?>
                <option value="<?= $m ?>"><?= $m ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <label class="full">System prompt
            <textarea name="system_prompt" rows="4" placeholder="You are a helpful assistant for..."></textarea>
          </label>
        </div>
        <button type="submit" class="btn btn-primary">Create chatbot</button>
      </form>
    </div>

    <!-- Chatbots list -->
    <div class="bots-grid">
      <?php foreach ($chatbots as $bot): ?>
        <div class="bot-card">
          <div class="bot-color" style="background:<?= htmlspecialchars($bot['widget_color']) ?>"></div>
          <div class="bot-info">
            <h3><?= htmlspecialchars($bot['name']) ?></h3>
            <code><?= htmlspecialchars($bot['model']) ?></code>
          </div>
          <div class="bot-actions">
            <a href="/admin/chatbots.php?edit=<?= $bot['uuid'] ?>" class="btn btn-sm">Edit</a>
            <a href="/admin/embed.php?id=<?= $bot['uuid'] ?>" class="btn btn-sm btn-outline">Embed code</a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</main>
<script src="/admin/assets/js/admin.js"></script>
</body>
</html>
