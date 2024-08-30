// eslint-disable-next-line import/prefer-default-export
import Popup from '../parts/popup-window';

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
                        } else {
                            // eslint-disable-next-line no-alert
                            alert(`Error adding company:${requestStatus.data.message}`);
                        }
                    })
                    .catch((error) => {
                        console.error('Request failed:', error);
                    });
            });
        });
};
