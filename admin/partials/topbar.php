<?php $u = auth_user(); ?>
<header class="topbar">
  <button class="sidebar-toggle" aria-label="Toggle sidebar">☰</button>
  <div class="topbar-right">
    <span class="plan-badge plan-<?= $plan ?? 'starter' ?>"><?= ucfirst($plan ?? 'starter') ?></span>
    <div class="avatar-menu">
      <span class="avatar"><?= mb_strtoupper(mb_substr($u['name'], 0, 1)) ?></span>
      <span class="username"><?= htmlspecialchars($u['name']) ?></span>
    </div>
  </div>
</header>
