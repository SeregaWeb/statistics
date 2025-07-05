import { printMessage } from './info-messages';

export const saveAllTracking = (urlAjax: string) => {
    const saveAllForms = document.querySelectorAll<HTMLFormElement>('.js-save-all-tracking');

    saveAllForms.forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const table = document.querySelector<HTMLElement>('.js-table-tracking');
            if (!table) return;

            const result = Array.from(
                table.querySelectorAll<HTMLButtonElement>('.js-save-status button:not([disabled])')
            )
                .map((button) => {
                    const formInTable = button.closest<HTMLFormElement>('.js-save-status');
                    if (!formInTable) return null;

                    const idLoadInput = formInTable.querySelector<HTMLInputElement>('[name="id_load"]');
                    const statusSelect = formInTable.querySelector<HTMLSelectElement>('[name="status"]');

                    if (!idLoadInput || !statusSelect) return null;

                    return `${idLoadInput.value}|${statusSelect.value}`;
                })
                .filter((item): item is string => item !== null);

            const formData = new FormData();
            formData.append('action', 'quick_update_status_all');
            formData.append('data', result.join(','));

            const btn = form.querySelector<HTMLButtonElement>('button');
            if (btn) btn.setAttribute('disabled', 'true');

            try {
                const response = await fetch(urlAjax, {
                    method: 'POST',
                    body: formData,
                });

                const requestStatus = await response.json();

                if (requestStatus.success) {
                    printMessage(requestStatus.data.message, 'success', 8000);
                    window.location.reload();
                } else {
                    printMessage(requestStatus.data.message, 'danger', 8000);
                }
            } catch (error) {
                printMessage(`Request failed: ${error}`, 'danger', 8000);
                console.error('Request failed:', error);
            } finally {
                if (btn) btn.removeAttribute('disabled');
            }

            console.log(result);
        });
    });
};
