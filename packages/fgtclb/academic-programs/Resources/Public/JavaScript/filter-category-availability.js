document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('studyfinder');

    if (!form) {
        return;
    }

    const updateAvailableCategories = async () => {
        const formData = new FormData(form);

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

            const returnedSelects = virtualDocument.querySelectorAll(
                '#studyfinder select'
            );

            returnedSelects.forEach((returnedSelect) => {
                const currentSelect = form.querySelector(
                    `#${CSS.escape(returnedSelect.id)}`
                );

                if (!currentSelect) {
                    return;
                }

                const currentValue = currentSelect.value;

                currentSelect.replaceChildren(
                    ...Array.from(returnedSelect.options).map((option) =>
                        option.cloneNode(true)
                    )
                );

                if (
                    Array.from(currentSelect.options).some(
                        (option) => option.value === currentValue
                    )
                ) {
                    currentSelect.value = currentValue;
                }
            });
        } catch (error) {
            console.error(
                'Studyfinder category availability failed:',
                error
            );
        }
    };

    form.querySelectorAll('select').forEach((select) => {
        select.addEventListener('change', updateAvailableCategories);
    });
});
