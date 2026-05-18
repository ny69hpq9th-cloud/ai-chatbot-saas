<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

$user = require_auth();

$chatbots = DB::findAll('SELECT * FROM chatbots WHERE user_id = ? ORDER BY created_at DESC', [$user['id']]);
$sub      = DB::find('SELECT * FROM subscriptions WHERE user_id = ? AND status IN ("active","trialing") ORDER BY id DESC LIMIT 1', [$user['id']]);
$plan     = $sub['plan'] ?? 'starter';

// 30-day stats
$stats = DB::find(
  'SELECT COALESCE(SUM(us.conversations),0) convs, COALESCE(SUM(us.messages_sent),0) msgs, COALESCE(SUM(us.tokens_consumed),0) tokens
   FROM usage_stats us JOIN chatbots c ON c.id=us.chatbot_id
   WHERE c.user_id=? AND us.stat_date>=DATE_SUB(CURDATE(), INTERVAL 30 DAY)',
  [$user['id']]
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — <?= APP_NAME ?></title>
<link rel="stylesheet" href="/admin/assets/css/admin.css">
</head>
<body>
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<main class="main">
  <?php include __DIR__ . '/partials/topbar.php'; ?>
  <div class="content">
    <div class="page-header">
      <h1>Dashboard</h1>
      <a href="/admin/chatbots.php" class="btn btn-primary">+ New chatbot</a>
    </div>

    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-label">Chatbots</div>
        <div class="stat-value"><?= count($chatbots) ?> / <?= PLAN_LIMITS[$plan]['chatbots'] ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Conversations (30d)</div>
        <div class="stat-value"><?= number_format((int)$stats['convs']) ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Messages (30d)</div>
        <div class="stat-value"><?= number_format((int)$stats['msgs']) ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Tokens (30d)</div>
        <div class="stat-value"><?= number_format((int)$stats['tokens']) ?></div>
      </div>
    </div>

    <section class="section">
      <h2>Your Chatbots</h2>
      <?php if (empty($chatbots)): ?>
        <div class="empty-state">
          <p>No chatbots yet. <a href="/admin/chatbots.php">Create your first one</a> — it only takes a minute.</p>
        </div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="table">
            <thead><tr><th>Name</th><th>Model</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($chatbots as $bot): ?>
              <tr>
                <td><strong><?= htmlspecialchars($bot['name']) ?></strong></td>
                <td><code><?= htmlspecialchars($bot['model']) ?></code></td>
                <td><span class="badge <?= $bot['is_active'] ? 'badge-success' : 'badge-gray' ?>">
                  <?= $bot['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                <td><?= date('M j, Y', strtotime($bot['created_at'])) ?></td>
                <td>
                  <a href="/admin/chatbots.php?edit=<?= $bot['uuid'] ?>" class="btn btn-sm">Edit</a>
                  <a href="/admin/embed.php?id=<?= $bot['uuid'] ?>" class="btn btn-sm btn-outline">Embed</a>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>
  </div>
</main>
<script src="/admin/assets/js/admin.js"></script>
</body>
</html>
