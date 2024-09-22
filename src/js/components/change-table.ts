import { printMessage } from './info-messages';

// eslint-disable-next-line import/prefer-default-export
export const changeTableInit = (ajaxUrl) => {
    const selectTable = document.querySelectorAll('.js-select-current-table');

    selectTable &&
        selectTable.forEach((item) => {
            item.addEventListener('change', (event) => {
                event.preventDefault();
                const { target } = event;

                const formData = new FormData();
                formData.append('action', 'select_project');
                // @ts-ignore
                formData.append('select_table', target.value);

                const options = {
                    method: 'POST',
                    body: formData,
                };

                fetch(ajaxUrl, options)
                    .then((res) => res.json())
                    .then((requestStatus) => {
                        if (requestStatus.success) {
                            console.log('Table update', requestStatus.data);
                            printMessage(requestStatus.data.message, 'success', 8000);
                            const currentUrl = window.location.origin + window.location.pathname;
                            window.history.replaceState(null, '', currentUrl);
                            window.location.reload();
                        } else {
                            // eslint-disable-next-line no-alert
                            printMessage(`Error change project: ${requestStatus.data.message}`, 'danger', 8000);
                        }
                    })
                    .catch((error) => {
                        printMessage(`Request failed: ${error}`, 'danger', 8000);
                        console.error('Request failed:', error);
                    });
            });
        });
};
