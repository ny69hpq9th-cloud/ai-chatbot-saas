<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

$user = require_auth();
$sub  = DB::find(
    'SELECT * FROM subscriptions WHERE user_id=? ORDER BY id DESC LIMIT 1',
    [$user['id']]
);
$plan = $sub['plan'] ?? 'free';

// Fetch last 10 invoices from Stripe if customer exists
$dbUser   = DB::find('SELECT stripe_customer_id FROM users WHERE id=?', [$user['id']]);
$invoices = [];
if (!empty($dbUser['stripe_customer_id'])) {
    $ch = curl_init('https://api.stripe.com/v1/invoices?' . http_build_query([
        'customer' => $dbUser['stripe_customer_id'],
        'limit'    => 10,
        'status'   => 'paid',
    ]));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERPWD        => STRIPE_SECRET_KEY . ':',
    ]);
    $res = json_decode(curl_exec($ch), true);
    curl_close($ch);
    $invoices = $res['data'] ?? [];
}

$isCanceling = $sub && !empty($sub['canceled_at']) && $sub['status'] !== 'canceled';
$isActive    = $sub && in_array($sub['status'], ['active', 'trialing'], true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Billing — <?= APP_NAME ?></title>
<link rel="stylesheet" href="/admin/assets/css/admin.css">
<style>
.billing-header { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1rem; margin-bottom:2rem; }
.plan-badge { display:inline-flex; align-items:center; gap:.5rem; background:#f0f9ff; border:1px solid #bae6fd; color:#0369a1; border-radius:999px; padding:.35rem 1rem; font-weight:600; font-size:.85rem; }
.plan-badge.active { background:#f0fdf4; border-color:#bbf7d0; color:#15803d; }
.plan-badge.canceled { background:#fff7ed; border-color:#fed7aa; color:#c2410c; }
.invoice-table { width:100%; border-collapse:collapse; font-size:.875rem; }
.invoice-table th { text-align:left; padding:.6rem 1rem; border-bottom:2px solid var(--border,#e2e8f0); color:var(--muted,#64748b); font-weight:600; font-size:.75rem; text-transform:uppercase; letter-spacing:.05em; }
.invoice-table td { padding:.75rem 1rem; border-bottom:1px solid var(--border,#e2e8f0); }
.invoice-table tr:last-child td { border-bottom:none; }
.badge-paid { background:#dcfce7; color:#15803d; border-radius:999px; padding:.2rem .6rem; font-size:.75rem; font-weight:600; }
.cancel-zone { border:1px solid #fecaca; border-radius:12px; padding:1.5rem; margin-top:2rem; }
.cancel-zone h4 { color:#b91c1c; margin-bottom:.5rem; }
.cancel-zone p { font-size:.875rem; color:var(--muted,#64748b); margin-bottom:1rem; }
</style>
</head>
<body>
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<main class="main">
  <?php include __DIR__ . '/partials/topbar.php'; ?>
  <div class="content">

    <?php if (!empty($_GET['success'])): ?>
      <div class="alert alert-success">Subscription activated! Welcome to <?= htmlspecialchars(ucfirst($plan)) ?>.</div>
    <?php endif; ?>
    <?php if (!empty($_GET['canceled'])): ?>
      <div class="alert alert-info">Checkout canceled. Your current plan is unchanged.</div>
    <?php endif; ?>

    <div class="page-header billing-header">
      <h1>Billing</h1>
      <?php if ($sub): ?>
        <span class="plan-badge <?= $isCanceling ? 'canceled' : ($isActive ? 'active' : '') ?>">
          <?= htmlspecialchars(ucfirst($sub['plan'])) ?>
          &nbsp;·&nbsp;
          <?= $isCanceling ? 'Cancels '.date('M j, Y', strtotime($sub['current_period_end'] ?? 'now')) : htmlspecialchars($sub['status']) ?>
        </span>
      <?php endif; ?>
    </div>

    <!-- Current subscription card -->
    <?php if ($sub && $sub['status'] !== 'canceled'): ?>
    <div class="card" style="margin-bottom:1.5rem">
      <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:1rem">
        <div>
          <h3 style="margin-bottom:.5rem"><?= htmlspecialchars(ucfirst($sub['plan'])) ?> Plan</h3>
          <p style="margin:0;font-size:.875rem;color:var(--muted,#64748b)">
            Status: <strong><?= htmlspecialchars($sub['status']) ?></strong>
            <?php if ($sub['current_period_end']): ?>
              &nbsp;·&nbsp; <?= $isCanceling ? 'Access until' : 'Renews' ?> <strong><?= date('M j, Y', strtotime($sub['current_period_end'])) ?></strong>
            <?php endif; ?>
            <?php if ($sub['trial_ends_at'] && $sub['status'] === 'trialing'): ?>
              &nbsp;·&nbsp; Trial ends <strong><?= date('M j, Y', strtotime($sub['trial_ends_at'])) ?></strong>
            <?php endif; ?>
          </p>
        </div>
        <button class="btn btn-outline" id="open-portal">Manage billing →</button>
      </div>
    </div>
    <?php endif; ?>

    <!-- Pricing plans -->
    <h2 style="margin-bottom:1.5rem">Plans</h2>
    <div id="plans-container" class="grid-4" style="margin-bottom:2.5rem"></div>

    <!-- Payment history -->
    <h2 style="margin-bottom:1rem">Payment history</h2>
    <div class="card" style="padding:0;overflow:hidden">
      <?php if ($invoices): ?>
      <table class="invoice-table">
        <thead>
          <tr>
            <th>Date</th>
            <th>Description</th>
            <th>Amount</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($invoices as $inv): ?>
          <tr>
            <td><?= date('M j, Y', $inv['created']) ?></td>
            <td><?= htmlspecialchars($inv['lines']['data'][0]['description'] ?? 'Subscription') ?></td>
            <td><?= number_format($inv['amount_paid'] / 100, 2) ?> <?= strtoupper($inv['currency']) ?></td>
            <td><span class="badge-paid">Paid</span></td>
            <td>
              <?php if (!empty($inv['invoice_pdf'])): ?>
                <a href="<?= htmlspecialchars($inv['invoice_pdf']) ?>" target="_blank" rel="noopener" style="font-size:.8rem">PDF ↗</a>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?>
      <p style="padding:1.5rem;color:var(--muted,#64748b);margin:0">No payments yet.</p>
      <?php endif; ?>
    </div>

    <!-- Cancel zone -->
    <?php if ($isActive && !$isCanceling): ?>
    <div class="cancel-zone">
      <h4>Cancel subscription</h4>
      <p>Your subscription will remain active until the end of the current billing period. After that you will lose access to paid features.</p>
      <button class="btn btn-danger" id="cancel-btn">Cancel subscription</button>
    </div>
    <?php elseif ($isCanceling): ?>
    <div class="cancel-zone" style="border-color:#fef08a">
      <h4 style="color:#854d0e">Cancellation scheduled</h4>
      <p>Your subscription is set to cancel on <?= date('M j, Y', strtotime($sub['current_period_end'])) ?>. You will keep access until then.</p>
    </div>
    <?php endif; ?>

  </div><!-- .content -->
</main>

<script>
const currentPlan = <?= json_encode($plan) ?>;

// Load plan cards
fetch('/api/billing/plans').then(r=>r.json()).then(res=>{
  if(!res.success) return;
  const c = document.getElementById('plans-container');
  res.data.forEach(p=>{
    const isCurrent = p.id === currentPlan;
    const div = document.createElement('div');
    div.className = 'price-card' + (isCurrent ? ' featured' : '');
    div.innerHTML = `
      ${isCurrent ? '<div class="badge">Current plan</div>' : ''}
      <h3>${p.name}</h3>
      <div class="price">${p.price ? '$'+p.price+'<span>/mo</span>' : 'Custom'}</div>
      <ul>${p.features.map(f=>`<li>${f}</li>`).join('')}</ul>
      ${p.stripe_price_id && !isCurrent
        ? `<button class="btn btn-primary upgrade-btn" data-price="${p.stripe_price_id}">Upgrade</button>`
        : isCurrent ? '<button class="btn" disabled>Current plan</button>' : ''}
    `;
    c.appendChild(div);
  });

  document.querySelectorAll('.upgrade-btn').forEach(btn=>{
    btn.addEventListener('click',()=>{
      btn.disabled = true;
      btn.textContent = 'Loading…';
      fetch('/api/billing/checkout', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({price_id: btn.dataset.price})
      }).then(r=>r.json()).then(d=>{
        if(d.data?.url) location.href = d.data.url;
        else { btn.disabled=false; btn.textContent='Upgrade'; alert(d.error || 'Error'); }
      });
    });
  });
});

// Billing portal
document.getElementById('open-portal')?.addEventListener('click',()=>{
  fetch('/api/billing/portal', {method:'POST'}).then(r=>r.json()).then(d=>{
    if(d.data?.url) location.href = d.data.url;
  });
});

// Cancel subscription
document.getElementById('cancel-btn')?.addEventListener('click',()=>{
  if(!confirm('Are you sure you want to cancel? You will keep access until the end of your billing period.')) return;
  const btn = document.getElementById('cancel-btn');
  btn.disabled = true;
  btn.textContent = 'Canceling…';
  fetch('/api/billing/cancel', {method:'POST'}).then(r=>r.json()).then(d=>{
    if(d.success) location.reload();
    else { btn.disabled=false; btn.textContent='Cancel subscription'; alert(d.error || 'Error'); }
  });
});
</script>
<script src="/admin/assets/js/admin.js"></script>
</body>
</html>
