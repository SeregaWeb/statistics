// eslint-disable-next-line import/prefer-default-export
import { printMessage } from './info-messages';
import { setUpTabInUrl } from './create-report';
import { disabledBtnInForm } from './disabled-btn-in-form';

export const createDriver = (urlAjax) => {
    const form = document.querySelector('.js-create-driver');
    console.log('form', form);
    if (!form) return;

    form.addEventListener('submit', (e: Event) => {
        e.preventDefault();
        console.log('form click');

        const target = e.target as HTMLFormElement;
        disabledBtnInForm(target);

        const formData = new FormData(target);
        formData.append('action', 'add_driver');

        const options = {
            method: 'POST',
            body: formData,
        };

        fetch(urlAjax, options)
            .then((res) => res.json())
            .then((requestStatus) => {
                if (requestStatus.success) {
                    const newUrl = new URL(window.location.href);
                    newUrl.searchParams.set('driver', requestStatus.data.id_driver);
                    window.location.href = newUrl.toString();
                    return true;
                }
                printMessage(`${requestStatus.data.message}`, 'danger', 8000);
                disabledBtnInForm(target, true);
                // eslint-disable-next-line consistent-return
                return false;
            })
            .catch((error) => {
                printMessage(`'Request failed' ${error}`, 'danger', 8000);
                disabledBtnInForm(target, true);
                return false;
            });
    });
};

export const updateDriverContact = (urlAjax) => {
    const form = document.querySelector('.js-update-driver');
    console.log('form', form);
    if (!form) return;

    form.addEventListener('submit', (e: Event) => {
        e.preventDefault();
        console.log('form click');

        const target = e.target as HTMLFormElement;
        disabledBtnInForm(target);

        const formData = new FormData(target);
        formData.append('action', 'update_driver_contact');

        const options = {
            method: 'POST',
            body: formData,
        };

        const nextTargetTab = 'pills-driver-vehicle-tab';

        fetch(urlAjax, options)
            .then((res) => res.json())
            .then((requestStatus) => {
                if (requestStatus.success) {
                    setUpTabInUrl(nextTargetTab);
                    disabledBtnInForm(target, true);
                    return true;
                }
                printMessage(`${requestStatus.data.message}`, 'danger', 8000);
                disabledBtnInForm(target, true);
                // eslint-disable-next-line consistent-return
                return false;
            })
            .catch((error) => {
                disabledBtnInForm(target, true);
                printMessage(`'Request failed' ${error}`, 'danger', 8000);
                return false;
            });
    });
};

export const updateDriverInformation = (urlAjax) => {
    const form = document.querySelector('.js-update-driver-information');
    console.log('form', form);
    if (!form) return;

    form.addEventListener('submit', (e: Event) => {
        e.preventDefault();
        console.log('form click');

        const target = e.target as HTMLFormElement;
        disabledBtnInForm(target);

        const formData = new FormData(target);
        formData.append('action', 'update_driver_information');

        const options = {
            method: 'POST',
            body: formData,
        };

        const nextTargetTab = 'pills-driver-finance-tab';

        fetch(urlAjax, options)
            .then((res) => res.json())
            .then((requestStatus) => {
                if (requestStatus.success) {
                    setUpTabInUrl(nextTargetTab);
                    disabledBtnInForm(target, true);
                    return true;
                }
                printMessage(`${requestStatus.data.message}`, 'danger', 8000);
                disabledBtnInForm(target, true);
                // eslint-disable-next-line consistent-return
                return false;
            })
            .catch((error) => {
                printMessage(`'Request failed' ${error}`, 'danger', 8000);
                disabledBtnInForm(target, true);
                return false;
            });
    });
};

export const removeOneFileInitial = (ajaxUrl) => {
    const deleteForms = document.querySelectorAll('.js-remove-one-driver');

    deleteForms &&
        deleteForms.forEach((item) => {
            item.addEventListener('submit', (event) => {
                event.preventDefault();
                const { target } = event;
                // @ts-ignore
                disabledBtnInForm(target);
                // @ts-ignore
                const formData = new FormData(target);
                const action = 'delete_open_image_driver';

                formData.append('action', action);

                const options = {
                    method: 'POST',
                    body: formData,
                };

                fetch(ajaxUrl, options)
                    .then((res) => res.json())
                    .then((requestStatus) => {
                        if (requestStatus.success) {
                            printMessage(requestStatus.data.message, 'success', 8000);
                            // @ts-ignore
                            disabledBtnInForm(target, true);
                            // @ts-ignore
                            setUpTabInUrl(target.dataset.tab);
                        } else {
                            // @ts-ignore
                            disabledBtnInForm(target, true);
                            printMessage(`Error adding report:${requestStatus.data.message}`, 'danger', 8000);
                        }
                    })
                    .catch((error) => {
                        printMessage(`Request failed: ${error}`, 'danger', 8000);
                        // @ts-ignore
                        disabledBtnInForm(target, true);
                        console.error('Request failed:', error);
                    });
            });
        });
};

export const driversActions = (urlAjax) => {
    createDriver(urlAjax);
    updateDriverContact(urlAjax);
    updateDriverInformation(urlAjax);
    removeOneFileInitial(urlAjax);
};
