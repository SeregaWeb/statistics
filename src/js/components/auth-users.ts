import { printMessage } from './info-messages';

// eslint-disable-next-line import/prefer-default-export
export const AuthUsersInit = (ajaxUrl) => {
    const loginForm = document.querySelector('.js-login-form');

    if (loginForm) {
        const hideBtnLogin = document.querySelector('.js-login-btn') as HTMLElement;
        const hideCodeEl = document.querySelector('.js-hide-field') as HTMLElement;
        const sendCodeBtn = document.querySelector('.js-send-code') as HTMLButtonElement;

        sendCodeBtn &&
            sendCodeBtn.addEventListener('click', (evt) => {
                evt.preventDefault();

                const { target } = evt;
                if (target instanceof HTMLElement) {
                    const form = target.closest('form') as HTMLFormElement | null;

                    if (form) {
                        const formData = new FormData(form);
                        formData.append('action', 'send_code');

                        const options = {
                            method: 'POST',
                            body: formData,
                        };

                        fetch(ajaxUrl, options)
                            .then((res) => res.json())
                            .then((requestStatus) => {
                                if (requestStatus.success) {
                                    console.log('success send code', requestStatus.data);
                                    printMessage(requestStatus.data.message, 'success', 16000);
                                    hideBtnLogin.classList.remove('d-none');
                                    hideCodeEl.classList.remove('d-none');
                                    sendCodeBtn.innerText = 'Send the code again';
                                } else {
                                    // eslint-disable-next-line no-alert
                                    printMessage(`Error send code: ${requestStatus.data.message}`, 'danger', 8000);
                                }
                            })
                            .catch((error) => {
                                printMessage(`Request failed: ${error}`, 'danger', 8000);
                                console.error('Request failed:', error);
                            });
                    } else {
                        console.error('Форма не найдена');
                    }
                }
            });

        loginForm &&
            loginForm.addEventListener('submit', (evt) => {
                evt.preventDefault();

                const { target } = evt;
                if (target instanceof HTMLFormElement) {
                    const formData = new FormData(target);
                    formData.append('action', 'verify_code');

                    const options = {
                        method: 'POST',
                        body: formData,
                    };

                    fetch(ajaxUrl, options)
                        .then((res) => res.json())
                        .then((requestStatus) => {
                            if (requestStatus.success) {
                                console.log('success login', requestStatus.data);
                                printMessage(requestStatus.data.message, 'success', 16000);
                                window.location.href = requestStatus.data.redirect;
                            } else {
                                // eslint-disable-next-line no-alert
                                printMessage(`Error login: ${requestStatus.data.message}`, 'danger', 8000);
                            }
                        })
                        .catch((error) => {
                            printMessage(`Request failed: ${error}`, 'danger', 8000);
                            console.error('Request failed:', error);
                        });
                }
            });
    }
};
