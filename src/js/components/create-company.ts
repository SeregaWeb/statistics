// eslint-disable-next-line import/prefer-default-export
import Popup from '../parts/popup-window';
import { printMessage } from './info-messages';

// eslint-disable-next-line import/prefer-default-export
export const actionCreateCompanyInit = (ajaxUrl) => {
    const forms = document.querySelectorAll('.js-add-new-company');
    const popupInstance = new Popup();

    forms &&
        forms.forEach((item) => {
            item.addEventListener('submit', (event) => {
                event.preventDefault();
                const { target } = event;
                // @ts-ignore
                const formData = new FormData(target);
                formData.append('action', 'add_new_company');

                const options = {
                    method: 'POST',
                    body: formData,
                };

                fetch(ajaxUrl, options)
                    .then((res) => res.json())
                    .then((requestStatus) => {
                        if (requestStatus.success) {
                            console.log('Company added successfully:', requestStatus.data);
                            popupInstance.forceCloseAllPopup();
                            printMessage(requestStatus.data.message, 'success', 8000);
                        } else {
                            // eslint-disable-next-line no-alert
                            printMessage(`Error adding company: ${requestStatus.data.message}`, 'danger', 8000);
                        }
                    })
                    .catch((error) => {
                        printMessage(`Request failed: ${error}`, 'danger', 8000);
                        console.error('Request failed:', error);
                    });
            });
        });
};

export const ActionUpdateCompanyInit = (ajaxUrl) => {
    const form = document.querySelector('.js-update-company');

    if (form) {
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            const { target } = event;
            // @ts-ignore
            const formData = new FormData(target);
            formData.append('action', 'update_company');

            const options = {
                method: 'POST',
                body: formData,
            };

            fetch(ajaxUrl, options)
                .then((res) => res.json())
                .then((requestStatus) => {
                    if (requestStatus.success) {
                        console.log('Broker update successfully:', requestStatus.data);
                        printMessage(requestStatus.data.message, 'success', 8000);
                    } else {
                        // eslint-disable-next-line no-alert
                        printMessage(`Error update broker: ${requestStatus.data.message}`, 'danger', 8000);
                    }
                })
                .catch((error) => {
                    printMessage(`Request failed: ${error}`, 'danger', 8000);
                    console.error('Request failed:', error);
                });
        });
    }
};

export const ActionDeleteCompanyInit = (ajaxUrl) => {
    const form = document.querySelector('.js-delete-broker');
    if (form) {
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            const { target } = event;
            // @ts-ignore
            const formData = new FormData(target);
            formData.append('action', 'delete_broker');

            const options = {
                method: 'POST',
                body: formData,
            };

            fetch(ajaxUrl, options)
                .then((res) => res.json())
                .then((requestStatus) => {
                    if (requestStatus.success) {
                        console.log('Broker delete successfully:', requestStatus.data);
                        printMessage(requestStatus.data.message, 'success', 8000);
                        const link = document.querySelector('.js-brokers-page-link');

                        if (link) {
                            setTimeout(() => {
                                window.location.href = <string>link.getAttribute('href');
                            }, 1500);
                        }
                    } else {
                        // eslint-disable-next-line no-alert
                        printMessage(`Error update broker: ${requestStatus.data.message}`, 'danger', 8000);
                    }
                })
                .catch((error) => {
                    printMessage(`Request failed: ${error}`, 'danger', 8000);
                    console.error('Request failed:', error);
                });
        });
    }
};
