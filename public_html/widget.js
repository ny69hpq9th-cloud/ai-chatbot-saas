/**
 * AI Chatbot Platform — Embed Widget  v2.0
 * ─────────────────────────────────────────
 * Usage: <script src="https://domain.com/widget.js?id=BOT_UUID"></script>
 *
 * Runs entirely inside a Shadow DOM — zero CSS conflicts with the host page.
 * Session ID persisted in localStorage so the conversation survives page reloads.
 */
(function () {
  'use strict';

  /* ── 1. Locate this script tag ────────────────────────────────────────────── */
  var ME = document.currentScript || (function () {
    var s = document.querySelectorAll('script');
    return s[s.length - 1];
  }());

  if (!ME) return;

  var scriptUrl = new URL(ME.src || location.href);
  var BOT_UUID  = scriptUrl.searchParams.get('id') || ME.getAttribute('data-bot');
  var API_ROOT  = scriptUrl.origin + '/api';

  if (!BOT_UUID) {
    console.warn('[ChatWidget] Missing ?id= parameter in script src.');
    return;
  }

  /* ── 2. Session ID (localStorage — survives page reloads) ─────────────────── */
  var SK = 'aichat_s_' + BOT_UUID;  // session key
  var WK = 'aichat_w_' + BOT_UUID;  // welcomed flag

  var sessionId = localStorage.getItem(SK);
  if (!sessionId) {
    sessionId = uid();
    localStorage.setItem(SK, sessionId);
  }

  /* ── 3. App state ─────────────────────────────────────────────────────────── */
  var st = { open: false, busy: false };

  /* ── 4. Fetch public bot config, then mount ───────────────────────────────── */
  fetch(API_ROOT + '/widget/' + BOT_UUID + '/config', { credentials: 'omit' })
    .then(function (r) { return r.json(); })
    .then(function (j) { mount(j.success ? j.data : fallback()); })
    .catch(function ()  { mount(fallback()); });

  function fallback() {
    return {
      name:            'AI Assistant',
      welcome_message: 'Hi! How can I help you today?',
      widget_color:    '#00D4FF',
      widget_position: 'bottom-right',
    };
  }

  /* ── 5. Mount: create Shadow DOM and inject everything ────────────────────── */
  function mount(cfg) {
    var color    = /^#[0-9a-fA-F]{6}$/.test(cfg.widget_color) ? cfg.widget_color : '#00D4FF';
    var isLeft   = cfg.widget_position === 'bottom-left';
    var iconClr  = perceivedBrightness(color) > 145 ? '#000' : '#fff';

    // Inject Google Font into the main document (fonts are shared across shadow roots)
    if (!document.getElementById('aichat-gf')) {
      var lk = document.createElement('link');
      lk.id  = 'aichat-gf';
      lk.rel = 'stylesheet';
      lk.href = 'https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700;800&display=swap';
      document.head.appendChild(lk);
    }

    // Shadow host — full-viewport fixed overlay
    var host = document.createElement('div');
    host.style.cssText = 'all:initial;position:fixed;inset:0;z-index:2147483647;pointer-events:none;';
    document.body.appendChild(host);

    var shadow = host.attachShadow({ mode: 'open' });

    // Style
    var styleEl = document.createElement('style');
    styleEl.textContent = buildCSS(color, iconClr, isLeft);
    shadow.appendChild(styleEl);

    // HTML
    var root = document.createElement('div');
    root.id  = 'root';
    root.innerHTML = buildHTML(cfg, color);
    shadow.appendChild(root);

    // Wire interactions
    wire(shadow, cfg, color);
  }

  /* ── 6. CSS (injected into Shadow DOM) ───────────────────────────────────── */
  function buildCSS(c, ic, left) {
    var side = left ? 'left' : 'right';
    return [
      '*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}',

      /* ── FAB ─────────────────────────────────────────────── */
      '#fab{',
        'position:fixed;' + side + ':24px;bottom:24px;',
        'width:58px;height:58px;border-radius:50%;',
        'background:' + c + ';border:none;cursor:pointer;pointer-events:all;',
        'display:flex;align-items:center;justify-content:center;',
        'box-shadow:0 4px 22px ' + hex2rgba(c,.55) + ',0 2px 8px rgba(0,0,0,.5);',
        'transition:transform .18s,box-shadow .18s;',
        'outline:none;',
      '}',
      '#fab:hover{transform:scale(1.1);box-shadow:0 6px 30px ' + hex2rgba(c,.75) + ',0 2px 8px rgba(0,0,0,.5)}',
      '#fab:focus-visible{outline:2px solid ' + c + ';outline-offset:3px}',
      '#fab svg{width:26px;height:26px;position:absolute;transition:opacity .16s,transform .16s}',
      '#fab .ic-chat {opacity:1;transform:scale(1) rotate(0deg)}',
      '#fab .ic-x    {opacity:0;transform:scale(.5) rotate(-60deg)}',
      '#fab.open .ic-chat{opacity:0;transform:scale(.5) rotate(60deg)}',
      '#fab.open .ic-x  {opacity:1;transform:scale(1) rotate(0deg)}',

      /* Unread badge */
      '#badge{',
        'position:absolute;top:-2px;' + (left ? 'right' : 'right') + ':-2px;',
        'min-width:18px;height:18px;border-radius:9px;',
        'background:#EF4444;color:#fff;font-size:.65rem;font-weight:800;',
        'display:none;align-items:center;justify-content:center;padding:0 4px;',
        'font-family:"Space Grotesk",system-ui,sans-serif;',
        'box-shadow:0 2px 6px rgba(0,0,0,.4);pointer-events:none;',
      '}',
      '#badge.show{display:flex}',

      /* ── Panel ───────────────────────────────────────────── */
      '#panel{',
        'position:fixed;' + side + ':24px;bottom:96px;',
        'width:375px;height:580px;',
        'background:rgba(4,4,12,.97);',
        'border:1px solid rgba(255,255,255,.09);',
        'border-radius:22px;',
        'box-shadow:0 24px 80px rgba(0,0,0,.8),0 0 0 1px ' + hex2rgba(c,.12) + ';',
        'display:flex;flex-direction:column;overflow:hidden;',
        'pointer-events:none;opacity:0;',
        'transform:translateY(20px) scale(.95);',
        'transition:opacity .22s,transform .24s cubic-bezier(.4,0,.2,1);',
      '}',
      '#panel.open{pointer-events:all;opacity:1;transform:translateY(0) scale(1)}',

      /* Subtle grid overlay on panel */
      '#panel::before{',
        'content:"";position:absolute;inset:0;border-radius:22px;pointer-events:none;z-index:0;',
        'background-image:',
          'linear-gradient(' + hex2rgba(c,.04) + ' 1px,transparent 1px),',
          'linear-gradient(90deg,' + hex2rgba(c,.04) + ' 1px,transparent 1px);',
        'background-size:44px 44px;',
      '}',

      /* Gradient line at top of panel */
      '#panel::after{',
        'content:"";position:absolute;top:0;left:0;right:0;height:1px;z-index:2;',
        'background:linear-gradient(90deg,transparent,' + c + ',' + hex2rgba(c,.4) + ',transparent);',
      '}',

      /* ── Header ──────────────────────────────────────────── */
      '#hdr{',
        'display:flex;align-items:center;gap:.7rem;',
        'padding:.9rem 1rem;',
        'border-bottom:1px solid rgba(255,255,255,.07);',
        'background:rgba(255,255,255,.025);',
        'position:relative;z-index:1;flex-shrink:0;',
      '}',
      '#avatar{',
        'width:40px;height:40px;border-radius:50%;',
        'background:linear-gradient(135deg,' + c + ',#8B5CF6);',
        'display:flex;align-items:center;justify-content:center;',
        'font-family:"Space Grotesk",system-ui,sans-serif;',
        'font-size:1rem;font-weight:800;color:#000;flex-shrink:0;',
        'box-shadow:0 2px 12px ' + hex2rgba(c,.4) + ';',
      '}',
      '#info{flex:1;min-width:0}',
      '#bot-name{',
        'font-family:"Space Grotesk",system-ui,sans-serif;',
        'font-size:.9rem;font-weight:700;color:#fff;',
        'white-space:nowrap;overflow:hidden;text-overflow:ellipsis;',
      '}',
      '#status{',
        'font-family:"Space Grotesk",system-ui,sans-serif;',
        'font-size:.68rem;color:#4ade80;',
        'display:flex;align-items:center;gap:.3rem;margin-top:.1rem;',
      '}',
      '#status::before{',
        'content:"";width:6px;height:6px;background:#4ade80;border-radius:50%;flex-shrink:0;',
        'box-shadow:0 0 6px #4ade8088;',
      '}',
      '#close-btn{',
        'background:none;border:none;cursor:pointer;',
        'color:rgba(255,255,255,.3);padding:.3rem;border-radius:7px;',
        'display:flex;align-items:center;justify-content:center;',
        'transition:color .12s,background .12s;flex-shrink:0;outline:none;',
      '}',
      '#close-btn:hover{color:#fff;background:rgba(255,255,255,.08)}',
      '#close-btn:focus-visible{outline:2px solid ' + c + '}',

      /* ── Messages ────────────────────────────────────────── */
      '#msgs{',
        'flex:1;overflow-y:auto;padding:.85rem .85rem .4rem;',
        'display:flex;flex-direction:column;gap:.5rem;',
        'position:relative;z-index:1;',
        'scroll-behavior:smooth;',
      '}',
      '#msgs::-webkit-scrollbar{width:3px}',
      '#msgs::-webkit-scrollbar-thumb{background:rgba(255,255,255,.12);border-radius:4px}',
      '#msgs::-webkit-scrollbar-track{background:transparent}',

      '.msg{display:flex;flex-direction:column;max-width:86%}',
      '.msg.u{align-self:flex-end;align-items:flex-end}',
      '.msg.b{align-self:flex-start;align-items:flex-start}',

      '.bubble{',
        'padding:.55rem .9rem;border-radius:17px;',
        'font-family:"Space Grotesk",system-ui,sans-serif;',
        'font-size:.855rem;line-height:1.57;word-break:break-word;white-space:pre-wrap;',
      '}',
      '.msg.u .bubble{',
        'background:' + c + ';color:' + (perceivedBrightness(c) > 145 ? '#000' : '#fff') + ';',
        'font-weight:600;border-bottom-right-radius:4px;',
      '}',
      '.msg.b .bubble{',
        'background:rgba(255,255,255,.07);color:rgba(255,255,255,.9);',
        'border:1px solid rgba(255,255,255,.09);border-bottom-left-radius:4px;',
      '}',
      '.ts{',
        'font-size:.62rem;color:rgba(255,255,255,.2);',
        'margin-top:.2rem;padding:0 .15rem;',
        'font-family:"Space Grotesk",system-ui,sans-serif;',
      '}',

      /* Typing indicator */
      '.typing .bubble{display:flex;align-items:center;gap:4px;padding:.55rem .9rem;min-width:58px}',
      '@keyframes wdot{',
        '0%,60%,100%{transform:translateY(0);opacity:.3}',
        '30%{transform:translateY(-5px);opacity:1}',
      '}',
      '.dot{',
        'width:6px;height:6px;border-radius:50%;',
        'background:rgba(255,255,255,.45);',
        'animation:wdot 1.1s ease-in-out infinite;',
      '}',
      '.dot:nth-child(2){animation-delay:.18s}',
      '.dot:nth-child(3){animation-delay:.36s}',

      /* Date separator */
      '.date-sep{',
        'align-self:center;',
        'font-family:"Space Grotesk",system-ui,sans-serif;',
        'font-size:.65rem;font-weight:600;letter-spacing:.06em;text-transform:uppercase;',
        'color:rgba(255,255,255,.2);margin:.3rem 0;',
      '}',

      /* ── Footer ──────────────────────────────────────────── */
      '#ftr{',
        'display:flex;align-items:flex-end;gap:.45rem;',
        'padding:.7rem .85rem;',
        'border-top:1px solid rgba(255,255,255,.07);',
        'background:rgba(255,255,255,.02);',
        'position:relative;z-index:1;flex-shrink:0;',
      '}',
      '#inp{',
        'flex:1;background:rgba(255,255,255,.06);',
        'border:1px solid rgba(255,255,255,.1);border-radius:13px;',
        'padding:.55rem .9rem;',
        'font-family:"Space Grotesk",system-ui,sans-serif;font-size:.855rem;color:#fff;',
        'resize:none;outline:none;min-height:38px;max-height:96px;',
        'line-height:1.5;overflow-y:auto;',
        'transition:border-color .15s,background .15s;',
      '}',
      '#inp::placeholder{color:rgba(255,255,255,.22)}',
      '#inp:focus{border-color:' + hex2rgba(c,.55) + ';background:rgba(255,255,255,.08)}',
      '#inp:disabled{opacity:.35;cursor:not-allowed}',

      '#send{',
        'width:38px;height:38px;border-radius:12px;',
        'background:' + c + ';border:none;cursor:pointer;outline:none;',
        'display:flex;align-items:center;justify-content:center;',
        'flex-shrink:0;transition:transform .15s,box-shadow .15s;',
        'box-shadow:0 2px 12px ' + hex2rgba(c,.4) + ';',
      '}',
      '#send:hover{transform:scale(1.09);box-shadow:0 4px 18px ' + hex2rgba(c,.65) + '}',
      '#send:focus-visible{outline:2px solid ' + c + ';outline-offset:2px}',
      '#send:disabled{opacity:.3;cursor:not-allowed;transform:none;box-shadow:none}',
      '#send svg{width:16px;height:16px}',

      /* ── Branding ────────────────────────────────────────── */
      '#brand{',
        'text-align:center;font-size:.6rem;',
        'color:rgba(255,255,255,.1);padding:.2rem 0 .4rem;',
        'font-family:"Space Grotesk",system-ui,sans-serif;',
        'position:relative;z-index:1;flex-shrink:0;',
      '}',
      '#brand a{color:rgba(255,255,255,.12);text-decoration:none}',
      '#brand a:hover{color:rgba(255,255,255,.28)}',

      /* ── Mobile: full screen panel ───────────────────────── */
      '@media(max-width:480px){',
        '#panel{left:0!important;right:0!important;bottom:0!important;',
          'width:100%!important;height:100%!important;',
          'border-radius:0;border-left:none;border-right:none;border-bottom:none}',
        '#fab{' + side + ':16px!important;bottom:16px!important}',
      '}',
    ].join('');
  }

  /* ── 7. HTML template ─────────────────────────────────────────────────────── */
  function buildHTML(cfg, color) {
    var init = ((cfg.name || 'A')[0]).toUpperCase();
    var ic   = perceivedBrightness(color) > 145 ? '#000' : '#fff';
    return (
      '<button id="fab" aria-label="Open chat" aria-haspopup="dialog" aria-expanded="false">' +
        '<svg class="ic-chat" viewBox="0 0 24 24" fill="none"' +
            ' stroke="' + ic + '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
          '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>' +
        '</svg>' +
        '<svg class="ic-x" viewBox="0 0 24 24" fill="none"' +
            ' stroke="' + ic + '" stroke-width="2.5" stroke-linecap="round">' +
          '<line x1="18" y1="6" x2="6" y2="18"/>' +
          '<line x1="6" y1="6" x2="18" y2="18"/>' +
        '</svg>' +
        '<span id="badge" aria-hidden="true">1</span>' +
      '</button>' +

      '<div id="panel" role="dialog" aria-modal="true" aria-label="' + escA(cfg.name) + ' chat" aria-live="polite">' +
        '<div id="hdr">' +
          '<div id="avatar">' + init + '</div>' +
          '<div id="info">' +
            '<div id="bot-name">' + esc(cfg.name || 'AI Assistant') + '</div>' +
            '<div id="status">Online</div>' +
          '</div>' +
          '<button id="close-btn" aria-label="Close chat">' +
            '<svg width="15" height="15" viewBox="0 0 24 24" fill="none"' +
                ' stroke="currentColor" stroke-width="2.5" stroke-linecap="round">' +
              '<line x1="18" y1="6" x2="6" y2="18"/>' +
              '<line x1="6" y1="6" x2="18" y2="18"/>' +
            '</svg>' +
          '</button>' +
        '</div>' +

        '<div id="msgs" role="log" aria-label="Chat messages"></div>' +

        '<div id="ftr">' +
          '<textarea id="inp" placeholder="Ask me anything…" rows="1" aria-label="Your message"></textarea>' +
          '<button id="send" aria-label="Send message">' +
            '<svg viewBox="0 0 24 24" fill="none"' +
                ' stroke="' + ic + '" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">' +
              '<line x1="22" y1="2" x2="11" y2="13"/>' +
              '<polygon points="22 2 15 22 11 13 2 9 22 2" fill="' + ic + '"/>' +
            '</svg>' +
          '</button>' +
        '</div>' +

        '<div id="brand">Powered by <a href="#" target="_blank" rel="noopener">AI Chatbot Platform</a></div>' +
      '</div>'
    );
  }

  /* ── 8. Wire all interactions ─────────────────────────────────────────────── */
  function wire(shadow, cfg) {
    var fab      = shadow.getElementById('fab');
    var panel    = shadow.getElementById('panel');
    var badge    = shadow.getElementById('badge');
    var closeBtn = shadow.getElementById('close-btn');
    var msgs     = shadow.getElementById('msgs');
    var inp      = shadow.getElementById('inp');
    var send     = shadow.getElementById('send');

    /* Open / close */
    fab.addEventListener('click', toggle);
    closeBtn.addEventListener('click', close);

    /* Escape key closes panel */
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && st.open) close();
    });

    /* Send on button click */
    send.addEventListener('click', doSend);

    /* Send on Enter (Shift+Enter = new line) */
    inp.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); doSend(); }
    });

    /* Auto-resize textarea */
    inp.addEventListener('input', function () {
      inp.style.height = 'auto';
      inp.style.height = Math.min(inp.scrollHeight, 96) + 'px';
    });

    /* ── toggle ── */
    function toggle() {
      st.open ? close() : open();
    }

    function open() {
      st.open = true;
      fab.classList.add('open');
      fab.setAttribute('aria-expanded', 'true');
      panel.classList.add('open');
      badge.classList.remove('show');

      // Show welcome message on first-ever open
      if (!localStorage.getItem(WK) && msgs.children.length === 0) {
        localStorage.setItem(WK, '1');
        addDateSep(msgs, 'Today');
        addBotMsg(msgs, cfg.welcome_message || 'Hi! How can I help you today?');
      }

      setTimeout(function () { inp.focus(); }, 220);
      scrollDown(msgs);
    }

    function close() {
      st.open = false;
      fab.classList.remove('open');
      fab.setAttribute('aria-expanded', 'false');
      panel.classList.remove('open');
    }

    /* ── send message ── */
    function doSend() {
      var text = inp.value.trim();
      if (!text || st.busy) return;

      inp.value = '';
      inp.style.height = 'auto';
      setLoading(true);

      addUserMsg(msgs, text);
      var typingEl = addTyping(msgs);
      scrollDown(msgs);

      fetch(API_ROOT + '/chat/' + BOT_UUID + '/message', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({
          message:    text,
          session_id: sessionId,
          page_url:   location.href,
        }),
      })
        .then(function (r) { return r.json(); })
        .then(function (j) {
          typingEl.remove();
          addBotMsg(msgs, j.success ? j.data.message
            : (j.message || 'Sorry, something went wrong. Please try again.'));
        })
        .catch(function () {
          typingEl.remove();
          addBotMsg(msgs, 'Connection error — please check your internet and try again.');
        })
        .finally(function () {
          setLoading(false);
          scrollDown(msgs);
          inp.focus();
        });
    }

    function setLoading(v) {
      st.busy       = v;
      inp.disabled  = v;
      send.disabled = v;
    }
  }

  /* ── 9. DOM helpers ───────────────────────────────────────────────────────── */
  function addUserMsg(container, text) {
    return addMsg(container, 'u', text);
  }

  function addBotMsg(container, text) {
    return addMsg(container, 'b', text);
  }

  function addMsg(container, role, text) {
    var wrap   = document.createElement('div');
    wrap.className = 'msg ' + role;

    var bubble = document.createElement('div');
    bubble.className = 'bubble';
    bubble.textContent = text;

    var ts = document.createElement('div');
    ts.className = 'ts';
    ts.textContent = fmtTime();

    wrap.appendChild(bubble);
    wrap.appendChild(ts);
    container.appendChild(wrap);
    return wrap;
  }

  function addTyping(container) {
    var wrap = document.createElement('div');
    wrap.className = 'msg b typing';
    wrap.innerHTML = '<div class="bubble">' +
      '<span class="dot"></span><span class="dot"></span><span class="dot"></span>' +
      '</div>';
    container.appendChild(wrap);
    return wrap;
  }

  function addDateSep(container, label) {
    var d = document.createElement('div');
    d.className = 'date-sep';
    d.textContent = label;
    container.appendChild(d);
    return d;
  }

  function scrollDown(el) {
    requestAnimationFrame(function () { el.scrollTop = el.scrollHeight; });
  }

  /* ── 10. Utility functions ────────────────────────────────────────────────── */
  function fmtTime() {
    var n = new Date();
    return ('0' + n.getHours()).slice(-2) + ':' + ('0' + n.getMinutes()).slice(-2);
  }

  function uid() {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
      var r = Math.random() * 16 | 0;
      return (c === 'x' ? r : (r & 3 | 8)).toString(16);
    });
  }

  /** Perceived brightness 0-255 (W3C formula) */
  function perceivedBrightness(hex) {
    var r = parseInt(hex.slice(1, 3), 16) || 0;
    var g = parseInt(hex.slice(3, 5), 16) || 0;
    var b = parseInt(hex.slice(5, 7), 16) || 0;
    return (r * 299 + g * 587 + b * 114) / 1000;
  }

  /** Hex → rgba() string */
  function hex2rgba(hex, a) {
    var r = parseInt(hex.slice(1, 3), 16) || 0;
    var g = parseInt(hex.slice(3, 5), 16) || 0;
    var b = parseInt(hex.slice(5, 7), 16) || 0;
    return 'rgba(' + r + ',' + g + ',' + b + ',' + a + ')';
  }

  /** HTML-escape for text nodes */
  function esc(s) {
    var d = document.createElement('div');
    d.textContent = String(s);
    return d.innerHTML;
  }

  /** Attribute-safe escape */
  function escA(s) {
    return String(s).replace(/"/g, '&quot;').replace(/'/g, '&#39;');
  }

}());
