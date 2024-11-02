export const showHiddenValueInit = () => {
    const selects = document.querySelectorAll('.js-show-hidden-values');

    selects &&
        selects.forEach((item) => {
            item.addEventListener('change', (event) => {
                event.preventDefault();

                const { target } = event;

                if (!target || !(target instanceof HTMLSelectElement)) return;

                // Split data-value and data-selector into arrays
                const valuesNeeded = target.getAttribute('data-value')?.split('|') || [];
                const selectorsNeeded = target.getAttribute('data-selector')?.split('|') || [];
                let dataRequired = target.getAttribute('data-required');
                const currentValue = target.value;

                if (!dataRequired) {
                    dataRequired = 'false';
                }

                if (!valuesNeeded.length || !selectorsNeeded.length) return;

                // Show or hide each selector based on the valuesNeeded array
                selectorsNeeded.forEach((selectorString) => {
                    const selector = document.querySelector(selectorString);

                    if (!selector) return;

                    // Check if the currentValue matches any value in valuesNeeded
                    const shouldShow = valuesNeeded.includes(currentValue);

                    const input = selector.querySelector('input');
                    
                    // Toggle visibility
                    if (shouldShow) {
                        if (dataRequired === 'true' && input && input instanceof HTMLInputElement) {
                            input.setAttribute('required', 'required');
                        }

                        selector.classList.remove('d-none');
                    } else {
                        if (dataRequired === 'true' && input && input instanceof HTMLInputElement) {
                            input.removeAttribute('required');
                            input.value = '';
                        }

                        selector.classList.add('d-none');
                    }
                });
            });
        });
};
