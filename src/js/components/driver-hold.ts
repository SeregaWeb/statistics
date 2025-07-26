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
                    
                    // Обновляем UI - можно добавить визуальную индикацию
                    // Например, изменить цвет кнопки или добавить класс
                    if (holdUserId && holdUserId !== 'null') {
                        // Водитель освобожден
                        target.classList.remove('active', 'btn-danger');
                        target.classList.add('btn-primary');
                        target.setAttribute('data-hold', 'null');
                    } else {
                        // Водитель удержан
                        target.classList.remove('btn-primary');
                        target.classList.add('active', 'btn-danger');
                        target.setAttribute('data-hold', dispatcherId);
                    }
                    
                    // Показываем уведомление
                    printMessage(result.data || 'Статус водителя обновлен', 'success', 8000);
                } else {
                    console.error('Error updating driver hold status:', result.data);
                    printMessage(result.data || 'Ошибка при обновлении статуса', 'danger', 8000);
                }
            } catch (error) {
                console.error('Request failed:', error);
                printMessage('Ошибка сети', 'danger', 8000);
            }
        });
    });
};

 