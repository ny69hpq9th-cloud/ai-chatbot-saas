/**
 * AI Chatbot Platform — Embed Widget
 * Usage: <script src="https://yourdomain.com/widget.js" data-bot="CHATBOT_UUID"></script>
 */
(function () {
  'use strict';

  const script = document.currentScript || (function () {
    const scripts = document.getElementsByTagName('script');
    return scripts[scripts.length - 1];
  })();

  const BOT_UUID   = script.getAttribute('data-bot');
  const API_BASE   = script.getAttribute('data-api') || script.src.replace('/widget.js', '/api');
  const POSITION   = script.getAttribute('data-position') || 'bottom-right';
  const COLOR      = script.getAttribute('data-color') || '#6366f1';

  if (!BOT_UUID) { console.warn('[AI Chatbot] data-bot attribute is required'); return; }

  // Session ID (persisted in sessionStorage)
  let sessionId = sessionStorage.getItem('aichat_sid_' + BOT_UUID);
  if (!sessionId) {
    sessionId = 'sess_' + Math.random().toString(36).slice(2) + Date.now().toString(36);
    sessionStorage.setItem('aichat_sid_' + BOT_UUID, sessionId);
  }

  // ── Styles ─────────────────────────────────────────────────────────────────
  const css = `
  #aichat-btn{position:fixed;${POSITION.includes('right') ? 'right:24px' : 'left:24px'};bottom:24px;
    width:56px;height:56px;border-radius:50%;background:${COLOR};border:none;cursor:pointer;
    box-shadow:0 4px 20px rgba(0,0,0,.25);z-index:99998;display:flex;align-items:center;justify-content:center;
    transition:transform .2s;}
  #aichat-btn:hover{transform:scale(1.08);}
  #aichat-btn svg{fill:#fff;width:26px;height:26px;}
  #aichat-window{position:fixed;${POSITION.includes('right') ? 'right:24px' : 'left:24px'};bottom:92px;
    width:360px;max-width:calc(100vw - 32px);height:520px;max-height:calc(100vh - 120px);
    background:#fff;border-radius:14px;box-shadow:0 8px 40px rgba(0,0,0,.18);
    display:flex;flex-direction:column;z-index:99999;overflow:hidden;
    transform:scale(0) translateY(20px);transform-origin:bottom ${POSITION.includes('right') ? 'right' : 'left'};
    transition:transform .2s cubic-bezier(.34,1.56,.64,1),opacity .2s;opacity:0;}
  #aichat-window.open{transform:scale(1) translateY(0);opacity:1;}
  #aichat-header{background:${COLOR};color:#fff;padding:14px 16px;display:flex;align-items:center;gap:10px;
    font-family:system-ui,sans-serif;}
  #aichat-header .avatar{width:36px;height:36px;border-radius:50%;background:#ffffff33;display:flex;
    align-items:center;justify-content:center;font-size:18px;}
  #aichat-header .info{flex:1;}
  #aichat-header .name{font-weight:600;font-size:.95rem;}
  #aichat-header .status{font-size:.75rem;opacity:.8;}
  #aichat-header .close{background:none;border:none;color:#fff;cursor:pointer;font-size:1.2rem;opacity:.8;padding:4px;}
  #aichat-header .close:hover{opacity:1;}
  #aichat-messages{flex:1;overflow-y:auto;padding:16px;display:flex;flex-direction:column;gap:12px;
    font-family:system-ui,sans-serif;font-size:.9rem;}
  .aichat-msg{max-width:80%;padding:10px 14px;border-radius:14px;line-height:1.5;word-break:break-word;}
  .aichat-msg.user{background:${COLOR};color:#fff;align-self:flex-end;border-bottom-right-radius:4px;}
  .aichat-msg.assistant{background:#f3f4f6;color:#1f2937;align-self:flex-start;border-bottom-left-radius:4px;}
  .aichat-msg.typing{opacity:.6;}
  #aichat-input-area{border-top:1px solid #e5e7eb;padding:12px;display:flex;gap:8px;background:#fff;}
  #aichat-input{flex:1;border:1px solid #e5e7eb;border-radius:8px;padding:9px 13px;font-size:.9rem;
    outline:none;resize:none;font-family:system-ui,sans-serif;max-height:100px;}
  #aichat-input:focus{border-color:${COLOR};}
  #aichat-send{background:${COLOR};color:#fff;border:none;border-radius:8px;padding:9px 14px;cursor:pointer;
    font-size:.9rem;font-weight:500;transition:opacity .15s;}
  #aichat-send:hover{opacity:.9;}
  #aichat-send:disabled{opacity:.5;cursor:not-allowed;}
  `;

  const style = document.createElement('style');
  style.textContent = css;
  document.head.appendChild(style);

  // ── DOM ────────────────────────────────────────────────────────────────────
  const btn = document.createElement('button');
  btn.id = 'aichat-btn';
  btn.setAttribute('aria-label', 'Open chat');
  btn.innerHTML = `<svg viewBox="0 0 24 24"><path d="M20 2H4a2 2 0 0 0-2 2v18l4-4h14a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2z"/></svg>`;

  const win = document.createElement('div');
  win.id = 'aichat-window';
  win.setAttribute('role', 'dialog');
  win.setAttribute('aria-label', 'Chat window');
  win.innerHTML = `
    <div id="aichat-header">
      <div class="avatar">🤖</div>
      <div class="info">
        <div class="name">AI Assistant</div>
        <div class="status">Online</div>
      </div>
      <button class="close" aria-label="Close chat">✕</button>
    </div>
    <div id="aichat-messages" role="log" aria-live="polite"></div>
    <div id="aichat-input-area">
      <textarea id="aichat-input" rows="1" placeholder="Type a message…"></textarea>
      <button id="aichat-send">Send</button>
    </div>`;

  document.body.appendChild(btn);
  document.body.appendChild(win);

  const msgs   = win.querySelector('#aichat-messages');
  const input  = win.querySelector('#aichat-input');
  const sendBtn = win.querySelector('#aichat-send');

  // ── State ──────────────────────────────────────────────────────────────────
  let isOpen    = false;
  let isLoading = false;
  let welcomed  = false;

  function toggle() {
    isOpen = !isOpen;
    win.classList.toggle('open', isOpen);
    btn.setAttribute('aria-expanded', String(isOpen));
    if (isOpen && !welcomed) {
      welcomed = true;
      loadHistory();
    }
    if (isOpen) input.focus();
  }

  function appendMsg(role, text, id) {
    const el = document.createElement('div');
    el.className = 'aichat-msg ' + role;
    if (id) el.id = id;
    el.textContent = text;
    msgs.appendChild(el);
    msgs.scrollTop = msgs.scrollHeight;
    return el;
  }

  function loadHistory() {
    fetch(`${API_BASE}/chat/${BOT_UUID}/history?session_id=${encodeURIComponent(sessionId)}`)
      .then(r => r.json())
      .then(res => {
        if (!res.success || !res.data.length) {
          appendMsg('assistant', 'Hi! How can I help you today?');
          return;
        }
        res.data.forEach(m => { if (m.role !== 'system') appendMsg(m.role, m.content); });
      })
      .catch(() => appendMsg('assistant', 'Hi! How can I help you today?'));
  }

  async function sendMessage() {
    const text = input.value.trim();
    if (!text || isLoading) return;

    input.value = '';
    input.style.height = 'auto';
    appendMsg('user', text);

    const typingId = 'typing_' + Date.now();
    const typing   = appendMsg('assistant', '…', typingId);
    typing.classList.add('typing');
    isLoading = true;
    sendBtn.disabled = true;

    try {
      const res = await fetch(`${API_BASE}/chat/${BOT_UUID}/message`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message: text, session_id: sessionId, page_url: location.href }),
      });
      const data = await res.json();
      typing.remove();
      if (data.success) {
        appendMsg('assistant', data.data.message);
      } else {
        appendMsg('assistant', data.message || 'Sorry, something went wrong.');
      }
    } catch (e) {
      typing.remove();
      appendMsg('assistant', 'Connection error. Please try again.');
    } finally {
      isLoading = false;
      sendBtn.disabled = false;
      input.focus();
    }
  }

  // ── Events ─────────────────────────────────────────────────────────────────
  btn.addEventListener('click', toggle);
  win.querySelector('.close').addEventListener('click', toggle);
  sendBtn.addEventListener('click', sendMessage);
  input.addEventListener('keydown', e => {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
  });
  input.addEventListener('input', function () {
    this.style.height = 'auto';
    this.style.height = Math.min(this.scrollHeight, 100) + 'px';
  });
})();
