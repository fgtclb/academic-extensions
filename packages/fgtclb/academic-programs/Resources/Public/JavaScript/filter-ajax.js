document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('studyfinder');

  if (!form) return;

  const targetUrl = form.action;
  const results = document.getElementById('studyfinder-results');
  if (!results) return;
  form.querySelectorAll('select').forEach(select => {
    select.addEventListener('change', async() => {
      const formData = new FormData(form);

      form.classList.add('is-loading');

      try {
        const response = await fetch(targetUrl, {
          method: 'POST',
          body: formData,
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        });

        if (!response.ok) {
          throw new Error(`Server Error: ${response.status}`);
        }

        const htmlContent = await response.text();

        const parser = new DOMParser();
        const virtualDocument = parser.parseFromString(
          htmlContent,
          'text/html'
        );

        const filteredResults = virtualDocument.querySelector(
          '#studyfinder-results'
        );

        if (filteredResults) {
          results.innerHTML = filteredResults.innerHTML;
        }

      } catch (error) {
        console.error('Fehler beim AJAX-Filter:', error);
      } finally {
        form.classList.remove('is-loading');
      }
    });
  });
})
