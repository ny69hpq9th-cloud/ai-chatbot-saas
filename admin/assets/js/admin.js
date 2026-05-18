// Admin JS
document.addEventListener('DOMContentLoaded', function () {
  // Sidebar toggle (mobile)
  const toggle = document.querySelector('.sidebar-toggle');
  const sidebar = document.querySelector('.sidebar');
  if (toggle && sidebar) {
    toggle.addEventListener('click', () => sidebar.classList.toggle('open'));
  }

  // Chatbot create form (AJAX)
  const createForm = document.getElementById('create-bot-form');
  if (createForm) {
    createForm.addEventListener('submit', async function (e) {
      e.preventDefault();
      const btn = createForm.querySelector('button[type="submit"]');
      btn.disabled = true; btn.textContent = 'Creating…';
      const data = Object.fromEntries(new FormData(createForm).entries());
      try {
        const res = await fetch('/api/chatbots', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(data),
        });
        const json = await res.json();
        if (json.success) { location.reload(); }
        else { alert(json.message || 'Error creating chatbot'); }
      } catch { alert('Network error'); }
      btn.disabled = false; btn.textContent = 'Create chatbot';
    });
  }

  // Chatbot edit form (AJAX)
  const editForm = document.getElementById('bot-form');
  if (editForm) {
    editForm.addEventListener('submit', async function (e) {
      e.preventDefault();
      const btn = editForm.querySelector('button[type="submit"]');
      const uuid = editForm.dataset.uuid;
      btn.disabled = true; btn.textContent = 'Saving…';
      const data = Object.fromEntries(new FormData(editForm).entries());
      try {
        const res = await fetch('/api/chatbots/' + uuid, {
          method: 'PUT',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(data),
        });
        const json = await res.json();
        if (json.success) {
          btn.textContent = 'Saved!';
          setTimeout(() => { btn.textContent = 'Save changes'; btn.disabled = false; }, 2000);
        } else { alert(json.message || 'Error saving'); btn.disabled = false; btn.textContent = 'Save changes'; }
      } catch { alert('Network error'); btn.disabled = false; btn.textContent = 'Save changes'; }
    });
  }
});
