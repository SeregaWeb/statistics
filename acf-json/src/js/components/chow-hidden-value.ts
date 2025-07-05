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
                // eslint-disable-next-line @wordpress/no-unused-vars-before-return
                const revertLogic = target.getAttribute('data-revertlogic');
                const currentValue = target.value;

                if (!dataRequired) {
                    dataRequired = 'false';
                }

                if (!valuesNeeded.length || !selectorsNeeded.length) return;

                // Show or hide each selector based on the valuesNeeded array
                selectorsNeeded.forEach((selectorString) => {
                    const selector = document.querySelector(selectorString);
                    // @ts-ignore
                    // eslint-disable-next-line @wordpress/no-unused-vars-before-return
                    const selectorRevertLogic = document.querySelector(revertLogic);
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

                        if (selectorRevertLogic) {
                            selectorRevertLogic.classList.add('d-none');
                        }
                    } else {
                        if (dataRequired === 'true' && input && input instanceof HTMLInputElement) {
                            input.removeAttribute('required');
                            input.value = '';
                        }

                        selector.classList.add('d-none');

                        if (selectorRevertLogic) {
                            selectorRevertLogic.classList.remove('d-none');
                        }
                    }
                });
            });
        });
};

export const disabledValuesInSelectInit = () => {
    // Find the select element
    const selectElement = document.querySelector('.js-blocked-value');

    if (selectElement) {
        // @ts-ignore
        const blockedValues = selectElement.dataset.blocked.split('|');
        // @ts-ignore
        const blockedCurrentValues = selectElement.dataset.blockedCurrent.split('|');
        // @ts-ignore
        const targetSelector = selectElement.dataset.blockedSelector;
        const targetSelect = document.querySelector(`.${targetSelector}`);

        // Function to update disabled status
        // eslint-disable-next-line no-inner-declarations
        function updateDisabledOptions() {
            // Get the current selected value
            if (!selectElement) return;
            // @ts-ignore
            const selectedValue = selectElement.value;
            if (!targetSelect) return;

            // Check if selected value is one of the blocked current values
            if (blockedCurrentValues.includes(selectedValue)) {
                // @ts-ignore
                // Disable options in the target select that match blockedValues
                Array.from(targetSelect.options).forEach((option) => {
                    // @ts-ignore
                    // eslint-disable-next-line no-param-reassign
                    option.disabled = blockedValues.includes(option.value);
                });
            } else {
                // @ts-ignore
                // Enable all options if condition is not met
                Array.from(targetSelect.options).forEach((option) => {
                    // @ts-ignore
                    // eslint-disable-next-line no-param-reassign
                    option.disabled = false;
                });
            }
        }

        // Attach event listener to handle changes in select
        selectElement.addEventListener('change', updateDisabledOptions);

        // Initial call to set the correct state
        updateDisabledOptions();
    }
};
