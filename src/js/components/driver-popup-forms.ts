import { printMessage } from './info-messages';
import { driverPopupsInstance } from './driver-popups';

/**
 * Driver Popup Forms Handler
 * Handles forms for adding ratings and notices in driver popups
 */

class DriverPopupForms {
    private ajaxUrl: string;
    private currentDriverId: string | null = null;

    constructor(ajaxUrl: string) {
        this.ajaxUrl = ajaxUrl;
        this.init();
    }

    private init(): void {
        this.handleRatingButtons();
        this.handleRatingForm();
        this.handleNoticeForm();
        this.listenForPopupOpen();
    }

    /**
     * Listen for popup open events to capture driver ID
     */
    private listenForPopupOpen(): void {
        // Listen for when popups are opened
        document.addEventListener('click', (e) => {
            const target = e.target as HTMLElement;
            
            // Check if it's a rating button
            if (target.closest('[data-driver-id]')) {
                const button = target.closest('[data-driver-id]') as HTMLElement;
                const driverId = button.getAttribute('data-driver-id');
                if (driverId) {
                    this.currentDriverId = driverId;
                }
            }
        });
    }

    /**
     * Handle rating button clicks
     */
    private handleRatingButtons(): void {
        document.querySelectorAll('.rating-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const target = e.target as HTMLButtonElement;
                
                // Remove active class from all buttons
                document.querySelectorAll('.rating-btn').forEach(b => b.classList.remove('active'));
                
                // Add active class to clicked button
                target.classList.add('active');
                
                // Set the rating value
                const selectedRating = document.getElementById('selectedRating') as HTMLInputElement;
                if (selectedRating) {
                    selectedRating.value = target.dataset.rating || '';
                }
            });
        });
    }

    /**
     * Handle rating form submission
     */
    private handleRatingForm(): void {
        const form = document.getElementById('ratingForm') as HTMLFormElement;
        if (!form) {
            return;
        }

        form.addEventListener('submit', (e) => {
            e.preventDefault();
            
            // Check if driver_id is already in the form (for modal windows)
            const existingDriverId = form.querySelector('input[name="driver_id"]') as HTMLInputElement;
            let driverId = existingDriverId ? existingDriverId.value : null;
            
            // If no driver_id in form, try to get it from popup or current context
            if (!driverId) {
                driverId = this.currentDriverId || this.getDriverIdFromPopup('driverRatingName');
                
                // Set the driver ID in the hidden field if it exists
                const driverIdField = document.getElementById('ratingDriverId') as HTMLInputElement;
                if (driverIdField && driverId) {
                    driverIdField.value = driverId;
                }
            }
            
            if (driverId) {
                const formData = new FormData(form);
                
                // Add action for AJAX
                formData.set('action', 'add_driver_rating');
                
                this.submitForm(formData, 'rating');
            } else {
                printMessage('No driver ID found!', 'danger', 3000);
                return;
            }
        });
    }

    /**
     * Handle notice form submission
     */
    private handleNoticeForm(): void {
        const form = document.getElementById('noticeForm') as HTMLFormElement;
        if (!form) return;

        form.addEventListener('submit', (e) => {
            e.preventDefault();
            
            // Check if driver_id is already in the form (for modal windows)
            const existingDriverId = form.querySelector('input[name="driver_id"]') as HTMLInputElement;
            let driverId = existingDriverId ? existingDriverId.value : null;
            
            // If no driver_id in form, try to get it from popup or current context
            if (!driverId) {
                driverId = this.currentDriverId || this.getDriverIdFromPopup('driverNoticeName');
                
                // Set the driver ID in the hidden field if it exists
                const driverIdField = document.getElementById('noticeDriverId') as HTMLInputElement;
                if (driverIdField && driverId) {
                    driverIdField.value = driverId;
                }
            }
            
            if (driverId) {
                const formData = new FormData(form);
                
                // Add action for AJAX
                formData.set('action', 'add_driver_notice');
                
                this.submitForm(formData, 'notice');
            } else {
                printMessage('No driver ID found!', 'danger', 3000);
                return;
            }
        });
    }

    /**
     * Get driver ID from popup element
     */
    private getDriverIdFromPopup(elementId: string): string | null {
        const element = document.getElementById(elementId);
        const driverId = element ? element.getAttribute('data-driver-id') : null;
        
        return driverId;
    }

    /**
     * Submit form via AJAX
     */
    private submitForm(formData: FormData, type: 'rating' | 'notice'): void {
        fetch(this.ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    printMessage('Failed to parse server response', 'danger', 3000);
                    throw new Error('Invalid JSON response');
                }
            });
        })
        .then(data => {
            if (data.success) {
                this.handleSuccess(type);
            } else {
                this.handleError(type, data.data || 'Unknown error');
            }
        })
        .catch(error => {
            printMessage('Network error occurred', 'danger', 3000);
            this.handleError(type, error.message || 'Network error');
        });
    }

    /**
     * Handle successful form submission
     */
    private handleSuccess(type: 'rating' | 'notice'): void {
        const formId = type === 'rating' ? 'ratingForm' : 'noticeForm';
        const form = document.getElementById(formId) as HTMLFormElement;
        
        if (form) {
            form.reset();
            
            // Remove active class from rating buttons
            if (type === 'rating') {
                document.querySelectorAll('.rating-btn').forEach(b => b.classList.remove('active'));
            }
        }
        
        // Show success message
        this.showMessage(`${type === 'rating' ? 'Rating' : 'Notice'} added successfully!`, 'success');
        
        // Check if we're in a popup context (driver search table)
        const isInPopup = this.isInPopupContext();
        
        if (isInPopup) {
            // Update popup data without page reload
            this.updatePopupData(type);
        } else {
            // Reload page for single post context
            setTimeout(() => {
                location.reload();
            }, 1000); // Small delay to show success message
        }
    }

    /**
     * Handle form submission error
     */
    private handleError(type: 'rating' | 'notice', error: string): void {
        this.showMessage(`Error adding ${type}: ${error}`, 'error');
    }

    /**
     * Check if we're in a popup context (driver search table)
     */
    private isInPopupContext(): boolean {
        // Check if we're in a popup by looking for popup-specific elements
        const ratingPopup = document.getElementById('driver-rating-popup');
        const noticePopup = document.getElementById('driver-notice-popup');
        
        return !!(ratingPopup || noticePopup);
    }

    /**
     * Update popup data after successful submission
     */
    private updatePopupData(type: 'rating' | 'notice'): void {
        // Get driver ID from current context
        const driverId = this.currentDriverId || this.getDriverIdFromPopup(type === 'rating' ? 'driverRatingName' : 'driverNoticeName');
        
        if (!driverId) {
            console.error('No driver ID found for popup update');
            return;
        }

        // Call the appropriate function to get updated data
        const action = type === 'rating' ? 'get_driver_ratings' : 'get_driver_notices';
        
        const formData = new FormData();
        formData.append('action', action);
        formData.append('driver_id', driverId);

        fetch(this.ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.updatePopupContent(type, data.data);
            }
        })
        .catch(error => {
            console.error('Error updating popup data:', error);
        });
    }

    /**
     * Update popup content with new data
     */
    private updatePopupContent(type: 'rating' | 'notice', data: any): void {
        if (type === 'rating') {
            this.updateRatingPopup(data);
        } else {
            this.updateNoticePopup(data);
        }
    }

    /**
     * Update rating popup content
     */
    private updateRatingPopup(data: any): void {
        // Find the ratings container in the popup
        const ratingsContainer = document.getElementById('driverRatingContent');
        
        if (ratingsContainer && driverPopupsInstance) {
            // Use existing method from DriverPopups
            (driverPopupsInstance as any).displayRatings(data, ratingsContainer);
        }
    }

    /**
     * Update notice popup content
     */
    private updateNoticePopup(data: any): void {
        // Find the notices container in the popup
        const noticesContainer = document.getElementById('driverNoticeContent');
        
        if (noticesContainer && driverPopupsInstance) {
            // Use existing method from DriverPopups
            (driverPopupsInstance as any).displayNotices(data, noticesContainer);
        }
    }

    /**
     * Show message to user using existing printMessage function
     */
    private showMessage(message: string, type: 'success' | 'error'): void {
        // Map 'error' to 'danger' to match your existing function signature
        const messageType = type === 'error' ? 'danger' : type;
        
        printMessage(message, messageType, 3000);
    }
}

export default DriverPopupForms;
