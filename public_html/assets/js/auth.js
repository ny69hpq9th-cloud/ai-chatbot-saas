/* Auth pages — password toggle, strength meter, real-time validation */
(function () {
  'use strict';

  /* ── Password visibility toggle ──────────────────────────────── */
  document.querySelectorAll('.toggle-pw').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var targetId = btn.dataset.target;
      var input = document.getElementById(targetId);
      if (!input) return;
      var isHidden = input.type === 'password';
      input.type = isHidden ? 'text' : 'password';
      btn.setAttribute('aria-label', isHidden ? 'Hide' : 'Show');
      btn.querySelector('svg').innerHTML = isHidden
        ? '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94" stroke="currentColor" stroke-width="2" fill="none"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19" stroke="currentColor" stroke-width="2" fill="none"/><line x1="1" y1="1" x2="23" y2="23" stroke="currentColor" stroke-width="2"/>'
        : '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor" stroke-width="2" fill="none"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" fill="none"/>';
    });
  });

  /* ── Password strength meter ──────────────────────────────────── */
  var pwInput  = document.getElementById('password');
  var pwFill   = document.getElementById('pw-fill');
  var pwLabel  = document.getElementById('pw-label');

  function scorePassword(pw) {
    var score = 0;
    if (!pw) return 0;
    if (pw.length >= 8)  score++;
    if (pw.length >= 12) score++;
    if (/[0-9]/.test(pw))    score++;
    if (/[a-z]/.test(pw) && /[A-Z]/.test(pw)) score++;
    if (/[^A-Za-z0-9]/.test(pw)) score++;
    return score;
  }

  var levels = [
    { label: '',       color: 'transparent',            pct: 0   },
    { label: 'Weak',   color: '#EF4444',                pct: 25  },
    { label: 'Fair',   color: '#F97316',                pct: 50  },
    { label: 'Good',   color: '#EAB308',                pct: 75  },
    { label: 'Strong', color: 'linear-gradient(90deg,#00D4FF,#8B5CF6)', pct: 100 },
  ];

  if (pwInput && pwFill && pwLabel) {
    pwInput.addEventListener('input', function () {
      var score = Math.min(scorePassword(pwInput.value), 4);
      var lvl = levels[score];
      pwFill.style.width      = lvl.pct + '%';
      pwFill.style.background = lvl.color;
      pwLabel.textContent     = lvl.label;
      pwLabel.style.color     = score >= 4 ? '#00D4FF' :
                                score >= 3 ? '#EAB308' :
                                score >= 2 ? '#F97316' :
                                score >= 1 ? '#EF4444' : '#505050';
    });
  }

  /* ── Live confirm-password match ─────────────────────────────── */
  var confirmInput = document.getElementById('password_confirm');

  function checkMatch() {
    if (!pwInput || !confirmInput || !confirmInput.value) return;
    var match = pwInput.value === confirmInput.value;
    confirmInput.classList.toggle('is-error', !match);
    confirmInput.classList.toggle('is-valid', match);
  }

  if (confirmInput) {
    confirmInput.addEventListener('input', checkMatch);
    if (pwInput) pwInput.addEventListener('input', checkMatch);
  }

  /* ── Submit loading state ─────────────────────────────────────── */
  ['register-form', 'login-form'].forEach(function (id) {
    var form = document.getElementById(id);
    if (!form) return;
    form.addEventListener('submit', function () {
      var btn = form.querySelector('button[type="submit"]');
      if (!btn) return;
      btn.disabled = true;
      var span = btn.querySelector('span');
      if (span) span.textContent = 'Please wait…';
    });
  });

})();
