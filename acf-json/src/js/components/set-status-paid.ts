export const setStatusPaid = (): void => {
    const selectElementAll = document.querySelectorAll<HTMLSelectElement>('.js-select-status-factoring');
    selectElementAll.forEach((item) => {
        item.addEventListener('change', (event) => {
            const target = event.target as HTMLSelectElement;
            const selectedValue = target.value;
            const previousValue = target.dataset.previousValue || '';

            if (selectedValue === 'paid') {
                const confirmationMessage =
                    "If you select 'Paid', make sure editing is finished, as the load will be locked and no further changes will be possible.";

                // eslint-disable-next-line no-alert
                const userConfirmed = confirm(confirmationMessage);

                if (userConfirmed) {
                    // Save the current value as the new previous value
                    target.dataset.previousValue = selectedValue;
                } else {
                    // Revert to the previous value
                    target.value = previousValue;
                }
            } else {
                // Update the previous value for non-'paid' selections
                target.dataset.previousValue = selectedValue;
            }
        });
    });
};
