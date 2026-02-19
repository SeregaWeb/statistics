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
                const rowRoot = target.closest('.d-flex.flex-column.gap-1') as HTMLElement | null;
                const logWrapper = rowRoot?.querySelector('.js-log-wrapper') as HTMLElement | null;
                const pinnedWrapper = rowRoot?.querySelector('.js-pinned-wrapper') as HTMLElement | null;
                if (logWrapper || pinnedWrapper) {
                    const modal = document.getElementById('addLogModal');
                    if (modal) {
                        if (logWrapper) {
                            (modal as any).targetLogWrapper = logWrapper;
                        }
                        if (pinnedWrapper) {
                            (modal as any).targetPinnedWrapper = pinnedWrapper;
                        }
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
            
            // Check if pinned message checkbox is checked (renamed to is_pinned)
            const pinnedCheckbox = (target as HTMLFormElement).querySelector('input[name="is_pinned"]') as HTMLInputElement;
            const isPinned = pinnedCheckbox && pinnedCheckbox.checked;
            
            // If pinned, send pinned_message with textarea value
            if (isPinned) {
                const messageTextarea = (target as HTMLFormElement).querySelector('#logMessageTextarea') as HTMLTextAreaElement;
                const messageValue = messageTextarea ? messageTextarea.value : '';
                form.set('pinned_message', messageValue);
            }
            
            // Detect FLT context
            const fltInput = (target as HTMLFormElement).querySelector('input[name="flt"]') as HTMLInputElement | null;
            const isFlt = !!(fltInput && fltInput.value);
            
            // Set the appropriate action based on checkbox and FLT
            const action = isPinned
                ? (isFlt ? 'add_pinned_message_flt' : 'add_pinned_message')
                : 'add_user_log';
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
                        const successMessage = isPinned ? 'Pinned message added successfully' : 'Log message added successfully';
                        printMessage(successMessage, 'success', 3000);
                        // @ts-ignore
                        target.reset();

                        if (isPinned) {
                            // Handle pinned message response (pinned is an array)
                            const pinnedList = requestStatus.data?.pinned;
                            if (pinnedList && Array.isArray(pinnedList)) {
                                const modalForLog = document.getElementById('addLogModal');
                                const pinnedWrapper = modalForLog ? (modalForLog as any).targetPinnedWrapper as HTMLElement | undefined : undefined;
                                if (pinnedWrapper) {
                                    let pinnedHtml = '';
                                    pinnedList.forEach((pinned: { full_name?: string; time_pinned?: string; pinned_message?: string }) => {
                                        pinnedHtml += `
                                    <div class="pinned-message">
                                        <div class="d-flex justify-content-between align-items-center pinned-message__header">
                                            <span class="d-flex align-items-center ">
                                                <svg fill="#000000" width="18px" height="18px" viewBox="0 0 32 32" version="1.1" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M18.973 17.802l-7.794-4.5c-0.956-0.553-2.18-0.225-2.732 0.731-0.552 0.957-0.224 2.18 0.732 2.732l7.793 4.5c0.957 0.553 2.18 0.225 2.732-0.732 0.554-0.956 0.226-2.179-0.731-2.731zM12.545 12.936l6.062 3.5 2.062-5.738-4.186-2.416-3.938 4.654zM8.076 27.676l5.799-7.044-2.598-1.5-3.201 8.544zM23.174 7.525l-5.195-3c-0.718-0.414-1.635-0.169-2.049 0.549-0.415 0.718-0.168 1.635 0.549 2.049l5.196 3c0.718 0.414 1.635 0.169 2.049-0.549s0.168-1.635-0.55-2.049z"></path>
                                                </svg>
                                                ${pinned.full_name || ''}
                                            </span>
                                            <span>${pinned.time_pinned || ''}</span>
                                        </div>
                                        <div class="pinned-message__content">
                                            ${pinned.pinned_message || ''}
                                        </div>
                                    </div>`;
                                    });
                                    pinnedWrapper.innerHTML = pinnedHtml;
                                }
                            }
                        } else {
                            // Handle regular log response
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
                                    forceCloseModal(modal);
                                }
                            } catch (error) {
                                console.log('Error closing modal:', error);
                                // Fallback: manually hide modal
                                forceCloseModal(modal);
                            }
                        }
                        
                        // Clean up the references
                        const modalRef = document.getElementById('addLogModal');
                        if (modalRef) {
                            if ((modalRef as any).targetLogWrapper) {
                                delete (modalRef as any).targetLogWrapper;
                            }
                            if ((modalRef as any).targetPinnedWrapper) {
                                delete (modalRef as any).targetPinnedWrapper;
                            }
                        }
                        
                        // Additional cleanup to ensure scroll is restored
                        setTimeout(() => {
                            // Remove any remaining modal-related classes and styles
                            document.body.classList.remove('modal-open');
                            document.body.style.overflow = '';
                            document.body.style.paddingRight = '';
                            document.documentElement.style.overflow = '';
                            document.documentElement.style.paddingRight = '';
                            
                            // Remove any remaining backdrops
                            const remainingBackdrops = document.querySelectorAll('.modal-backdrop');
                            remainingBackdrops.forEach(backdrop => backdrop.remove());
                        }, 100);
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

// Helper function to force close modal and restore scroll
function forceCloseModal(modal: HTMLElement) {
    // Remove modal classes
    modal.classList.remove('show');
    modal.classList.remove('fade');
    modal.style.display = 'none';
    modal.setAttribute('aria-hidden', 'true');
    modal.removeAttribute('aria-modal');
    modal.removeAttribute('role');
    
    // Remove body classes that prevent scrolling
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
    
    // Remove all modal backdrops
    const backdrops = document.querySelectorAll('.modal-backdrop');
    backdrops.forEach(backdrop => backdrop.remove());
    
    // Remove any inline styles that might be blocking scroll
    document.documentElement.style.overflow = '';
    document.documentElement.style.paddingRight = '';
    
    // Force reflow to ensure changes take effect
    modal.offsetHeight;
}
