import { printMessage } from './info-messages';
/**
 * Load existing ETA record from database
 */
const loadExistingEtaRecord = (loadId: string, etaType: string, isFlt: string, popup: HTMLElement): Promise<void> => {
    const formData = new FormData();
    formData.append('action', 'get_eta_record');
    formData.append('load_id', loadId);
    formData.append('eta_type', etaType);
    formData.append('is_flt', isFlt);

    return fetch((window as any).var_from_php?.ajax_url || '/wp-admin/admin-ajax.php', {
        method: 'POST',
        body: formData
    })
    .then((response) => response.json())
    .then((data) => {
        if (data.success && data.data.exists) {
            // Fill form with existing data
            const form = popup.querySelector('form') as HTMLFormElement;
            if (form) {
                const dateInput = form.querySelector('input[name="date"]') as HTMLInputElement;
                const timeInput = form.querySelector('input[name="time"]') as HTMLInputElement;
                
                if (dateInput) dateInput.value = data.data.date || '';
                if (timeInput) timeInput.value = data.data.time || '';
            }
        }
    })
    .catch((error) => {
        console.error('Error loading ETA record:', error);
    });
};

/**
 * ETA Popup Handler
 * Handles opening ETA popups and filling them with current data
 */

export const initEtaPopups = (): void => {
    // Store reference to the clicked button
    let clickedButton: HTMLButtonElement | null = null;
    
    // Handle ETA button clicks
    document.addEventListener('click', (event: Event): void => {
        const target = event.target as HTMLElement;
        
        if (!target.classList.contains('js-open-popup-activator')) {
            return;
        }

        const button = target as HTMLButtonElement;
        const href = button.getAttribute('data-href');
        
        // Check if it's an ETA popup 
        if (href === '#popup_eta_pick_up' || href === '#popup_eta_delivery') {
            event.preventDefault();
            
            // Store reference to clicked button
            clickedButton = button;
            
            // Get data from button attributes
            const loadId = button.getAttribute('data-load-id');
            const currentDate = button.getAttribute('data-current-date');
            const currentTime = button.getAttribute('data-current-time');
            const state = button.getAttribute('data-state');
            
            // Find the popup
            const popup = document.querySelector(href) as HTMLElement;
            if (!popup) {
                console.error('ETA popup not found:', href);
                return;
            }
            
            // Fill the form with load ID first
            const form = popup.querySelector('form') as HTMLFormElement;
            if (form) {
                const idInput = form.querySelector('input[name="id_load"]') as HTMLInputElement;
                if (idInput) idInput.value = loadId || '';
            }

            // Load existing ETA record if exists, otherwise use current data
            const etaType = button.getAttribute('data-eta-type') || '';
            const isFlt = button.getAttribute('data-is-flt') || '0';
            
            // Check if button is green (has existing record)
            const hasExistingRecord = button.classList.contains('btn-success');
            
            if (hasExistingRecord) {
                // Show popup first
                popup.classList.add('active');
                document.body.classList.add('popup-open');
                
                // Disable submit button while loading
                if (form) {
                    const submitBtn = form.querySelector('button[type="submit"]') as HTMLButtonElement;
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.textContent = 'Loading...';
                    }
                }
                
                // Load existing data from database
                loadExistingEtaRecord(loadId || '', etaType, isFlt, popup).then(() => {
                    // Re-enable submit button after data is loaded
                    if (form) {
                        const submitBtn = form.querySelector('button[type="submit"]') as HTMLButtonElement;
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.textContent = 'Save';
                        }
                    }
                });
            } else {
                // Fill with current data from button
                if (form) {
                    const dateInput = form.querySelector('input[name="date"]') as HTMLInputElement;
                    const timeInput = form.querySelector('input[name="time"]') as HTMLInputElement;
                    
                    if (dateInput) dateInput.value = currentDate || '';
                    if (timeInput) timeInput.value = currentTime || '';
                }
                
                // Show the popup immediately for new records
                popup.classList.add('active');
                document.body.classList.add('popup-open');
            }
        }
    });

    // Handle ETA form submissions
    document.addEventListener('submit', (event: Event): void => {
        const form = event.target as HTMLFormElement;
        
        if (!form.classList.contains('js-eta-pickup-form') && !form.classList.contains('js-eta-delivery-form')) {
            return;
        }

        event.preventDefault();
        
        const loadId = form.querySelector('input[name="id_load"]')?.getAttribute('value') || '';
        const date = (form.querySelector('input[name="date"]') as HTMLInputElement)?.value || '';
        const time = (form.querySelector('input[name="time"]') as HTMLInputElement)?.value || '';
        
        if (!loadId || !date || !time) {
            printMessage('Please fill in all required fields', 'danger', 5000);
            return;
        }

        // Use the stored reference to the clicked button
        const activeButton = clickedButton;
        if (!activeButton) {
            printMessage('Error: Could not find load data', 'danger', 5000);
            return;
        }

        const timezone = activeButton.getAttribute('data-timezone') || '';
        const etaType = activeButton.getAttribute('data-eta-type') || '';
        const isFlt = activeButton.getAttribute('data-is-flt') || '0';


        // Prepare form data
        const formData = new FormData();
        formData.append('action', 'save_eta_record');
        formData.append('load_id', loadId);
        formData.append('date', date);
        formData.append('time', time);
        formData.append('timezone', timezone);
        formData.append('eta_type', etaType);
        formData.append('is_flt', isFlt);

        // Disable submit button
        const submitBtn = form.querySelector('button[type="submit"]') as HTMLButtonElement;
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Saving...';
        }

        // Send AJAX request
        fetch((window as any).var_from_php?.ajax_url || '/wp-admin/admin-ajax.php', {
            method: 'POST',
            body: formData
        })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                // Update button appearance
                activeButton.classList.remove('btn-outline-primary');
                activeButton.classList.add('btn-success');
                
                // Close popup
                const popup = form.closest('.popup') as HTMLElement;
                if (popup) {
                    popup.classList.remove('active');
                    document.body.classList.remove('popup-open');
                }
                
                // Show success message
                printMessage(data.data.message, 'success', 5000);
            } else {
                printMessage('Error: ' + (data.data?.message || 'Unknown error'), 'danger', 5000);
            }
        })
        .catch((error) => {
            console.error('ETA save error:', error);
            printMessage('Request failed. Please try again.', 'danger', 5000);
        })
        .finally(() => {
            // Re-enable submit button
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Save';
            }
        });
    });
};
