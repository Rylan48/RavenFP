document.addEventListener('DOMContentLoaded', function () {
  const form = document.querySelector('.contact-form');

  if (!form) return;

  const loading = form.querySelector('.loading');
  const errorEl = form.querySelector('.error-message');
  const sentEl = form.querySelector('.sent-message');
  const button = form.querySelector('button[type="submit"]');

  form.addEventListener('submit', async function (e) {
    e.preventDefault();

    // Reset messages
    errorEl.textContent = '';
    errorEl.style.display = 'none';

    sentEl.style.display = 'none';
    loading.style.display = 'block';

    button.disabled = true;
    button.textContent = 'Sending...';

    try {
      const response = await fetch(form.action, {
        method: 'POST',
        body: new FormData(form),
        headers: {
          'Accept': 'application/json'
        }
      });

      const result = await response.json();

      if (!response.ok || result.status !== 'success') {
        throw new Error(
          result.message || 'Unable to send message.'
        );
      }

      // Success
      loading.style.display = 'none';
      sentEl.style.display = 'block';

      form.reset();

    } catch (error) {
      loading.style.display = 'none';

      errorEl.textContent =
        error.message || 'Unable to send message.';

      errorEl.style.display = 'block';

      console.error('Contact form error:', error);

    } finally {
      button.disabled = false;
      button.textContent = 'Send Message';
    }
  });
});