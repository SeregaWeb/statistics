import { printMessage } from './info-messages';
import { updateEtaTimer, initEtaTimers } from './eta-timer';

/**
 * Parse timezone string like "PDT (UTC-7)" or "PST (UTC-8)" to get offset
 */
interface TimezoneInfo {
    offset: number; // UTC offset in hours (e.g., -7 for PDT, -8 for PST)
}

/**
 * Normalize date string for input[type="date"] (expects YYYY-MM-DD).
 * If value is datetime like "2026-02-02 08:00:00", returns "2026-02-02".
 */
const normalizeDateForInput = (val: string | null | undefined): string => {
    if (val == null || val === '') return '';
    const s = val.trim();
    const datePart = s.indexOf(' ') >= 0 ? s.split(/\s/)[0] : s;
    return datePart.length >= 10 ? datePart.substring(0, 10) : datePart;
};

/**
 * Normalize time string for input[type="time"] (expects HH:MM or HH:MM:SS).
 * If value is "08:00:00", returns "08:00".
 */
const normalizeTimeForInput = (val: string | null | undefined): string => {
    if (val == null || val === '') return '';
    const s = val.trim();
    return s.length > 5 ? s.substring(0, 5) : s;
};

const parseTimezone = (timezoneStr: string): TimezoneInfo | null => {
    if (!timezoneStr) return null;
    
    // Match pattern like "PDT (UTC-7)" or "PST (UTC-8)"
    const match = timezoneStr.match(/\(UTC([+-]?\d+)\)/);
    if (!match) return null;
    
    const offset = parseInt(match[1], 10);
    if (isNaN(offset)) return null;
    
    return { offset };
};

/**
 * Get current time in destination timezone
 */
const getCurrentTimeInTimezone = (timezoneInfo: TimezoneInfo): string => {
    const nowUtc = new Date();
    const nowUtcTimestamp = nowUtc.getTime();
    
    // Convert UTC to destination timezone
    const nowDestinationTimestamp = nowUtcTimestamp + (timezoneInfo.offset * 60 * 60 * 1000);
    
    // Create date object for destination time
    const nowDestinationDate = new Date(nowDestinationTimestamp);
    const hours = String(nowDestinationDate.getUTCHours()).padStart(2, '0');
    const minutes = String(nowDestinationDate.getUTCMinutes()).padStart(2, '0');
    const seconds = String(nowDestinationDate.getUTCSeconds()).padStart(2, '0');
    
    return `${hours}:${minutes}:${seconds}`;
};

/**
 * Update location info and current time in popup
 */
let popupTimeInterval: number | null = null;

const updatePopupLocationInfo = (popup: HTMLElement, state: string | null, timezone: string | null): void => {
    // Update state
    const stateElement = popup.querySelector('.js-eta-popup-state') as HTMLElement;
    if (stateElement) {
        stateElement.textContent = state || '--';
    }
    
    // Update timezone
    const timezoneElement = popup.querySelector('.js-eta-popup-timezone') as HTMLElement;
    if (timezoneElement) {
        timezoneElement.textContent = timezone || '--';
    }
    
    // Clear existing interval
    if (popupTimeInterval !== null) {
        clearInterval(popupTimeInterval);
        popupTimeInterval = null;
    }
    
    // Update current time
    const currentTimeElement = popup.querySelector('.js-eta-popup-current-time') as HTMLElement;
    if (currentTimeElement && timezone) {
        const timezoneInfo = parseTimezone(timezone);
        if (timezoneInfo) {
            // Update immediately
            currentTimeElement.textContent = getCurrentTimeInTimezone(timezoneInfo);
            
            // Update every second
            popupTimeInterval = window.setInterval(() => {
                if (popup.classList.contains('active')) {
                    currentTimeElement.textContent = getCurrentTimeInTimezone(timezoneInfo);
                } else {
                    // Stop interval if popup is closed
                    if (popupTimeInterval !== null) {
                        clearInterval(popupTimeInterval);
                        popupTimeInterval = null;
                    }
                }
            }, 1000);
        } else {
            currentTimeElement.textContent = '--:--:--';
        }
    } else if (currentTimeElement) {
        currentTimeElement.textContent = '--:--:--';
    }
};

/**
 * Load existing ETA record from database
 * Uses get_eta_record_for_display to get ETA for any user (not just current user)
 */
const loadExistingEtaRecord = (loadId: string, etaType: string, isFlt: string, popup: HTMLElement, state: string | null, timezone: string | null): Promise<any> => {
    const formData = new FormData();
    formData.append('action', 'get_eta_record_for_display');
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
            // Fill form with existing data (normalized for input type="date" and type="time")
            const form = popup.querySelector('form') as HTMLFormElement;
            if (form) {
                const dateInput = form.querySelector('input[name="date"]') as HTMLInputElement;
                const timeInput = form.querySelector('input[name="time"]') as HTMLInputElement;
                
                if (dateInput) dateInput.value = normalizeDateForInput(data.data.date);
                if (timeInput) timeInput.value = normalizeTimeForInput(data.data.time);
            }
            
            // Update location info with timezone from database (if available)
            // Use timezone from DB if available, otherwise use from button
            const dbTimezone = data.data.timezone || timezone;
            updatePopupLocationInfo(popup, state, dbTimezone);
        } else {
            // If no record exists, still update location info from button
            updatePopupLocationInfo(popup, state, timezone);
        }
        
        // Return data so it can be used in the calling function
        return data;
    })
    .catch((error) => {
        console.error('Error loading ETA record:', error);
        // On error, still update location info from button
        updatePopupLocationInfo(popup, state, timezone);
        return { success: false, data: { exists: false } };
    });
};

/**
 * ETA Popup Handler
 * Handles opening ETA popups and filling them with current data
 */

export const initEtaPopups = (): void => {
    // Store reference to the clicked button
    let clickedButton: HTMLButtonElement | null = null;
    
    // Handle popup close - clear time interval
    const clearPopupInterval = (popup: HTMLElement | null): void => {
        if (popup && (popup.id === 'popup_eta_pick_up' || popup.id === 'popup_eta_delivery')) {
            if (popupTimeInterval !== null) {
                clearInterval(popupTimeInterval);
                popupTimeInterval = null;
            }
        }
    };
    
    document.addEventListener('click', (event: Event): void => {
        const target = event.target as HTMLElement;
        
        // Check if popup is being closed
        if (target.classList.contains('js-popup-close') || target.closest('.js-popup-close')) {
            const popup = target.closest('.popup') as HTMLElement;
            clearPopupInterval(popup);
        }
        
        // Check if overlay is clicked
        if (target.classList.contains('my_overlay')) {
            const popup = target.closest('.popup') as HTMLElement;
            clearPopupInterval(popup);
        }
    });
    
    // Handle ESC key to close popup
    document.addEventListener('keydown', (event: KeyboardEvent): void => {
        if (event.key === 'Escape') {
            const activePopup = document.querySelector('.popup.active') as HTMLElement;
            clearPopupInterval(activePopup);
        }
    });
    
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
            const timezone = button.getAttribute('data-timezone');
            
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
            
            // Get shipper ETA values first (if filled in shipper form)
            const shipperEtaDate = button.getAttribute('data-shipper-eta-date');
            const shipperEtaTime = button.getAttribute('data-shipper-eta-time');
            const hasShipperEta = shipperEtaDate && shipperEtaDate !== '' && shipperEtaTime && shipperEtaTime !== '';
            
            // Check if ETA record exists in database:
            // Button has btn-success class means there's a record in DB (admin user sees it)
            const hasExistingRecord = button.classList.contains('btn-success');
            
            if (hasExistingRecord) {
                // Show popup first
                popup.classList.add('active');
                document.body.classList.add('popup-open');
                
                // Update location info immediately (will be updated again after loading from DB)
                updatePopupLocationInfo(popup, state, timezone);
                
                // Disable submit button while loading
                if (form) {
                    const submitBtn = form.querySelector('button[type="submit"]') as HTMLButtonElement;
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.textContent = 'Loading...';
                    }
                }
                
                // Load existing data from database (for all users, not just current user)
                loadExistingEtaRecord(loadId || '', etaType, isFlt, popup, state, timezone).then((data) => {
                    // After loading, check if record exists in DB
                    // If no record exists in DB but shipper ETA is filled, use shipper ETA (normalized for date/time inputs)
                    if (form && (!data || !data.success || !data.data.exists) && hasShipperEta) {
                        const dateInput = form.querySelector('input[name="date"]') as HTMLInputElement;
                        const timeInput = form.querySelector('input[name="time"]') as HTMLInputElement;
                        
                        if (dateInput) dateInput.value = normalizeDateForInput(shipperEtaDate);
                        if (timeInput) timeInput.value = normalizeTimeForInput(shipperEtaTime);
                    }
                    
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
                // Update location info in popup
                updatePopupLocationInfo(popup, state, timezone);
                
                // Fill with data from button (for new ETA records)
                // Priority: shipper ETA > current date/time (normalized for input type="date" and type="time")
                if (form) {
                    const dateInput = form.querySelector('input[name="date"]') as HTMLInputElement;
                    const timeInput = form.querySelector('input[name="time"]') as HTMLInputElement;
                    
                    const dateValue = hasShipperEta ? shipperEtaDate : (currentDate || '');
                    const timeValue = hasShipperEta ? shipperEtaTime : (currentTime || '');
                    if (dateInput) dateInput.value = normalizeDateForInput(dateValue);
                    if (timeInput) timeInput.value = normalizeTimeForInput(timeValue);
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
        const state = activeButton.getAttribute('data-state') || '';
        const etaType = activeButton.getAttribute('data-eta-type') || '';
        const isFlt = activeButton.getAttribute('data-is-flt') || '0';


        // Prepare form data
        const formData = new FormData();
        formData.append('action', 'save_eta_record');
        formData.append('load_id', loadId);
        formData.append('date', date);
        formData.append('time', time);
        formData.append('timezone', timezone);
        formData.append('state', state); // Add state for timezone recalculation
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
                
                // Build new ETA datetime string
                const newEtaDatetime = `${date} ${time}:00`;
                
                // Update button data attributes with new ETA values
                activeButton.setAttribute('data-current-date', date);
                activeButton.setAttribute('data-current-time', time);
                
                // Get timezone and other data from button
                const timezone = activeButton.getAttribute('data-timezone') || '';
                const isFlt = activeButton.getAttribute('data-is-flt') || '0';
                const etaType = activeButton.getAttribute('data-eta-type') || '';
                
                // Find or create timer container
                let timerContainer = activeButton.parentElement;
                if (!timerContainer || !timerContainer.classList.contains('d-flex')) {
                    // Create container if it doesn't exist
                    const newContainer = document.createElement('div');
                    newContainer.className = 'd-flex flex-column align-items-start gap-1';
                    activeButton.parentNode?.insertBefore(newContainer, activeButton);
                    newContainer.appendChild(activeButton);
                    timerContainer = newContainer;
                }
                
                // Check if timer element exists
                let timerElement = timerContainer.querySelector(
                    `.js-eta-timer[data-load-id="${loadId}"][data-eta-type="${etaType}"]`
                ) as HTMLElement;
                
                if (!timerElement) {
                    // Create timer element
                    timerElement = document.createElement('div');
                    timerElement.className = 'js-eta-timer';
                    timerElement.setAttribute('data-load-id', loadId);
                    timerElement.setAttribute('data-eta-type', etaType);
                    timerElement.setAttribute('data-eta-datetime', newEtaDatetime);
                    timerElement.setAttribute('data-timezone', timezone);
                    timerElement.setAttribute('data-is-flt', isFlt);
                    
                    // Get load status from the row
                    const statusSelect = activeButton.closest('tr')?.querySelector('select[name="status"]') as HTMLSelectElement;
                    const loadStatus = statusSelect?.value || activeButton.closest('tr')?.getAttribute('data-load-status') || '';
                    timerElement.setAttribute('data-load-status', loadStatus);
                    
                    // Set styles for visibility
                    timerElement.style.fontSize = '11px';
                    timerElement.style.lineHeight = '1.2';
                    timerElement.style.minHeight = '14px';
                    timerElement.style.display = 'block';
                    
                    const timerText = document.createElement('span');
                    timerText.className = 'js-eta-timer-text';
                    timerText.textContent = '--:--';
                    timerText.style.display = 'inline-block';
                    timerElement.appendChild(timerText);
                    
                    timerContainer.appendChild(timerElement);
                } else {
                    // Update existing timer
                    timerElement.setAttribute('data-eta-datetime', newEtaDatetime);
                }
                
                // Initialize or update timer immediately
                // The timer element should already be created above
                if (timerElement) {
                    // Update data attribute
                    timerElement.setAttribute('data-eta-datetime', newEtaDatetime);
                    // Initialize timer immediately
                    updateEtaTimer(loadId, etaType, newEtaDatetime);
                }
                
                // Close popup
                const popup = form.closest('.popup') as HTMLElement;
                if (popup) {
                    popup.classList.remove('active');
                    document.body.classList.remove('popup-open');
                    
                    // Clear time interval when popup is closed
                    if (popupTimeInterval !== null) {
                        clearInterval(popupTimeInterval);
                        popupTimeInterval = null;
                    }
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
