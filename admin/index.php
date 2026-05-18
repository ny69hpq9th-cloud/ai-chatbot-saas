<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

$user = require_auth();

$chatbots = DB::findAll('SELECT * FROM chatbots WHERE user_id = ? ORDER BY created_at DESC', [$user['id']]);
$sub      = DB::find('SELECT * FROM subscriptions WHERE user_id = ? AND status IN ("active","trialing") ORDER BY id DESC LIMIT 1', [$user['id']]);
$plan     = $sub['plan'] ?? 'starter';

$stats = DB::find(
  'SELECT COALESCE(SUM(us.conversations),0) convs,
          COALESCE(SUM(us.messages_sent),0) msgs,
          COALESCE(SUM(us.tokens_consumed),0) tokens
   FROM usage_stats us JOIN chatbots c ON c.id=us.chatbot_id
   WHERE c.user_id=? AND us.stat_date>=DATE_SUB(CURDATE(), INTERVAL 30 DAY)',
  [$user['id']]
);

$planLimit = PLAN_LIMITS[$plan]['chatbots'] ?? 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — <?= APP_NAME ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/admin/assets/css/admin.css">
</head>
<body>
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<main class="main">
  <?php include __DIR__ . '/partials/topbar.php'; ?>
  <div class="content">

    <div class="page-header">
      <h1>Dashboard</h1>
      <?php if (count($chatbots) < $planLimit): ?>
        <a href="/admin/chatbots.php" class="btn btn-primary">+ New chatbot</a>
      <?php else: ?>
        <a href="/admin/billing.php" class="btn btn-outline">Upgrade plan</a>
      <?php endif; ?>
    </div>

    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-label">Chatbots</div>
        <div class="stat-value"><?= count($chatbots) ?> <span style="font-size:1rem;opacity:.4">/ <?= $planLimit ?></span></div>
        <div class="stat-sub">on <?= ucfirst($plan) ?> plan</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Conversations</div>
        <div class="stat-value"><?= number_format((int)$stats['convs']) ?></div>
        <div class="stat-sub">last 30 days</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Messages sent</div>
        <div class="stat-value"><?= number_format((int)$stats['msgs']) ?></div>
        <div class="stat-sub">last 30 days</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Tokens used</div>
        <div class="stat-value"><?= number_format((int)$stats['tokens']) ?></div>
        <div class="stat-sub">last 30 days</div>
      </div>
    </div>

    <section class="section">
      <h2>Your Chatbots</h2>
      <?php if (empty($chatbots)): ?>
        <div class="empty-state">
          <span class="empty-icon">&#127916;</span>
          <p>No chatbots yet. <a href="/admin/chatbots.php">Create your first one</a> — it only takes a minute and no credit card is required.</p>
        </div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="table">
            <thead>
              <tr>
                <th>Name</th>
                <th>Model</th>
                <th>Status</th>
                <th>Created</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($chatbots as $bot): ?>
              <tr>
                <td><strong><?= htmlspecialchars($bot['name']) ?></strong></td>
                <td><code><?= htmlspecialchars($bot['model']) ?></code></td>
                <td>
                  <span class="badge <?= $bot['is_active'] ? 'badge-success' : 'badge-gray' ?>">
                    <?= $bot['is_active'] ? 'Active' : 'Inactive' ?>
                  </span>
                </td>
                <td><?= date('M j, Y', strtotime($bot['created_at'])) ?></td>
                <td style="display:flex;gap:.5rem;flex-wrap:wrap;">
                  <a href="/admin/chatbots.php?edit=<?= urlencode($bot['uuid']) ?>" class="btn btn-sm btn-ghost">Edit</a>
                  <a href="/admin/embed.php?id=<?= urlencode($bot['uuid']) ?>" class="btn btn-sm btn-outline">Embed</a>
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
<script>
  // Mobile sidebar toggle
  var toggle  = document.getElementById('sidebar-toggle');
  var sidebar = document.getElementById('sidebar');
  if (toggle && sidebar) {
    toggle.addEventListener('click', function () {
      sidebar.classList.toggle('open');
    });
    document.addEventListener('click', function (e) {
      if (!sidebar.contains(e.target) && e.target !== toggle) {
        sidebar.classList.remove('open');
      }
    });
  }
</script>
</body>
</html>
