// eslint-disable-next-line import/prefer-default-export
import * as url from 'node:url';
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
export const updateDriverFinance = (urlAjax) => {
    const form = document.querySelector('.js-driver-finance-form');
    console.log('form', form);
    if (!form) return;

    form.addEventListener('submit', (e: Event) => {
        e.preventDefault();
        console.log('form click');

        const target = e.target as HTMLFormElement;
        disabledBtnInForm(target);

        const formData = new FormData(target);
        formData.append('action', 'update_driver_finance');

        const options = {
            method: 'POST',
            body: formData,
        };

        const nextTargetTab = 'pills-driver-documents-tab';

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

export const updateDriverDocument = (urlAjax) => {
    const form = document.querySelector('.js-driver-document-form');
    console.log('form', form);
    if (!form) return;

    form.addEventListener('submit', (e: Event) => {
        e.preventDefault();
        console.log('form click');

        const target = e.target as HTMLFormElement;
        disabledBtnInForm(target);

        const formData = new FormData(target);
        formData.append('action', 'update_driver_document');

        const options = {
            method: 'POST',
            body: formData,
        };

        const nextTargetTab = 'pills-driver-documents-tab';

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

export const updateStatusDriver = (ajaxUrl) => {
    const btns = document.querySelectorAll('.js-update-driver-status');
    btns &&
        btns.forEach((btn) => {
            btn.addEventListener('click', (event) => {
                event.preventDefault();

                const { target } = event;

                // @ts-ignore
                const formData = new FormData();
                const action = 'update_driver_status';

                const postId = document.querySelector('.js-post-id');

                if (!postId) {
                    printMessage('Driver id not found', 'danger', 8000);
                    return;
                }

                formData.append('action', action);
                // @ts-ignore
                formData.append('post_id', postId.value);

                const options = {
                    method: 'POST',
                    body: formData,
                };

                // @ts-ignore
                fetch(ajaxUrl, options)
                    .then((res) => res.json())
                    .then((requestStatus) => {
                        if (requestStatus.success) {
                            printMessage(requestStatus.data.message, 'success', 8000);
                            if (requestStatus.data.send_email?.success) {
                                console.log(requestStatus.data);
                                printMessage(requestStatus.data.send_email?.message, 'success', 8000);
                                setTimeout(() => {
                                    window.location.reload();
                                }, 4000);
                            } else {
                                printMessage(requestStatus.data.send_email?.message, 'danger', 8000);
                            }

                            const container = document.querySelector('.js-update-status');

                            if (!container) return;
                            container.innerHTML = '';

                            setUpTabInUrl('pills-driver-contact-tab');
                        } else {
                            printMessage(requestStatus.data.message, 'danger', 8000);
                        }
                    })
                    .catch((error) => {
                        printMessage(`Request failed: ${error}`, 'danger', 8000);
                        console.error('Request failed:', error);
                    });
            });
        });
};

export const helperDisabledChecbox = () => {
    const checkboxes = document.querySelectorAll('.js-disable-with-logic');

    checkboxes.forEach((item) => {
        item.addEventListener('change', (evt) => {
            const { target } = evt as Event & { target: HTMLInputElement };
            const container = target.closest('.js-container-checkboxes');

            if (!container) return;

            const allCheckboxes = container.querySelectorAll<HTMLInputElement>('input[type="checkbox"]');
            const mainCheckbox = Array.from(allCheckboxes).find((cb) => cb.value === cb.getAttribute('data-value'));
            console.log('mainCheckbox', mainCheckbox);
            if (!mainCheckbox) return;

            // Если главный чекбокс выбран
            if (mainCheckbox.checked) {
                // Дизейблим все кроме главного
                allCheckboxes.forEach((cb) => {
                    if (cb !== mainCheckbox) {
                        // eslint-disable-next-line no-param-reassign
                        cb.disabled = true;
                        // eslint-disable-next-line no-param-reassign
                        cb.checked = true;
                    }
                });
            } else {
                allCheckboxes.forEach((cb) => {
                    if (cb !== mainCheckbox) {
                        // eslint-disable-next-line no-param-reassign
                        cb.disabled = false;
                    }
                });
            }

            if (target !== mainCheckbox) {
                const others = Array.from(allCheckboxes).filter((cb) => cb !== mainCheckbox);
                const allOthersChecked = others.every((cb) => cb.checked);

                if (allOthersChecked) {
                    mainCheckbox.checked = true;
                    allCheckboxes.forEach((cb) => {
                        if (cb !== mainCheckbox) {
                            // eslint-disable-next-line no-param-reassign
                            cb.checked = true;
                            // eslint-disable-next-line no-param-reassign
                            cb.disabled = true;
                        }
                    });
                }
            }
        });
    });
};

export const removeFullDriver = (ajaxUrl) => {
    const btnsRemove = document.querySelectorAll('.js-remove-driver');

    btnsRemove &&
        btnsRemove.forEach((item) => {
            item.addEventListener('click', (event) => {
                event.preventDefault();

                const { target } = event;

                const question = confirm(
                    'Are you sure you want to delete this driver? \nIf you agree it will be deleted permanently'
                );

                if (target instanceof HTMLElement && question) {
                    const idLoad = target.getAttribute('data-id');

                    if (!idLoad) {
                        printMessage(`Error remove Load: reload this page and try again`, 'danger', 8000);
                        return;
                    }

                    const action = 'remove_one_driver';

                    const formData = new FormData();

                    formData.append('action', action);
                    formData.append('id_driver', idLoad);

                    const options = {
                        method: 'POST',
                        body: formData,
                    };

                    fetch(ajaxUrl, options)
                        .then((res) => res.json())
                        .then((requestStatus) => {
                            if (requestStatus.success) {
                                console.log('Driver remove successfully:', requestStatus.data);
                                const contain = target.closest('tr');

                                if (contain) {
                                    contain.remove();
                                }
                                printMessage(requestStatus.data.message, 'success', 8000);
                            } else {
                                // eslint-disable-next-line no-alert
                                printMessage(`Error remove Driver:${requestStatus.data.message}`, 'danger', 8000);
                            }
                        })
                        .catch((error) => {
                            printMessage(`Request failed: ${error}`, 'danger', 8000);
                            console.error('Request failed:', error);
                        });
                }
            });
        });
};

export const uploadFileDriver = (ajaxUrl) => {
    const forms = document.querySelectorAll('.js-upload-driver-helper');

    forms.forEach((form) => {
        form.addEventListener('submit', (e) => {
            e.preventDefault();

            const action = 'upload_driver_helper';
        });
    });
};
export const driversActions = (urlAjax) => {
    createDriver(urlAjax);
    removeFullDriver(urlAjax);
    updateDriverContact(urlAjax);
    updateDriverInformation(urlAjax);
    updateDriverFinance(urlAjax);
    updateDriverDocument(urlAjax);
    removeOneFileInitial(urlAjax);
    updateStatusDriver(urlAjax);
    uploadFileDriver(urlAjax);

    helperDisabledChecbox();
};
