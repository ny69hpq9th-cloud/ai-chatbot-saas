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
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<nav class="navbar">
  <a href="/" class="brand"><?= APP_NAME ?></a>
  <div class="nav-links">
    <a href="#features">Features</a>
    <a href="#pricing">Pricing</a>
    <?php if ($user): ?>
      <a href="/admin/" class="btn btn-primary">Dashboard</a>
    <?php else: ?>
      <a href="/login.php">Login</a>
      <a href="/register.php" class="btn btn-primary">Start free</a>
    <?php endif; ?>
  </div>
</nav>

<section class="hero">
  <h1>Deploy AI chatbots on your website<br><span>in under 2 minutes</span></h1>
  <p>Powered by Claude — the world's most capable AI. No coding required.</p>
  <div class="hero-actions">
    <a href="/register.php" class="btn btn-primary btn-lg">Start 14-day free trial</a>
    <a href="#demo" class="btn btn-ghost btn-lg">See live demo</a>
  </div>
</section>

<section class="features" id="features">
  <h2>Everything you need</h2>
  <div class="grid-3">
    <div class="card">
      <div class="icon">⚡</div>
      <h3>Instant setup</h3>
      <p>Paste one line of JavaScript. Your chatbot is live in seconds.</p>
    </div>
    <div class="card">
      <div class="icon">🤖</div>
      <h3>Claude-powered</h3>
      <p>Built on Anthropic Claude — the safest, most accurate AI available.</p>
    </div>
    <div class="card">
      <div class="icon">📊</div>
      <h3>Analytics</h3>
      <p>Track conversations, messages, and user satisfaction in real-time.</p>
    </div>
    <div class="card">
      <div class="icon">🎨</div>
      <h3>Custom branding</h3>
      <p>Match your brand colors, name, and personality perfectly.</p>
    </div>
    <div class="card">
      <div class="icon">🔒</div>
      <h3>Secure by default</h3>
      <p>Domain restrictions, rate limiting, and GDPR-compliant data handling.</p>
    </div>
    <div class="card">
      <div class="icon">💳</div>
      <h3>Simple billing</h3>
      <p>Transparent pricing, cancel anytime. No hidden fees.</p>
    </div>
  </div>
</section>

<section class="pricing" id="pricing">
  <h2>Simple, transparent pricing</h2>
  <div id="pricing-cards" class="grid-4">
    <div class="price-card">
      <h3>Starter</h3>
      <div class="price">$9<span>/mo</span></div>
      <ul>
        <li>1 chatbot</li><li>500 messages/mo</li><li>Basic analytics</li><li>Email support</li>
      </ul>
      <a href="/register.php?plan=starter" class="btn btn-outline">Get started</a>
    </div>
    <div class="price-card featured">
      <div class="badge">Most popular</div>
      <h3>Pro</h3>
      <div class="price">$29<span>/mo</span></div>
      <ul>
        <li>5 chatbots</li><li>5,000 messages/mo</li><li>Advanced analytics</li><li>Priority support</li><li>Custom branding</li>
      </ul>
      <a href="/register.php?plan=pro" class="btn btn-primary">Get started</a>
    </div>
    <div class="price-card">
      <h3>Business</h3>
      <div class="price">$79<span>/mo</span></div>
      <ul>
        <li>20 chatbots</li><li>25,000 messages/mo</li><li>Full analytics</li><li>Dedicated support</li><li>API access</li>
      </ul>
      <a href="/register.php?plan=business" class="btn btn-outline">Get started</a>
    </div>
    <div class="price-card">
      <h3>Enterprise</h3>
      <div class="price">Custom</div>
      <ul>
        <li>Unlimited chatbots</li><li>Unlimited messages</li><li>SLA 99.9%</li><li>Onboarding</li><li>SSO</li>
      </ul>
      <a href="mailto:sales@yourdomain.com" class="btn btn-outline">Contact us</a>
    </div>
  </div>
</section>

<footer class="footer">
  <p>&copy; <?= date('Y') ?> <?= APP_NAME ?>. All rights reserved.</p>
  <div class="footer-links">
    <a href="/privacy.php">Privacy</a>
    <a href="/terms.php">Terms</a>
    <a href="mailto:support@yourdomain.com">Support</a>
  </div>
</footer>

<script src="/assets/js/main.js"></script>
</body>
</html>
