<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

$user = require_auth();
$uuid = $_GET['id'] ?? '';
$bot  = DB::find('SELECT * FROM chatbots WHERE uuid=? AND user_id=?', [$uuid, $user['id']]);
if (!$bot) { header('Location: /admin/chatbots.php'); exit; }

$snippet = '<script src="' . APP_URL . '/widget.js" data-bot="' . $bot['uuid'] . '"></script>';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Embed — <?= htmlspecialchars($bot['name']) ?></title>
<link rel="stylesheet" href="/admin/assets/css/admin.css">
</head>
<body>
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<main class="main">
  <?php include __DIR__ . '/partials/topbar.php'; ?>
  <div class="content">
    <div class="page-header">
      <h1>Embed: <?= htmlspecialchars($bot['name']) ?></h1>
      <a href="/admin/chatbots.php" class="btn btn-ghost">← Back</a>
    </div>

    <div class="card">
      <h3>Copy this snippet into your website's <code>&lt;/body&gt;</code> tag</h3>
      <pre class="code-block" id="snippet"><?= htmlspecialchars($snippet) ?></pre>
      <button class="btn btn-primary" onclick="copySnippet()">Copy to clipboard</button>
    </div>

    <div class="card" style="margin-top:1.5rem">
      <h3>Live preview</h3>
      <p>The widget below is a live preview of your chatbot.</p>
      <div style="position:relative;height:600px;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;background:#f9fafb;">
        <script src="/widget.js" data-bot="<?= $bot['uuid'] ?>" data-color="<?= htmlspecialchars($bot['widget_color']) ?>"></script>
      </div>
    </div>
  </div>
</main>
<script>
function copySnippet() {
  navigator.clipboard.writeText(document.getElementById('snippet').textContent.trim());
  const btn = event.target; btn.textContent = 'Copied!'; setTimeout(()=>btn.textContent='Copy to clipboard', 2000);
}
</script>
<script src="/admin/assets/js/admin.js"></script>
</body>
</html>
