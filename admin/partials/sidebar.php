<?php $current = basename($_SERVER['PHP_SELF']); ?>
<aside class="sidebar">
  <div class="sidebar-brand">
    <a href="/admin/"><?= APP_NAME ?></a>
  </div>
  <nav class="sidebar-nav">
    <a href="/admin/" class="nav-item <?= $current === 'index.php' ? 'active' : '' ?>">
      <span class="icon">📊</span> Dashboard
    </a>
    <a href="/admin/chatbots.php" class="nav-item <?= $current === 'chatbots.php' ? 'active' : '' ?>">
      <span class="icon">🤖</span> Chatbots
    </a>
    <a href="/admin/conversations.php" class="nav-item <?= $current === 'conversations.php' ? 'active' : '' ?>">
      <span class="icon">💬</span> Conversations
    </a>
    <a href="/admin/analytics.php" class="nav-item <?= $current === 'analytics.php' ? 'active' : '' ?>">
      <span class="icon">📈</span> Analytics
    </a>
    <a href="/admin/billing.php" class="nav-item <?= $current === 'billing.php' ? 'active' : '' ?>">
      <span class="icon">💳</span> Billing
    </a>
    <a href="/admin/settings.php" class="nav-item <?= $current === 'settings.php' ? 'active' : '' ?>">
      <span class="icon">⚙️</span> Settings
    </a>
  </nav>
  <div class="sidebar-footer">
    <a href="/api/auth/logout" class="nav-item">
      <span class="icon">🚪</span> Sign out
    </a>
  </div>
</aside>
