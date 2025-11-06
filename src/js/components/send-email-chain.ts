import { printMessage } from './info-messages';

// eslint-disable-next-line import/prefer-default-export
export const sendEmailChain = (ajaxUrl) => {
    const forms = document.querySelectorAll('.js-send-email-chain');

    forms &&
        forms.forEach((form) => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();

                const { target } = e;

                // Get project name from hidden input
                const projectInput = (target as HTMLFormElement).querySelector('input[name="project"]') as HTMLInputElement | null;
                const projectName = projectInput ? projectInput.value : 'Unknown Project';

                // Show confirmation prompt
                const confirmMessage = `${projectName}\n\nAre you sure you want to send tracking chain?`;
                if (!confirm(confirmMessage)) {
                    return;
                }

                // @ts-ignore
                const formData = new FormData(target);
                const flt = document.querySelector('input[name="flt"]');
                const action = flt ? 'send_email_chain_flt' : 'send_email_chain';
                formData.append('action', action);
                const options = {
                    method: 'POST',
                    body: formData,
                };

                // @ts-ignore
                const btn = target.querySelector('button');
                if (btn) {
                    btn.setAttribute('disabled', 'disabled');
                }

                // @ts-ignore
                fetch(ajaxUrl, options)
                    .then((res) => res.json())
                    .then((requestStatus) => {
                        if (requestStatus.success) {
                            printMessage(requestStatus.data.message, 'success', 8000);
                            setTimeout(() => {
                                window.location.reload();
                            }, 4000);
                        } else {
                            if (btn) {
                                btn.removeAttribute('disabled');
                            }

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
