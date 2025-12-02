/**
 * Driver Hold Functionality
 * Handles driver hold/release functionality via AJAX
 */

import { printMessage } from './info-messages';

export const driverHoldInit = (ajaxUrl: string) => {
    const holdButtons = document.querySelectorAll('.js-hold-driver');
    
    if (!holdButtons) return;

    holdButtons.forEach((button) => {
        button.addEventListener('click', async (event) => {
            event.preventDefault();
            
            const target = event.target as HTMLElement;
            const driverId = target.getAttribute('data-id');
            const dispatcherId = target.getAttribute('data-dispatcher');
            const holdUserId = target.getAttribute('data-hold');
            
            if (!driverId || !dispatcherId) {
                console.error('Missing required data attributes');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'hold_driver_status');
            formData.append('id_driver', driverId);
            formData.append('id_user', dispatcherId);
            
            if (holdUserId && holdUserId !== 'null') {
                formData.append('hold_user_id', holdUserId);
            }
            
            try {
                const response = await fetch(ajaxUrl, {
                    method: 'POST',
                    body: formData,
                });
                
                const result = await response.json();
                
                if (result.success) {
                    console.log('Driver hold status updated successfully:', result.data);
                    
                    if (holdUserId && holdUserId !== 'null') {
                        // Driver released
                        target.setAttribute('data-hold', 'null');
                    } else {
                        // Driver held
                        target.setAttribute('data-hold', dispatcherId);
                    }
                    
                    printMessage(result.data || 'Driver hold status updated', 'success', 8000);
                    window.location.reload();
                } else {
                    console.error('Error updating driver hold status:', result.data);
                    printMessage(result.data || 'Error updating driver hold status', 'danger', 8000);
                }
            } catch (error) {
                console.error('Request failed:', error);
                printMessage('Network error', 'danger', 8000);
            }
        });
    });
};

 