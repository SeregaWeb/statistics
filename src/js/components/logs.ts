import { printMessage } from './info-messages';
import { updateStatusPost } from './create-report';

// eslint-disable-next-line import/prefer-default-export
export const logsInit = (ajaxUrl) => {
    const userLog = document.querySelector('.js-log-message');

    userLog &&
        userLog.addEventListener('submit', (event) => {
            event.preventDefault();
            const { target } = event;
            // @ts-ignore
            const form = new FormData(target);
            const action = 'add_user_log';
            // @ts-ignore
            form.append('action', action);

            const logContainer = document.querySelector('.js-log-container');

            const options = {
                method: 'POST',
                body: form,
            };

            // @ts-ignore
            target.setAttribute('disabled', 'disabled');
            fetch(ajaxUrl, options)
                .then((res) => res.json())
                .then((requestStatus) => {
                    if (requestStatus.success && logContainer) {
                        logContainer.innerHTML = requestStatus.data.template + logContainer.innerHTML;
                        // @ts-ignore
                        target.removeAttribute('disabled');
                        // @ts-ignore
                        target.reset();
                    }
                })
                .catch((error) => {
                    printMessage(`Request failed: ${error}`, 'danger', 8000);
                    console.error('Request failed:', error);
                    // @ts-ignore
                    target.removeAttribute('disabled');
                });
        });

    const btns = document.querySelectorAll('.js-hide-logs');

    btns &&
        btns.forEach((item) => {
            item.addEventListener('click', (event) => {
                event.preventDefault();
                const { target } = event;
                if (target instanceof HTMLElement) {
                    const wrap = target.closest('.js-logs-wrap');

                    if (!wrap) return;
                    const content = wrap.querySelector('.js-logs-content');
                    const container = wrap.querySelector('.js-logs-container');
                    console.log('target', target);

                    if (!content) return;
                    content.classList.toggle('col-lg-9');
                    content.classList.toggle('col-lg-11');

                    if (!container) return;
                    container.classList.toggle('col-lg-3');
                    container.classList.toggle('col-lg-1');
                    container.classList.toggle('hidden-logs');

                    let val = 0;
                    if (container.classList.contains('hidden-logs')) {
                        val = 1;
                    }
                    document.cookie = `logshow=${val}; path=/; max-age=86400`;
                }
            });
        });
};
