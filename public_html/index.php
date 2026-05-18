<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';
$user = auth_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= APP_NAME ?> — AI Chatbots for Your Website</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

<!-- ── Navbar ─────────────────────────────────────────────── -->
<nav class="navbar">
  <a href="/" class="brand"><?= APP_NAME ?></a>
  <div class="nav-links">
    <a href="#features">Features</a>
    <a href="#pricing">Pricing</a>
    <?php if ($user): ?>
      <a href="/admin/" class="btn btn-primary">Dashboard →</a>
    <?php else: ?>
      <a href="/login.php">Login</a>
      <a href="/register.php" class="btn btn-primary">Start free</a>
    <?php endif; ?>
  </div>
</nav>

<!-- ── Hero ───────────────────────────────────────────────── -->
<section class="hero">
  <div class="hero-eyebrow">Powered by Anthropic Claude</div>

  <h1>
    Deploy AI chatbots<br>
    <span class="grad-text">in under 2 minutes</span>
  </h1>

  <p>
    Add a fully customizable AI assistant to any website with a single line of code.
    No backend, no training, no complexity.
  </p>

  <div class="hero-actions">
    <a href="/register.php" class="btn btn-primary btn-lg">Start 14-day free trial</a>
    <a href="#features" class="btn btn-ghost btn-lg">See how it works</a>
  </div>

  <div class="hero-stats">
    <div class="hero-stat">
      <strong>2 min</strong>
      <span>Setup time</span>
    </div>
    <div class="hero-stat">
      <strong>99.9%</strong>
      <span>Uptime SLA</span>
    </div>
    <div class="hero-stat">
      <strong>1 line</strong>
      <span>To embed</span>
    </div>
    <div class="hero-stat">
      <strong>14 days</strong>
      <span>Free trial</span>
    </div>
  </div>
</section>

<hr class="glow-divider">

<!-- ── Features ───────────────────────────────────────────── -->
<section class="features" id="features">
  <span class="section-label">Capabilities</span>
  <h2>Everything you need to <span class="grad-text">ship faster</span></h2>
  <p class="sub-heading">From a single embed snippet to enterprise-grade analytics — all included.</p>

  <div class="grid-3">
    <div class="card reveal">
      <span class="icon">⚡</span>
      <h3>Instant setup</h3>
      <p>Paste one line of JavaScript. Your chatbot is live in seconds. No backend configuration required.</p>
    </div>
    <div class="card reveal reveal-delay-1">
      <span class="icon">🤖</span>
      <h3>Claude-powered</h3>
      <p>Built on Anthropic Claude — the safest, most accurate AI available. State-of-the-art reasoning.</p>
    </div>
    <div class="card reveal reveal-delay-2">
      <span class="icon">📊</span>
      <h3>Analytics</h3>
      <p>Track conversations, messages, and user satisfaction in real-time with a beautiful dashboard.</p>
    </div>
    <div class="card reveal reveal-delay-3">
      <span class="icon">🎨</span>
      <h3>Custom branding</h3>
      <p>Match your brand colors, name, and personality perfectly. Full widget customization.</p>
    </div>
    <div class="card reveal reveal-delay-4">
      <span class="icon">🔒</span>
      <h3>Secure by default</h3>
      <p>Domain restrictions, rate limiting, and GDPR-compliant data handling out of the box.</p>
    </div>
    <div class="card reveal reveal-delay-5">
      <span class="icon">💳</span>
      <h3>Simple billing</h3>
      <p>Transparent pricing, cancel anytime. No hidden fees, no per-seat nonsense.</p>
    </div>
  </div>
</section>

<hr class="glow-divider">

<!-- ── Pricing ─────────────────────────────────────────────── -->
<section class="pricing" id="pricing">
  <span class="section-label">Pricing</span>
  <h2>Simple, <span class="grad-text">transparent</span> pricing</h2>
  <p class="sub-heading">Start free for 14 days. No credit card required. Cancel anytime.</p>

  <div class="grid-4">
    <div class="price-card reveal">
      <h3>Starter</h3>
      <div class="price">$9<span>/mo</span></div>
      <ul>
        <li>1 chatbot</li>
        <li>500 messages/mo</li>
        <li>Basic analytics</li>
        <li>Email support</li>
      </ul>
      <a href="/register.php?plan=starter" class="btn btn-outline btn-block">Get started</a>
    </div>

    <div class="price-card featured reveal reveal-delay-1">
      <div class="badge">Most popular</div>
      <h3>Pro</h3>
      <div class="price">$29<span>/mo</span></div>
      <ul>
        <li>5 chatbots</li>
        <li>5,000 messages/mo</li>
        <li>Advanced analytics</li>
        <li>Priority support</li>
        <li>Custom branding</li>
      </ul>
      <a href="/register.php?plan=pro" class="btn btn-primary btn-block">Get started</a>
    </div>

    <div class="price-card reveal reveal-delay-2">
      <h3>Business</h3>
      <div class="price">$79<span>/mo</span></div>
      <ul>
        <li>20 chatbots</li>
        <li>25,000 messages/mo</li>
        <li>Full analytics</li>
        <li>Dedicated support</li>
        <li>API access</li>
      </ul>
      <a href="/register.php?plan=business" class="btn btn-outline btn-block">Get started</a>
    </div>

    <div class="price-card reveal reveal-delay-3">
      <h3>Enterprise</h3>
      <div class="price">Custom</div>
      <ul>
        <li>Unlimited chatbots</li>
        <li>Unlimited messages</li>
        <li>SLA 99.9%</li>
        <li>Onboarding</li>
        <li>SSO</li>
      </ul>
      <a href="mailto:sales@yourdomain.com" class="btn btn-outline btn-block">Contact us</a>
    </div>
  </div>
</section>

<!-- ── Footer ─────────────────────────────────────────────── -->
<footer class="footer">
  <p>&copy; <?= date('Y') ?> <?= APP_NAME ?>. All rights reserved.</p>
  <div class="footer-links">
    <a href="/privacy.php">Privacy</a>
    <a href="/terms.php">Terms</a>
    <a href="mailto:support@yourdomain.com">Support</a>
  </div>
</footer>

<script src="/assets/js/main.js"></script>
<script>
// Scroll reveal
const observer = new IntersectionObserver((entries) => {
  entries.forEach(el => {
    if (el.isIntersecting) {
      el.target.classList.add('visible');
      observer.unobserve(el.target);
    }
  });
}, { threshold: 0.12 });

document.querySelectorAll('.reveal').forEach(el => observer.observe(el));
</script>
</body>
</html>
