document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('academic-programs-filtersorting');
    const results = document.getElementById('studyfinder-results');

    if (!form || !results) {
        return;
    }
    const updateResults = async () => {
        const formData = new FormData(form);

        form.classList.add('is-loading');

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error(`Server Error: ${response.status}`);
            }

            const html = await response.text();

            const virtualDocument = new DOMParser().parseFromString(
                html,
                'text/html'
            );

            const returnedResults = virtualDocument.querySelector(
                '#studyfinder-results'
            );

            if (returnedResults) {
                results.replaceWith(returnedResults);
            }
        } catch (error) {
            console.error(
                'Academic programs AJAX filter failed:',
                error
            );
        } finally {
            form.classList.remove('is-loading');
        }
    };

  form.querySelectorAll('select').forEach((select) => {

    select.addEventListener('change', () => {
      updateResults();
    });
  });
});
