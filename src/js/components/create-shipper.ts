// eslint-disable-next-line import/prefer-default-export
import Popup from '../parts/popup-window';

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
                        } else {
                            // eslint-disable-next-line no-alert
                            alert(`Error adding shipper:${requestStatus.data.message}`);
                        }
                    })
                    .catch((error) => {
                        console.error('Request failed:', error);
                    });
            });
        });
};
