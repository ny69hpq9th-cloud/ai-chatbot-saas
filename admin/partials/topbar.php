<?php $u = auth_user(); ?>
<header class="topbar">
  <button class="sidebar-toggle" id="sidebar-toggle" aria-label="Toggle sidebar">&#9776;</button>
  <div class="topbar-right">
    <span class="plan-badge plan-<?= htmlspecialchars($plan ?? 'starter') ?>"><?= ucfirst(htmlspecialchars($plan ?? 'starter')) ?></span>
    <div class="avatar-menu">
      <span class="avatar"><?= mb_strtoupper(mb_substr($u['full_name'] ?? $u['name'] ?? 'U', 0, 1)) ?></span>
      <span class="username"><?= htmlspecialchars($u['full_name'] ?? $u['name'] ?? '') ?></span>
    </div>
  </div>
</header>
