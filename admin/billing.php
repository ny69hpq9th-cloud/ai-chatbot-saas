<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

$user = require_auth();
$sub  = DB::find('SELECT * FROM subscriptions WHERE user_id=? ORDER BY id DESC LIMIT 1', [$user['id']]);
$plan = $sub['plan'] ?? 'free';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Billing — <?= APP_NAME ?></title>
<link rel="stylesheet" href="/admin/assets/css/admin.css">
</head>
<body>
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<main class="main">
  <?php include __DIR__ . '/partials/topbar.php'; ?>
  <div class="content">
    <div class="page-header"><h1>Billing</h1></div>

    <?php if (!empty($_GET['success'])): ?>
      <div class="alert alert-success">Subscription activated! Welcome to <?= ucfirst($plan) ?>.</div>
    <?php endif; ?>
    <?php if (!empty($_GET['canceled'])): ?>
      <div class="alert alert-info">Checkout canceled. Your current plan is unchanged.</div>
    <?php endif; ?>

    <?php if ($sub): ?>
    <div class="card">
      <h3>Current plan</h3>
      <p><strong><?= ucfirst($sub['plan']) ?></strong> — Status: <span class="badge badge-<?= $sub['status']==='active'?'success':'gray' ?>"><?= $sub['status'] ?></span></p>
      <?php if ($sub['current_period_end']): ?>
        <p>Renews <?= date('M j, Y', strtotime($sub['current_period_end'])) ?></p>
      <?php endif; ?>
      <button class="btn btn-outline" id="open-portal">Manage billing →</button>
    </div>
    <?php endif; ?>

    <h2 style="margin:2rem 0 1.5rem">Choose a plan</h2>
    <div id="plans-container" class="grid-4"></div>
  </div>
</main>
<script>
const currentPlan = <?= json_encode($plan) ?>;
fetch('/api/billing/plans').then(r=>r.json()).then(res=>{
  if(!res.success) return;
  const c = document.getElementById('plans-container');
  res.data.forEach(p=>{
    const div = document.createElement('div');
    div.className = 'price-card' + (p.id===currentPlan?' featured':'');
    div.innerHTML = `
      ${p.id===currentPlan ? '<div class="badge">Current plan</div>' : ''}
      <h3>${p.name}</h3>
      <div class="price">${p.price ? '$'+p.price+'<span>/mo</span>' : 'Custom'}</div>
      <ul>${p.features.map(f=>`<li>${f}</li>`).join('')}</ul>
      ${p.stripe_price_id && p.id!==currentPlan ? `<button class="btn btn-primary upgrade-btn" data-price="${p.stripe_price_id}">Upgrade</button>` : ''}
    `;
    c.appendChild(div);
  });
  document.querySelectorAll('.upgrade-btn').forEach(btn=>{
    btn.addEventListener('click',()=>{
      btn.disabled=true; btn.textContent='Loading…';
      fetch('/api/billing/checkout',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({price_id:btn.dataset.price})})
        .then(r=>r.json()).then(d=>{ if(d.data?.url) location.href=d.data.url; });
    });
  });
});
document.getElementById('open-portal')?.addEventListener('click',()=>{
  fetch('/api/billing/portal',{method:'POST'}).then(r=>r.json()).then(d=>{ if(d.data?.url) location.href=d.data.url; });
});
</script>
<script src="/admin/assets/js/admin.js"></script>
</body>
</html>
