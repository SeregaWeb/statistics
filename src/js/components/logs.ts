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

            // Disable submit button to prevent double submission
            const submitButton = (target as HTMLFormElement).querySelector('button[type="submit"]') as HTMLButtonElement;
            if (submitButton) {
                submitButton.disabled = true;
            }

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
                    } else {
                        printMessage(requestStatus.data.message, 'danger', 8000);
                    }
                })
                .catch((error) => {
                    printMessage(`Request failed: ${error}`, 'danger', 8000);
                    console.error('Request failed:', error);
                    // @ts-ignore
                    target.removeAttribute('disabled');
                })
                .finally(() => {
                    // Re-enable submit button
                    if (submitButton) {
                        submitButton.disabled = false;
                    }
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

// Function to handle modal log message form
export const modalLogsInit = (ajaxUrl) => {
    // Handle opening modal and setting post ID
    const openModalButtons = document.querySelectorAll('.js-open-log-modal');
    
    openModalButtons.forEach((button) => {
        button.addEventListener('click', (event) => {
            const target = event.currentTarget as HTMLElement;
            const postId = target.getAttribute('data-post-id');
            
            if (postId) {
                const modalPostIdInput = document.getElementById('modal_post_id') as HTMLInputElement;
                if (modalPostIdInput) {
                    modalPostIdInput.value = postId;
                }
                
                // Store reference to the log wrapper for this specific row
                const logWrapper = target.closest('.d-flex.flex-column.gap-1')?.querySelector('.js-log-wrapper') as HTMLElement;
                if (logWrapper) {
                    // Store the log wrapper reference in the modal for later use
                    const modal = document.getElementById('addLogModal');
                    if (modal) {
                        modal.setAttribute('data-target-log-wrapper', '');
                        // Store reference in a way we can access it later
                        (modal as any).targetLogWrapper = logWrapper;
                    }
                }
            }
        });
    });

    // Handle modal form submission
    const modalLogForm = document.querySelector('.js-log-message-modal');
    
    modalLogForm &&
        modalLogForm.addEventListener('submit', (event) => {
            event.preventDefault();
            const { target } = event;
            // @ts-ignore
            const form = new FormData(target);
            const action = 'add_user_log';
            // @ts-ignore
            form.append('action', action);
            

            const options = {
                method: 'POST',
                body: form,
            };

            // Disable submit button
            const submitButton = (target as HTMLFormElement).querySelector('button[type="submit"]') as HTMLButtonElement;
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.textContent = 'Sending...';
            }

            fetch(ajaxUrl, options)
                .then((res) => res.json())
                .then((requestStatus) => {
                    if (requestStatus.success) {
                        printMessage('Log message added successfully', 'success', 3000);
                        // @ts-ignore
                        target.reset();
                        
                        // Replace the log entry in the specific log wrapper (keep only the latest message)
                        const modalForLog = document.getElementById('addLogModal');
                        if (modalForLog && (modalForLog as any).targetLogWrapper && requestStatus.data.template) {
                            const logWrapper = (modalForLog as any).targetLogWrapper as HTMLElement;
                            logWrapper.innerHTML = requestStatus.data.template;
                        } else {
                            // Fallback: try to find any log container
                            const logContainer = document.querySelector('.js-log-container');
                            if (logContainer && requestStatus.data.template) {
                                logContainer.innerHTML = requestStatus.data.template;
                            }
                        }
                        // Close modal
                        const modal = document.getElementById('addLogModal');
                        if (modal) {
                            // Try different ways to close the modal
                            try {
                                // @ts-ignore
                                if (window.bootstrap && window.bootstrap.Modal) {
                                    // @ts-ignore
                                    const bootstrapModal = window.bootstrap.Modal.getInstance(modal);
                                    if (bootstrapModal) {
                                        bootstrapModal.hide();
                                    } else {
                                        // Create new instance and hide
                                        // @ts-ignore
                                        const newModal = new window.bootstrap.Modal(modal);
                                        newModal.hide();
                                    }
                                } else {
                                    // Fallback: manually hide modal
                                    modal.classList.remove('show');
                                    modal.style.display = 'none';
                                    document.body.classList.remove('modal-open');
                                    const backdrop = document.querySelector('.modal-backdrop');
                                    if (backdrop) {
                                        backdrop.remove();
                                    }
                                }
                            } catch (error) {
                                console.log('Error closing modal:', error);
                                // Fallback: manually hide modal
                                modal.classList.remove('show');
                                modal.style.display = 'none';
                                document.body.classList.remove('modal-open');
                                const backdrop = document.querySelector('.modal-backdrop');
                                if (backdrop) {
                                    backdrop.remove();
                                }
                            }
                        }
                        
                        // Clean up the reference to the log wrapper
                        if (modal && (modal as any).targetLogWrapper) {
                            delete (modal as any).targetLogWrapper;
                        }
                    } else {
                        printMessage(requestStatus.data.message, 'danger', 8000);
                    }
                })
                .catch((error) => {
                    printMessage(`Request failed: ${error}`, 'danger', 8000);
                    console.error('Request failed:', error);
                })
                .finally(() => {
                    // Re-enable submit button
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.textContent = 'Send';
                    }
                });
        });
};
