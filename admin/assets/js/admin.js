/* Admin SPA helpers — chatbots, knowledge base, tabs */
(function () {
  'use strict';

  var PAGE = window.CHATBOTS_PAGE || {};

  /* ── Utility ──────────────────────────────────────────────── */
  function qs(sel, ctx) { return (ctx || document).querySelector(sel); }
  function on(el, ev, fn) { if (el) el.addEventListener(ev, fn); }

  function showAlert(el, msg) {
    if (!el) return;
    el.textContent = msg;
    el.style.display = 'flex';
  }
  function hideAlert(el) {
    if (!el) return;
    el.style.display = 'none';
  }

  function setLoading(btn, loading, label) {
    if (!btn) return;
    btn.disabled = loading;
    btn.querySelector('span') ? (btn.querySelector('span').textContent = label)
                              : (btn.textContent = label);
  }

  async function api(method, path, body) {
    var opts = {
      method: method,
      headers: { 'Content-Type': 'application/json' },
    };
    if (body) opts.body = JSON.stringify(body);
    var res  = await fetch('/api' + path, opts);
    var json = await res.json();
    return json;
  }

  /* ── Mobile sidebar toggle ────────────────────────────────── */
  var sidebarToggle = qs('#sidebar-toggle');
  var sidebar       = qs('#sidebar');
  on(sidebarToggle, 'click', function () { sidebar && sidebar.classList.toggle('open'); });
  document.addEventListener('click', function (e) {
    if (sidebar && !sidebar.contains(e.target) && e.target !== sidebarToggle) {
      sidebar.classList.remove('open');
    }
  });

  /* ── Create-bot drawer ────────────────────────────────────── */
  var overlay     = qs('#drawer-overlay');
  var drawer      = qs('#create-drawer');

  function openDrawer()  { drawer && drawer.classList.add('open'); overlay && overlay.classList.add('open'); }
  function closeDrawer() { drawer && drawer.classList.remove('open'); overlay && overlay.classList.remove('open'); }

  on(qs('#open-drawer'),   'click', openDrawer);
  on(qs('#open-drawer-2'), 'click', openDrawer);
  on(qs('#close-drawer'),  'click', closeDrawer);
  on(overlay,              'click', closeDrawer);

  /* ── Create bot form ──────────────────────────────────────── */
  var createForm = qs('#create-bot-form');
  on(createForm, 'submit', async function (e) {
    e.preventDefault();
    var btn = qs('#create-btn');
    var err = qs('#create-error');
    hideAlert(err);
    setLoading(btn, true, 'Creating…');

    var data = Object.fromEntries(new FormData(createForm));
    try {
      var json = await api('POST', '/chatbots', data);
      if (json.success) {
        window.location.href = '/admin/chatbots.php?edit=' + json.data.uuid;
      } else {
        showAlert(err, json.message || 'Failed to create chatbot.');
        setLoading(btn, false, 'Create chatbot');
      }
    } catch {
      showAlert(err, 'Network error — please try again.');
      setLoading(btn, false, 'Create chatbot');
    }
  });

  /* ── Tab switching ────────────────────────────────────────── */
  document.querySelectorAll('.tab-btn').forEach(function (btn) {
    on(btn, 'click', function () {
      var target = btn.dataset.tab;
      document.querySelectorAll('.tab-btn').forEach(function (b) { b.classList.remove('active'); });
      document.querySelectorAll('.tab-pane').forEach(function (p) { p.classList.remove('active'); });
      btn.classList.add('active');
      var pane = qs('#tab-' + target);
      if (pane) pane.classList.add('active');
      // Update URL without reload
      var url = new URL(window.location.href);
      url.searchParams.set('tab', target);
      history.replaceState(null, '', url.toString());
    });
  });

  /* ── Edit bot form ────────────────────────────────────────── */
  var editForm = qs('#edit-bot-form');
  on(editForm, 'submit', async function (e) {
    e.preventDefault();
    var btn     = qs('#save-btn');
    var errEl   = qs('#edit-error');
    var succEl  = qs('#edit-success');
    var uuid    = editForm.dataset.uuid;
    hideAlert(errEl); hideAlert(succEl);
    setLoading(btn, true, 'Saving…');

    var data = Object.fromEntries(new FormData(editForm));
    // Normalise checkbox
    data.is_active = qs('#is-active-toggle') && qs('#is-active-toggle').checked ? 1 : 0;
    // Sync hex to color
    if (data.widget_color_hex && /^#[0-9a-fA-F]{6}$/.test(data.widget_color_hex)) {
      data.widget_color = data.widget_color_hex;
    }
    delete data.widget_color_hex;

    try {
      var json = await api('PUT', '/chatbots/' + uuid, data);
      if (json.success) {
        showAlert(succEl, '✓ Changes saved.');
        setTimeout(function () { hideAlert(succEl); }, 3000);
      } else {
        showAlert(errEl, json.message || 'Save failed.');
      }
    } catch {
      showAlert(errEl, 'Network error.');
    }
    setLoading(btn, false, 'Save changes');
  });

  /* ── Color picker ↔ hex input sync ───────────────────────── */
  var colorInput = qs('input[name="widget_color"]');
  var hexInput   = qs('input[name="widget_color_hex"]');
  on(colorInput, 'input', function () { if (hexInput) hexInput.value = colorInput.value; });
  on(hexInput,   'input', function () {
    if (/^#[0-9a-fA-F]{6}$/.test(hexInput.value)) {
      colorInput.value = hexInput.value;
    }
  });

  /* ── Active toggle label ──────────────────────────────────── */
  var activeToggle = qs('#is-active-toggle');
  var activeLabel  = qs('#active-label');
  on(activeToggle, 'change', function () {
    if (activeLabel) activeLabel.textContent = activeToggle.checked ? 'Active' : 'Inactive';
  });

  /* ── Delete bot ───────────────────────────────────────────── */
  var deleteBtn = qs('#delete-bot-btn');
  on(deleteBtn, 'click', async function () {
    var uuid = deleteBtn.dataset.uuid;
    if (!confirm('Delete this chatbot and all its conversations? This cannot be undone.')) return;
    deleteBtn.disabled = true; deleteBtn.textContent = 'Deleting…';
    try {
      var json = await api('DELETE', '/chatbots/' + uuid);
      if (json.success) {
        window.location.href = '/admin/chatbots.php';
      } else {
        alert(json.message || 'Delete failed.');
        deleteBtn.disabled = false; deleteBtn.textContent = 'Delete bot';
      }
    } catch {
      alert('Network error.');
      deleteBtn.disabled = false; deleteBtn.textContent = 'Delete bot';
    }
  });

  /* ── KB: add text ─────────────────────────────────────────── */
  var kbTextForm = qs('#kb-text-form');
  on(kbTextForm, 'submit', async function (e) {
    e.preventDefault();
    var btn  = qs('#kb-text-btn');
    var err  = qs('#kb-text-error');
    var uuid = kbTextForm.dataset.uuid;
    hideAlert(err);
    var origLabel = btn.textContent;
    btn.disabled = true; btn.textContent = 'Adding…';

    var data = Object.fromEntries(new FormData(kbTextForm));
    try {
      var json = await api('POST', '/chatbots/' + uuid + '/knowledge', data);
      if (json.success) {
        prependKbItem(json.data);
        kbTextForm.reset();
      } else {
        showAlert(err, json.message || 'Failed to add item.');
      }
    } catch {
      showAlert(err, 'Network error.');
    }
    btn.disabled = false; btn.textContent = origLabel;
  });

  /* ── KB: scrape URL ───────────────────────────────────────── */
  var kbUrlForm = qs('#kb-url-form');
  on(kbUrlForm, 'submit', async function (e) {
    e.preventDefault();
    var btn  = qs('#kb-url-btn');
    var err  = qs('#kb-url-error');
    var uuid = kbUrlForm.dataset.uuid;
    hideAlert(err);
    btn.disabled = true; btn.textContent = 'Fetching page…';

    var data = Object.fromEntries(new FormData(kbUrlForm));
    try {
      var json = await api('POST', '/chatbots/' + uuid + '/knowledge/scrape', data);
      if (json.success) {
        prependKbItem(json.data);
        kbUrlForm.reset();
      } else {
        showAlert(err, json.message || 'Failed to fetch URL.');
      }
    } catch {
      showAlert(err, 'Network error.');
    }
    btn.disabled = false; btn.textContent = 'Fetch & add page';
  });

  /* ── KB: delete item ──────────────────────────────────────── */
  document.addEventListener('click', async function (e) {
    var btn = e.target.closest('.kb-delete-btn');
    if (!btn) return;
    if (!confirm('Remove this item from the knowledge base?')) return;
    var id   = btn.dataset.id;
    var uuid = PAGE.editUuid;
    btn.disabled = true;
    try {
      var json = await api('DELETE', '/chatbots/' + uuid + '/knowledge/' + id);
      if (json.success) {
        var el = qs('#kb-item-' + id);
        if (el) {
          el.style.opacity = '0';
          setTimeout(function () {
            el.remove();
            updateKbCount(-1);
            if (!qs('.kb-item')) showKbEmpty();
          }, 250);
        }
      } else {
        btn.disabled = false;
        alert(json.message || 'Delete failed.');
      }
    } catch {
      btn.disabled = false;
      alert('Network error.');
    }
  });

  /* ── KB helpers ───────────────────────────────────────────── */
  function prependKbItem(item) {
    var list  = qs('#kb-items-list');
    var empty = qs('#kb-empty');
    if (!list) return;
    if (empty) { empty.remove(); }

    var isUrl    = item.type === 'url';
    var hostname = '';
    if (item.source_url) {
      try { hostname = new URL(item.source_url).hostname; } catch { hostname = item.source_url; }
    }

    var div = document.createElement('div');
    div.className = 'kb-item';
    div.id        = 'kb-item-' + item.id;
    div.dataset.id = item.id;
    div.innerHTML = [
      '<div class="kb-item-icon ' + (isUrl ? 'kb-icon-url' : 'kb-icon-text') + '">',
        isUrl
          ? '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>'
          : '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>',
      '</div>',
      '<div class="kb-item-info">',
        '<div class="kb-item-title">' + escHtml(item.title || 'Untitled') + '</div>',
        '<div class="kb-item-meta">',
          (hostname ? '<span class="kb-item-url">' + escHtml(hostname) + '</span><span class="kb-dot">·</span>' : ''),
          formatNum(item.char_count) + ' chars',
          '<span class="kb-dot">·</span>just now',
        '</div>',
      '</div>',
      '<button class="kb-delete-btn" data-id="' + item.id + '" aria-label="Delete">',
        '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>',
      '</button>',
    ].join('');

    list.insertBefore(div, list.firstChild);
    updateKbCount(1);
  }

  function updateKbCount(delta) {
    var badge = qs('#kb-count-badge');
    var tabCount = document.querySelector('.tab-btn[data-tab="knowledge"] .tab-count');
    if (badge) {
      var n = parseInt(badge.textContent) + delta;
      badge.textContent = n + ' item' + (n === 1 ? '' : 's');
    }
    if (tabCount) {
      var n2 = parseInt(tabCount.textContent) + delta;
      tabCount.textContent = n2;
      if (n2 <= 0) tabCount.remove();
    }
  }

  function showKbEmpty() {
    var list = qs('#kb-items-list');
    if (!list) return;
    var div = document.createElement('div');
    div.className = 'kb-empty';
    div.id = 'kb-empty';
    div.innerHTML = '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="opacity:.3"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg><p>No items yet.</p>';
    list.appendChild(div);
  }

  function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  function formatNum(n) {
    return Number(n).toLocaleString();
  }

  /* ── Copy buttons ─────────────────────────────────────────── */
  document.querySelectorAll('.copy-btn').forEach(function (btn) {
    on(btn, 'click', function () {
      var target = qs('#' + btn.dataset.target);
      if (!target) return;
      navigator.clipboard.writeText(target.textContent.trim()).then(function () {
        btn.textContent = '✓ Copied!';
        btn.classList.add('copied');
        setTimeout(function () {
          btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg> Copy';
          btn.classList.remove('copied');
        }, 2500);
      });
    });
  });

})();
