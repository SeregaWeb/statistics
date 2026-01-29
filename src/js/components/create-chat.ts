import { printMessage } from './info-messages';

/**
 * Create Chat Form Handler
 * Handles chat creation form submission and button tooltip
 */

/**
 * Initialize create chat form handler
 */
export const initCreateChatForm = (urlAjax: string): void => {
    const form = document.querySelector<HTMLFormElement>('.js-create-chat-form');
    if (!form) return;

    const button = form.querySelector<HTMLButtonElement>('.js-create-chat');
    if (!button) return;

    // Handle form submission
    form.addEventListener('submit', (e: Event) => {
        e.preventDefault();

        const target = e.target as HTMLFormElement;
        
        // Prevent double submission
        if (target.dataset.submitting === 'true') {
            return false;
        }

        // Check for missing roles (soft-disable)
        const missingRoles = button.dataset.missingRoles ? JSON.parse(button.dataset.missingRoles) : [];
        if (Array.isArray(missingRoles) && missingRoles.length > 0) {
            printMessage('Missing required roles: ' + missingRoles.join(', '), 'danger', 5000);
            return false;
        }

        // Prevent double submission by disabling button only after validation
        if (button.disabled) {
            return false;
        }

        // Mark form as submitting
        target.dataset.submitting = 'true';
        button.disabled = true;
        button.textContent = 'Creating...';

        // Get form data
        const formData = new FormData(target);
        formData.append('action', 'create_load_chat');
        formData.append('nonce', (window as any).var_from_php?.nonce || '');

        // Send request
        fetch(urlAjax, {
            method: 'POST',
            body: formData
        })
        .then((res) => res.json())
        .then((data) => {
            if (data.success) {
                const message = data.data?.message || 'Chat created';
                printMessage(message, 'success', 5000);

                // Update button state to green and disabled
                button.classList.remove('btn-primary');
                button.classList.add('btn-success');
                button.textContent = 'Chat created';
                button.disabled = true;

                // Clear missing roles so further checks are not triggered
                button.dataset.missingRoles = JSON.stringify([]);
            } else {
                // Show human-friendly error message from backend if available
                const errorMessage = data.data?.message || 'Failed to create chat';
                printMessage(errorMessage, 'danger', 5000);
                button.disabled = false;
                button.textContent = 'Create chat';
            }
        })
        .catch((error) => {
            console.error('Error creating chat:', error);
            printMessage('An error occurred while creating the chat', 'danger', 5000);
            button.disabled = false;
            button.textContent = 'Create chat';
        })
        .finally(() => {
            target.dataset.submitting = 'false';
        });
    });

    // Tooltip content is handled purely via Bootstrap tooltips initialized in updateTooltip()
};
