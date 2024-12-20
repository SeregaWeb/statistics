// eslint-disable-next-line import/prefer-default-export
import Popup from '../parts/popup-window';
import { printMessage } from "./info-messages";

// eslint-disable-next-line import/prefer-default-export
export const actionCreateShipperInit = (ajaxUrl) => {
    const forms = document.querySelectorAll('.js-add-new-shipper');
    const popupInstance = new Popup();

    forms &&
        forms.forEach((item) => {
            item.addEventListener('submit', (event) => {
                event.preventDefault();
                const { target } = event;
                // @ts-ignore
                const formData = new FormData(target);
                formData.append('action', 'add_new_shipper');

                const options = {
                    method: 'POST',
                    body: formData,
                };

                fetch(ajaxUrl, options)
                    .then((res) => res.json())
                    .then((requestStatus) => {
                        if (requestStatus.success) {
                            console.log('Shipper added successfully:', requestStatus.data);
                            popupInstance.forceCloseAllPopup();
                            printMessage(requestStatus.data.message, 'success', 8000);
                        } else {
                            // eslint-disable-next-line no-alert
                            printMessage(`Error adding shipper: ${requestStatus.data.message}`, 'danger', 8000);
                        }
                    })
                    .catch((error) => {
                        printMessage(`Request failed: ${error}`, 'danger', 8000);
                        console.error('Request failed:', error);
                    });
            });
        });
};
// eslint-disable-next-line import/prefer-default-export
export const actionUpdateShipperInit = (ajaxUrl) => {
    const forms = document.querySelectorAll('.js-update-shipper');
    const popupInstance = new Popup();

    forms &&
        forms.forEach((item) => {
            item.addEventListener('submit', (event) => {
                event.preventDefault();
                const { target } = event;
                // @ts-ignore
                const formData = new FormData(target);
                formData.append('action', 'update_shipper');

                const options = {
                    method: 'POST',
                    body: formData,
                };

                fetch(ajaxUrl, options)
                    .then((res) => res.json())
                    .then((requestStatus) => {
                        if (requestStatus.success) {
                            console.log('Shipper update successfully:', requestStatus.data);
                            printMessage(requestStatus.data.message, 'success', 8000);
                        } else {
                            // eslint-disable-next-line no-alert
                            printMessage(`Error update shipper: ${requestStatus.data.message}`, 'danger', 8000);
                        }
                    })
                    .catch((error) => {
                        printMessage(`Request failed: ${error}`, 'danger', 8000);
                        console.error('Request failed:', error);
                    });
            });
        });
};

