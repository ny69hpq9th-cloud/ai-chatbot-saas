<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

$user = require_auth();
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;

$total = DB::count(
    'SELECT COUNT(*) FROM conversations conv JOIN chatbots b ON b.id=conv.chatbot_id WHERE b.user_id=?',
    [$user['id']]
);
$pager = paginate($total, $perPage, $page);

$convs = DB::findAll(
    "SELECT conv.uuid, conv.session_id, conv.message_count, conv.started_at, conv.last_message_at,
            conv.is_resolved, b.name chatbot_name
     FROM conversations conv
     JOIN chatbots b ON b.id=conv.chatbot_id
     WHERE b.user_id=?
     ORDER BY conv.last_message_at DESC
     LIMIT $perPage OFFSET {$pager['offset']}",
    [$user['id']]
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Conversations — <?= APP_NAME ?></title>
<link rel="stylesheet" href="/admin/assets/css/admin.css">
</head>
<body>
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<main class="main">
  <?php include __DIR__ . '/partials/topbar.php'; ?>
  <div class="content">
    <div class="page-header"><h1>Conversations</h1></div>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>Chatbot</th><th>Session</th><th>Messages</th><th>Last activity</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($convs as $c): ?>
          <tr>
            <td><?= htmlspecialchars($c['chatbot_name']) ?></td>
            <td><code><?= htmlspecialchars(substr($c['session_id'], 0, 16)) ?>…</code></td>
            <td><?= $c['message_count'] ?></td>
            <td><?= $c['last_message_at'] ? time_ago($c['last_message_at']) : '—' ?></td>
            <td><span class="badge <?= $c['is_resolved'] ? 'badge-success' : 'badge-gray' ?>">
              <?= $c['is_resolved'] ? 'Resolved' : 'Open' ?></span></td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($convs)): ?>
          <tr><td colspan="5" style="text-align:center;color:#6b7280;padding:2rem">No conversations yet.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
    <?php if ($pager['pages'] > 1): ?>
      <div class="pagination">
        <?php if ($pager['has_prev']): ?><a href="?page=<?= $page - 1 ?>" class="btn btn-ghost">← Prev</a><?php endif; ?>
        <span>Page <?= $page ?> of <?= $pager['pages'] ?></span>
        <?php if ($pager['has_next']): ?><a href="?page=<?= $page + 1 ?>" class="btn btn-primary">Next →</a><?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</main>
<script src="/admin/assets/js/admin.js"></script>
</body>
</html>
