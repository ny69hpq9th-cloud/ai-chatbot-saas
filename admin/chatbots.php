<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

$user = require_auth();
$sub  = DB::find('SELECT plan FROM subscriptions WHERE user_id=? AND status IN ("active","trialing") ORDER BY id DESC LIMIT 1', [$user['id']]);
$plan = $sub['plan'] ?? 'starter';

// Load chatbots with conversation counts
$chatbots = DB::findAll(
    'SELECT c.*, COUNT(cv.id) AS conv_count
     FROM chatbots c
     LEFT JOIN conversations cv ON cv.chatbot_id = c.id
     WHERE c.user_id = ?
     GROUP BY c.id
     ORDER BY c.created_at DESC',
    [$user['id']]
);

$planLimit = PLAN_LIMITS[$plan]['chatbots'] ?? 1;
$models    = [
    'claude-haiku-4-5-20251001' => 'Claude Haiku 4.5 — Fastest',
    'claude-sonnet-4-6'         => 'Claude Sonnet 4.6 — Balanced',
    'claude-opus-4-7'           => 'Claude Opus 4.7 — Most capable',
];

// Edit mode — load specific bot + its knowledge base
$editBot = null;
$kbItems = [];
$activeTab = $_GET['tab'] ?? 'settings';
if (!empty($_GET['edit'])) {
    $editBot = DB::find('SELECT * FROM chatbots WHERE uuid=? AND user_id=?', [$_GET['edit'], $user['id']]);
    if ($editBot) {
        $kbItems = DB::findAll(
            'SELECT id, type, title, source_url, char_count, created_at FROM knowledge_base WHERE chatbot_id=? ORDER BY id DESC',
            [$editBot['id']]
        );
    } else {
        header('Location: /admin/chatbots.php'); exit;
    }
}

$embedSnippet = $editBot
    ? '<script src="' . APP_URL . '/widget.js" data-bot="' . $editBot['uuid'] . '"></script>'
    : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $editBot ? htmlspecialchars($editBot['name']) . ' — ' : '' ?>Chatbots — <?= APP_NAME ?></title>
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

  <?php if (!$editBot): ?>
  <!-- ═══════════════════════════════════════════════════════════
       LIST VIEW
  ═══════════════════════════════════════════════════════════════ -->
  <div class="page-header">
    <div>
      <h1>Chatbots</h1>
      <p class="page-sub"><?= count($chatbots) ?> of <?= $planLimit === 9999 ? 'unlimited' : $planLimit ?> used</p>
    </div>
    <?php if (count($chatbots) < $planLimit): ?>
      <button class="btn btn-primary" id="open-drawer">+ New chatbot</button>
    <?php else: ?>
      <a href="/admin/billing.php" class="btn btn-outline">Upgrade for more</a>
    <?php endif; ?>
  </div>

  <?php if (empty($chatbots)): ?>
  <div class="empty-state">
    <span class="empty-icon">&#127916;</span>
    <p>No chatbots yet. Create your first one to get started — it only takes a minute.</p>
    <button class="btn btn-primary" id="open-drawer-2" style="margin-top:1.5rem;">Create first chatbot</button>
  </div>
  <?php else: ?>
  <div class="bots-grid">
    <?php foreach ($chatbots as $bot): ?>
    <div class="bot-card">
      <div class="bot-card-accent" style="background:<?= htmlspecialchars($bot['widget_color']) ?>"></div>
      <div class="bot-card-body">
        <div class="bot-card-header">
          <div class="bot-color-dot" style="background:<?= htmlspecialchars($bot['widget_color']) ?>"></div>
          <span class="badge <?= $bot['is_active'] ? 'badge-success' : 'badge-gray' ?>">
            <?= $bot['is_active'] ? 'Active' : 'Inactive' ?>
          </span>
        </div>
        <h3 class="bot-name"><?= htmlspecialchars($bot['name']) ?></h3>
        <?php if ($bot['description']): ?>
          <p class="bot-desc"><?= htmlspecialchars(mb_substr($bot['description'], 0, 80)) ?><?= mb_strlen($bot['description']) > 80 ? '…' : '' ?></p>
        <?php endif; ?>
        <div class="bot-meta">
          <span class="bot-meta-item">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            <?= number_format((int)$bot['conv_count']) ?> conversations
          </span>
          <span class="bot-meta-item">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            <?= date('M j', strtotime($bot['created_at'])) ?>
          </span>
        </div>
      </div>
      <div class="bot-card-footer">
        <a href="/admin/chatbots.php?edit=<?= urlencode($bot['uuid']) ?>" class="btn btn-sm btn-ghost">Edit</a>
        <a href="/admin/chatbots.php?edit=<?= urlencode($bot['uuid']) ?>&tab=knowledge" class="btn btn-sm btn-ghost">Knowledge</a>
        <a href="/admin/chatbots.php?edit=<?= urlencode($bot['uuid']) ?>&tab=embed" class="btn btn-sm btn-outline">Embed</a>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- ── Create drawer ──────────────────────────────────────── -->
  <div class="drawer-overlay" id="drawer-overlay"></div>
  <aside class="drawer" id="create-drawer">
    <div class="drawer-header">
      <h2>New chatbot</h2>
      <button class="drawer-close" id="close-drawer" aria-label="Close">&#10005;</button>
    </div>
    <div class="drawer-body">
      <form id="create-bot-form">
        <div class="form-group">
          <label class="form-label">Name <span class="req">*</span></label>
          <input type="text" name="name" class="form-control" placeholder="Customer support bot" required maxlength="150">
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <input type="text" name="description" class="form-control" placeholder="Short description (optional)" maxlength="255">
        </div>
        <div class="form-group">
          <label class="form-label">Welcome message</label>
          <input type="text" name="welcome_message" class="form-control" value="Hi! How can I help you today?" maxlength="255">
        </div>
        <div class="form-group">
          <label class="form-label">System prompt</label>
          <textarea name="system_prompt" class="form-control" rows="4" placeholder="You are a helpful assistant for [company]. Answer questions about..."></textarea>
        </div>
        <div class="form-row">
          <div class="form-group" style="flex:1">
            <label class="form-label">Model</label>
            <select name="model" class="form-control">
              <?php foreach ($models as $val => $label): ?>
                <option value="<?= $val ?>" <?= $val === 'claude-sonnet-4-6' ? 'selected' : '' ?>><?= $label ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group" style="flex:0 0 80px">
            <label class="form-label">Color</label>
            <input type="color" name="widget_color" class="form-control color-input" value="#00D4FF">
          </div>
        </div>
        <div id="create-error" class="alert alert-error" style="display:none"></div>
        <button type="submit" class="btn btn-primary btn-block" id="create-btn">
          <span>Create chatbot</span>
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
        </button>
      </form>
    </div>
  </aside>

  <?php else: ?>
  <!-- ═══════════════════════════════════════════════════════════
       EDIT VIEW — with tabs
  ═══════════════════════════════════════════════════════════════ -->
  <div class="page-header">
    <div>
      <a href="/admin/chatbots.php" class="breadcrumb">← Chatbots</a>
      <h1><?= htmlspecialchars($editBot['name']) ?></h1>
    </div>
    <button class="btn btn-danger btn-sm" id="delete-bot-btn" data-uuid="<?= $editBot['uuid'] ?>">Delete bot</button>
  </div>

  <!-- Tab bar -->
  <div class="tab-bar">
    <button class="tab-btn <?= $activeTab === 'settings'   ? 'active' : '' ?>" data-tab="settings">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
      Settings
    </button>
    <button class="tab-btn <?= $activeTab === 'knowledge'  ? 'active' : '' ?>" data-tab="knowledge">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
      Knowledge base
      <?php if ($kbItems): ?>
        <span class="tab-count"><?= count($kbItems) ?></span>
      <?php endif; ?>
    </button>
    <button class="tab-btn <?= $activeTab === 'embed'      ? 'active' : '' ?>" data-tab="embed">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
      Embed code
    </button>
  </div>

  <!-- ── Settings tab ─────────────────────────────────────── -->
  <div class="tab-pane <?= $activeTab === 'settings' ? 'active' : '' ?>" id="tab-settings">
    <div class="card">
      <form id="edit-bot-form" data-uuid="<?= $editBot['uuid'] ?>">
        <div class="form-grid-2">
          <div class="form-group">
            <label class="form-label">Name <span class="req">*</span></label>
            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($editBot['name']) ?>" required maxlength="150">
          </div>
          <div class="form-group">
            <label class="form-label">Model</label>
            <select name="model" class="form-control">
              <?php foreach ($models as $val => $label): ?>
                <option value="<?= $val ?>" <?= $editBot['model'] === $val ? 'selected' : '' ?>><?= $label ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group fg-span-2">
            <label class="form-label">Description</label>
            <input type="text" name="description" class="form-control" value="<?= htmlspecialchars($editBot['description'] ?? '') ?>" maxlength="255">
          </div>
          <div class="form-group fg-span-2">
            <label class="form-label">System prompt</label>
            <textarea name="system_prompt" class="form-control" rows="5"><?= htmlspecialchars($editBot['system_prompt'] ?? '') ?></textarea>
            <p class="form-hint">Instructions that define the bot's personality and expertise. The knowledge base is injected automatically after this.</p>
          </div>
          <div class="form-group">
            <label class="form-label">Welcome message</label>
            <input type="text" name="welcome_message" class="form-control" value="<?= htmlspecialchars($editBot['welcome_message'] ?? '') ?>" maxlength="255">
          </div>
          <div class="form-group">
            <label class="form-label">Widget position</label>
            <select name="widget_position" class="form-control">
              <option value="bottom-right" <?= ($editBot['widget_position'] ?? '') === 'bottom-right' ? 'selected' : '' ?>>Bottom right</option>
              <option value="bottom-left"  <?= ($editBot['widget_position'] ?? '') === 'bottom-left'  ? 'selected' : '' ?>>Bottom left</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Widget color</label>
            <div class="color-row">
              <input type="color" name="widget_color" class="color-input" value="<?= htmlspecialchars($editBot['widget_color']) ?>">
              <input type="text" name="widget_color_hex" class="form-control" value="<?= htmlspecialchars($editBot['widget_color']) ?>" maxlength="7" placeholder="#000000" style="font-family:monospace">
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Temperature <span class="form-hint-inline">(0 = precise, 1 = creative)</span></label>
            <input type="number" name="temperature" class="form-control" min="0" max="1" step="0.05" value="<?= $editBot['temperature'] ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Max tokens per reply</label>
            <input type="number" name="max_tokens" class="form-control" min="64" max="4096" value="<?= $editBot['max_tokens'] ?>">
          </div>
          <div class="form-group fg-span-2">
            <label class="form-label">Allowed domains <span class="form-hint-inline">(comma-separated, leave blank for all)</span></label>
            <input type="text" name="allowed_domains" class="form-control" placeholder="example.com, app.example.com" value="<?= htmlspecialchars($editBot['allowed_domains'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Status</label>
            <label class="toggle-label">
              <input type="checkbox" name="is_active" id="is-active-toggle" value="1" <?= $editBot['is_active'] ? 'checked' : '' ?>>
              <span class="toggle-track"><span class="toggle-thumb"></span></span>
              <span id="active-label"><?= $editBot['is_active'] ? 'Active' : 'Inactive' ?></span>
            </label>
          </div>
        </div>

        <div class="form-actions">
          <div id="edit-error"   class="alert alert-error"   style="display:none"></div>
          <div id="edit-success" class="alert alert-success" style="display:none">&#10003; Changes saved.</div>
          <div style="display:flex;gap:.75rem;align-items:center">
            <button type="submit" class="btn btn-primary" id="save-btn">Save changes</button>
            <a href="/admin/chatbots.php" class="btn btn-ghost">Cancel</a>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- ── Knowledge base tab ───────────────────────────────── -->
  <div class="tab-pane <?= $activeTab === 'knowledge' ? 'active' : '' ?>" id="tab-knowledge">

    <div class="kb-columns">
      <!-- Left: add sources -->
      <div class="kb-forms">
        <div class="card">
          <div class="card-header"><h2>Add text</h2></div>
          <form id="kb-text-form" data-uuid="<?= $editBot['uuid'] ?>">
            <div class="form-group">
              <label class="form-label">Title</label>
              <input type="text" name="title" class="form-control" placeholder="e.g. About us, FAQ, Pricing…">
            </div>
            <div class="form-group">
              <label class="form-label">Content <span class="req">*</span></label>
              <textarea name="content" class="form-control" rows="8" placeholder="Paste or type any text your chatbot should know about…" required></textarea>
            </div>
            <div id="kb-text-error" class="alert alert-error" style="display:none"></div>
            <button type="submit" class="btn btn-primary btn-block" id="kb-text-btn">Add to knowledge base</button>
          </form>
        </div>

        <div class="card" style="margin-top:1.25rem">
          <div class="card-header"><h2>Scrape a webpage</h2></div>
          <form id="kb-url-form" data-uuid="<?= $editBot['uuid'] ?>">
            <div class="form-group">
              <label class="form-label">URL <span class="req">*</span></label>
              <input type="url" name="url" class="form-control" placeholder="https://yoursite.com/about" required>
            </div>
            <p class="form-hint" style="margin-bottom:1rem">We'll fetch the page, strip navigation/footer noise, and extract the main text content.</p>
            <div id="kb-url-error" class="alert alert-error" style="display:none"></div>
            <button type="submit" class="btn btn-outline btn-block" id="kb-url-btn">Fetch &amp; add page</button>
          </form>
        </div>
      </div>

      <!-- Right: existing items -->
      <div class="kb-list-col">
        <div class="card">
          <div class="card-header">
            <h2>Knowledge base</h2>
            <span class="badge badge-gray" id="kb-count-badge"><?= count($kbItems) ?> items</span>
          </div>
          <div id="kb-items-list">
            <?php if (empty($kbItems)): ?>
            <div class="kb-empty" id="kb-empty">
              <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="opacity:.3"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
              <p>No items yet. Add text or scrape a URL to get started.</p>
            </div>
            <?php else: ?>
            <?php foreach ($kbItems as $item): ?>
            <div class="kb-item" id="kb-item-<?= $item['id'] ?>" data-id="<?= $item['id'] ?>">
              <div class="kb-item-icon <?= $item['type'] === 'url' ? 'kb-icon-url' : 'kb-icon-text' ?>">
                <?php if ($item['type'] === 'url'): ?>
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                <?php else: ?>
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                <?php endif; ?>
              </div>
              <div class="kb-item-info">
                <div class="kb-item-title"><?= htmlspecialchars($item['title'] ?: 'Untitled') ?></div>
                <div class="kb-item-meta">
                  <?php if ($item['source_url']): ?>
                    <a href="<?= htmlspecialchars($item['source_url']) ?>" target="_blank" class="kb-item-url"><?= htmlspecialchars(parse_url($item['source_url'], PHP_URL_HOST) ?: $item['source_url']) ?></a>
                    <span class="kb-dot">·</span>
                  <?php endif; ?>
                  <?= number_format($item['char_count']) ?> chars
                  <span class="kb-dot">·</span>
                  <?= date('M j', strtotime($item['created_at'])) ?>
                </div>
              </div>
              <button class="kb-delete-btn" data-id="<?= $item['id'] ?>" aria-label="Delete">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
              </button>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

  </div>

  <!-- ── Embed code tab ───────────────────────────────────── -->
  <div class="tab-pane <?= $activeTab === 'embed' ? 'active' : '' ?>" id="tab-embed">
    <div class="embed-grid">

      <div class="card">
        <div class="card-header"><h2>Installation snippet</h2></div>
        <p class="form-hint" style="margin-bottom:1.25rem">
          Paste this tag just before the closing <code>&lt;/body&gt;</code> tag on every page where you want the chatbot to appear.
        </p>
        <div class="code-wrap">
          <div class="code-lang">HTML</div>
          <pre class="code-block" id="embed-snippet"><?= htmlspecialchars($embedSnippet) ?></pre>
          <button class="copy-btn" id="copy-snippet-btn" data-target="embed-snippet">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
            Copy
          </button>
        </div>
      </div>

      <div class="card">
        <div class="card-header"><h2>How to install</h2></div>
        <ol class="install-steps">
          <li>
            <span class="step-num">1</span>
            <div>
              <strong>Copy the snippet</strong>
              <p>Click the Copy button above to copy the embed code.</p>
            </div>
          </li>
          <li>
            <span class="step-num">2</span>
            <div>
              <strong>Paste before <code>&lt;/body&gt;</code></strong>
              <p>Open your site's HTML or CMS template and paste the snippet just before the closing body tag.</p>
            </div>
          </li>
          <li>
            <span class="step-num">3</span>
            <div>
              <strong>That's it!</strong>
              <p>The chat widget will appear in the bottom <?= $editBot['widget_position'] ?? 'right' ?> corner of every page. No other setup needed.</p>
            </div>
          </li>
        </ol>
      </div>

      <div class="card">
        <div class="card-header"><h2>WordPress / page builders</h2></div>
        <div class="code-wrap" style="margin-bottom:1rem">
          <div class="code-lang">PHP</div>
          <pre class="code-block" id="wp-snippet"><?= htmlspecialchars("<?php\nadd_action('wp_footer', function() { ?>\n  {$embedSnippet}\n<?php });") ?></pre>
          <button class="copy-btn" data-target="wp-snippet">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
            Copy
          </button>
        </div>
        <p class="form-hint">Paste this in your theme's <code>functions.php</code>, or use a code snippet plugin like WPCode.</p>
      </div>

      <div class="card">
        <div class="card-header"><h2>Bot details</h2></div>
        <dl class="detail-list">
          <div class="detail-row">
            <dt>Bot UUID</dt>
            <dd><code><?= $editBot['uuid'] ?></code></dd>
          </div>
          <div class="detail-row">
            <dt>Model</dt>
            <dd><code><?= htmlspecialchars($editBot['model']) ?></code></dd>
          </div>
          <div class="detail-row">
            <dt>Status</dt>
            <dd><span class="badge <?= $editBot['is_active'] ? 'badge-success' : 'badge-gray' ?>"><?= $editBot['is_active'] ? 'Active' : 'Inactive' ?></span></dd>
          </div>
          <div class="detail-row">
            <dt>Widget color</dt>
            <dd><span class="color-swatch" style="background:<?= htmlspecialchars($editBot['widget_color']) ?>"></span> <?= htmlspecialchars($editBot['widget_color']) ?></dd>
          </div>
        </dl>
      </div>

    </div>
  </div>

  <?php endif; ?>
  </div><!-- .content -->
</main>

<!-- ── Page data for JS ─────────────────────────────────────── -->
<script>
window.CHATBOTS_PAGE = {
  editUuid: <?= json_encode($editBot['uuid'] ?? null) ?>,
  activeTab: <?= json_encode($activeTab) ?>,
};
</script>
<script src="/admin/assets/js/admin.js"></script>
</body>
</html>
