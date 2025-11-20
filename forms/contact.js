document.addEventListener('DOMContentLoaded', function () {
  const form = document.querySelector('.php-email-form');
  if (!form) return;

  const loading = form.querySelector('.loading');
  const errorEl = form.querySelector('.error-message');
  const sentEl = form.querySelector('.sent-message');

  form.addEventListener('submit', async function (e) {
    e.preventDefault();
    errorEl.textContent = '';
    sentEl.style.display = 'none';
    loading.style.display = 'block';

    const data = new FormData(form);
    try {
      const resp = await fetch(form.action, {
        method: 'POST',
        body: data,
        headers: { 'Accept': 'application/json' }
      });

      const json = await resp.json().catch(()=>({ status: 'error', message: 'Invalid server response.' }));

      if (!resp.ok || json.status === 'error') {
        loading.style.display = 'none';
        errorEl.textContent = json.message || 'Unable to send message.';
        return;
      }

      // success
      loading.style.display = 'none';
      sentEl.style.display = 'block';
      form.reset();

      // optionally hide the success message after X seconds:
      setTimeout(()=> sentEl.style.display = 'none', 8000);

    } catch (err) {
      loading.style.display = 'none';
      errorEl.textContent = 'Network error — please try again.';
      console.error(err);
    }
  });
});