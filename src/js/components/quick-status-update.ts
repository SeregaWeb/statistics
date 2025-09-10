/**
 * Quick Status Update functionality for driver table
 * Handles modal opening, form population, and submission
 */

// @ts-ignore
import { printMessage } from './info-messages';
import { updateTooltip } from './tooltip-start';

class QuickStatusUpdate {
    private modal: HTMLElement | null = null;
    private form: HTMLFormElement | null = null;
    private submitButton: HTMLElement | null = null;
    private ajaxUrl: string;
    private isInitialized: boolean = false;

    constructor(ajaxUrl: string) {
        this.ajaxUrl = ajaxUrl;
        this.init();
    }

    private init(): void {
        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.setupEventListeners());
        } else {
            this.setupEventListeners();
        }
    }

 
    private setupEventListeners(): void {
        // Prevent multiple initializations
        if (this.isInitialized) {
            return;
        }

        // Find modal elements
        this.modal = document.getElementById('quickStatusUpdateModal');
        this.form = document.querySelector('.js-quick-update-location-driver') as HTMLFormElement;
        this.submitButton = document.querySelector('.js-submit-quick-update');

        if (!this.modal || !this.form || !this.submitButton) {
            console.warn('Quick Status Update: Modal elements not found');
            return;
        }

        // Handle modal show event to populate form with driver data
        this.modal.addEventListener('show.bs.modal', (event) => {
            // @ts-ignore - Bootstrap modal event
            const button = event.relatedTarget as HTMLElement;
            if (button && button.classList.contains('js-quick-status-update')) {
                this.populateForm(button);
            }
        });

        // Handle form submission
        this.submitButton.addEventListener('click', (event) => {
            event.preventDefault();
            this.submitForm();
        });

        // Handle fill location button
        const fillLocationButton = this.form.querySelector('.js-fill-new-location');
        if (fillLocationButton) {
            fillLocationButton.addEventListener('click', (event) => {
                event.preventDefault();
                this.fillLocationByZipcode();
            });
        }

        // Mark as initialized
        this.isInitialized = true;
    }

    private closeModal(): void {
        if (!this.modal) return;

        // Try different ways to close the modal
        try {
            // Method 1: Try Bootstrap 5 API
            const windowAny = window as any;
            if (windowAny.bootstrap && windowAny.bootstrap.Modal) {
                const modalInstance = windowAny.bootstrap.Modal.getInstance(this.modal);
                if (modalInstance) {
                    modalInstance.hide();
                    return;
                }
            }

            // Method 2: Try jQuery if available
            if (windowAny.$ && windowAny.$(this.modal).modal) {
                windowAny.$(this.modal).modal('hide');
                return;
            }

            // Method 3: Manual close by triggering close button
            const closeButton = this.modal.querySelector('[data-bs-dismiss="modal"]');
            if (closeButton) {
                (closeButton as HTMLElement).click();
                return;
            }

            // Method 4: Remove modal classes and backdrop
            this.modal.classList.remove('show');
            this.modal.style.display = 'none';
            this.modal.setAttribute('aria-hidden', 'true');
            this.modal.removeAttribute('aria-modal');
            
            const backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) {
                backdrop.remove();
            }
            
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
        } catch (error) {
            console.warn('Error closing modal:', error);
        }
    }

    private updateTableRow(driverData: any): void {
        if (!driverData) return;

        // Find the table row for this driver
        const driverId = driverData.id;
        const tableRow = document.querySelector(`tr[data-driver-id="${driverId}"]`) as HTMLTableRowElement;
        
        if (!tableRow) {
            console.warn('Table row not found for driver ID:', driverId);
            return;
        }

        // Update status cell
        const statusCell = tableRow.querySelector('.driver-status') as HTMLElement;
        if (statusCell && driverData.status_text) {
            statusCell.textContent = driverData.status_text;
            statusCell.className = `driver-status ${driverData.status_class}`;
        }

        // Update location cell with HTML from backend
        const locationCell = tableRow.querySelector('.js-location-update') as HTMLElement;
        if (locationCell && driverData.location_html) {
            // Try to extract content using regex as fallback
            const htmlMatch = driverData.location_html.match(/<td[^>]*>([\s\S]*?)<\/td>/);
            if (htmlMatch) {
                const innerContent = htmlMatch[1];
                
                // Update the content
                locationCell.innerHTML = innerContent;
                
                // Extract class from HTML
                const classMatch = driverData.location_html.match(/class="([^"]*)"/);
                if (classMatch) {
                    locationCell.className = classMatch[1];
                }
            } else {
                // Fallback: try DOM parsing
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = driverData.location_html;
                const tempTd = tempDiv.querySelector('td');
                
                if (tempTd) {
                    const innerContent = tempTd.innerHTML;
                    
                    // Update the content
                    locationCell.innerHTML = innerContent;
                    
                    // Update the class as well
                    locationCell.className = tempTd.className;
                } else {
                    // Last resort: try to extract just the text content
                    const textMatch = driverData.location_html.match(/>([^<]+(?:<[^>]*>[^<]*)*)<\/td>/);
                    if (textMatch) {
                        locationCell.innerHTML = textMatch[1];
                    }
                }
            }
        }

        // Update button data attributes
        const updateButton = tableRow.querySelector('.js-quick-status-update') as HTMLElement;
        if (updateButton) {
            updateButton.setAttribute('data-driver-status', driverData.driver_status || '');
            updateButton.setAttribute('data-current-location', driverData.current_location || '');
            updateButton.setAttribute('data-current-city', driverData.current_city || '');
            updateButton.setAttribute('data-current-zipcode', driverData.current_zipcode || '');
            updateButton.setAttribute('data-latitude', driverData.latitude || '');
            updateButton.setAttribute('data-longitude', driverData.longitude || '');
            updateButton.setAttribute('data-country', driverData.country || '');
            updateButton.setAttribute('data-status-date', driverData.status_date || '');
        }

        // Initialize tooltips for any new capability icons
        updateTooltip();
    }

    private populateForm(button: HTMLElement): void {
        if (!this.form) return;

        // Get driver data from button data attributes
        const driverId = button.getAttribute('data-driver-id');
        const driverName = button.getAttribute('data-driver-name');
        const driverStatus = button.getAttribute('data-driver-status');
        const currentLocation = button.getAttribute('data-current-location');
        const currentCity = button.getAttribute('data-current-city');
        const currentZipcode = button.getAttribute('data-current-zipcode');
        const latitude = button.getAttribute('data-latitude');
        const longitude = button.getAttribute('data-longitude');
        const country = button.getAttribute('data-country');
        const statusDate = button.getAttribute('data-status-date');

        // Update modal title
        const modalTitle = document.getElementById('quickStatusUpdateModalLabel');
        if (modalTitle && driverName) {
            modalTitle.textContent = `Quick Status Update - ${driverName}`;
        }

        // Populate form fields
        const driverIdInput = this.form.querySelector('.js-id_driver') as HTMLInputElement;
        if (driverIdInput && driverId) {
            driverIdInput.value = driverId;
        }

        // Handle status field with role-based restrictions
        this.handleStatusField(driverStatus);

        const statusDateInput = this.form.querySelector('input[name="status_date"]') as HTMLInputElement;
        if (statusDateInput && statusDate) {
            statusDateInput.value = statusDate;
        }

        const locationSelect = this.form.querySelector('select[name="current_location"]') as HTMLSelectElement;
        if (locationSelect && currentLocation) {
            locationSelect.value = currentLocation;
        }

        const cityInput = this.form.querySelector('input[name="current_city"]') as HTMLInputElement;
        if (cityInput && currentCity) {
            cityInput.value = currentCity;
        }

        const zipcodeInput = this.form.querySelector('input[name="current_zipcode"]') as HTMLInputElement;
        if (zipcodeInput && currentZipcode) {
            zipcodeInput.value = currentZipcode;
        }

        const latitudeInput = this.form.querySelector('input[name="latitude"]') as HTMLInputElement;
        if (latitudeInput && latitude) {
            latitudeInput.value = latitude;
        }

        const longitudeInput = this.form.querySelector('input[name="longitude"]') as HTMLInputElement;
        if (longitudeInput && longitude) {
            longitudeInput.value = longitude;
        }

        const countryInput = this.form.querySelector('input[name="country"]') as HTMLInputElement;
        if (countryInput && country) {
            countryInput.value = country;
        }
    }

    private fillLocationByZipcode(): void {
        if (!this.form) return;

        const zipcodeInput = this.form.querySelector('input[name="current_zipcode"]') as HTMLInputElement;
        const countrySelect = this.form.querySelector('select[name="current_country"]') as HTMLSelectElement;
        const cityInput = this.form.querySelector('input[name="current_city"]') as HTMLInputElement;
        const stateSelect = this.form.querySelector('select[name="current_location"]') as HTMLSelectElement;
        const latitudeInput = this.form.querySelector('input[name="latitude"]') as HTMLInputElement;
        const longitudeInput = this.form.querySelector('input[name="longitude"]') as HTMLInputElement;
        const countryInput = this.form.querySelector('input[name="country"]') as HTMLInputElement;

        if (!zipcodeInput || !countrySelect) return;

        const zipcode = zipcodeInput.value.trim();
        const country = countrySelect.value || 'USA';

        if (!zipcode) {
            printMessage('Please enter a zip code first', 'warning', 3000);
            return;
        }

        // Use the same geocoding logic as the main form
        // This would need to be imported from the existing geocoding module
        console.log('Filling location for zipcode:', zipcode, 'country:', country);
        
        // For now, just show a message that this feature needs to be implemented
        printMessage('Location filling feature will be implemented', 'info', 3000);
    }

    private submitForm(): void {
        if (!this.form) return;

        // Validate required fields
        const requiredFields = this.form.querySelectorAll('[required]');
        let isValid = true;

        requiredFields.forEach((field) => {
            const input = field as HTMLInputElement | HTMLSelectElement;
            if (!input.value.trim()) {
                isValid = false;
                input.classList.add('is-invalid');
            } else {
                input.classList.remove('is-invalid');
            }
        });

        if (!isValid) {
            printMessage('Please fill in all required fields', 'warning', 3000);
            return;
        }

        // Prepare form data
        const formData = new FormData(this.form);
        formData.append('action', 'update_location_driver');
        
        // Handle readonly status field - if it exists, use its value instead of select
        const statusReadonly = this.form.querySelector('.js-status-readonly') as HTMLElement;
        const statusHidden = this.form.querySelector('.js-status-hidden') as HTMLInputElement;
        
        if (statusReadonly && statusReadonly.style.display !== 'none' && statusHidden && statusHidden.value) {
            // Remove the select value and use readonly value
            formData.delete('driver_status');
            formData.append('driver_status', statusHidden.value);
            console.log('Using readonly status value:', statusHidden.value);
        }
        
        // Debug: Log form data to see what's being sent
        console.log('Form data being sent:');
        for (let [key, value] of formData.entries()) {
            console.log(key, ':', value);
        }

        // Show loading state
        if (this.submitButton) {
            this.submitButton.textContent = 'Updating...';
            (this.submitButton as HTMLButtonElement).disabled = true;
        }

        // Submit form
        const options = {
            method: 'POST',
            body: formData,
        };

        // Use the passed AJAX URL
        fetch(this.ajaxUrl, options)
            .then((res) => res.json())
            .then((requestStatus) => {
                if (requestStatus.success) {
                    printMessage(requestStatus.data.message, 'success', 8000);
                    
                    // Close modal
                    if (this.modal) {
                        this.closeModal();
                    }
                    
                    // Update table row with new data
                    this.updateTableRow(requestStatus.data.updated_driver);
                } else {
                    printMessage(`Error updating status: ${requestStatus.data.message}`, 'danger', 8000);
                }
            })
            .catch((error) => {
                printMessage(`Request failed: ${error}`, 'danger', 8000);
                console.error('Quick update request failed:', error);
            })
            .finally(() => {
                // Reset button state
                if (this.submitButton) {
                    this.submitButton.textContent = 'Update Status';
                    (this.submitButton as HTMLButtonElement).disabled = false;
                }
            });
    }

    /**
     * Handle status field with role-based restrictions
     * If driver has restricted status and user can't change it, show readonly text
     */
    private handleStatusField(driverStatus: string | null): void {
        if (!this.form || !driverStatus) return;

        const statusSelect = this.form.querySelector('select[name="driver_status"]') as HTMLSelectElement;
        const statusReadonly = this.form.querySelector('.js-status-readonly') as HTMLElement;
        const statusText = this.form.querySelector('.js-status-text') as HTMLElement;
        const statusHidden = this.form.querySelector('.js-status-hidden') as HTMLInputElement;

        if (!statusSelect || !statusReadonly || !statusText || !statusHidden) return;

        console.log('handleStatusField - driverStatus:', driverStatus);
        console.log('Available options in select:', Array.from(statusSelect.options).map(opt => opt.value));

        // Restricted statuses that only Admin, Recruiter, Recruiter Team Leader can change
        const restrictedStatuses = ['no_Interview', 'expired_documents', 'blocked'];
        
        // Check if current status is restricted
        const isRestrictedStatus = restrictedStatuses.includes(driverStatus);
        console.log('isRestrictedStatus:', isRestrictedStatus);
        
        if (isRestrictedStatus) {
            // Check if user can change this status by looking at available options
            const canChangeStatus = Array.from(statusSelect.options).some(option => 
                option.value === driverStatus && option.value !== ''
            );
            console.log('canChangeStatus:', canChangeStatus);
            
            if (!canChangeStatus) {
                // User can't change this status, show readonly
                console.log('Showing readonly status:', driverStatus);
                statusSelect.style.display = 'none';
                statusSelect.removeAttribute('required'); // Remove required from select
                statusReadonly.style.display = 'block';
                statusHidden.setAttribute('required', 'required'); // Add required to hidden field
                statusText.textContent = statusSelect.options[Array.from(statusSelect.options).findIndex(opt => opt.value === driverStatus)]?.textContent || driverStatus;
                statusHidden.value = driverStatus;
                console.log('Set statusHidden.value to:', statusHidden.value);
            } else {
                // User can change this status, show select
                console.log('Showing select for restricted status:', driverStatus);
                statusSelect.style.display = 'block';
                statusSelect.setAttribute('required', 'required'); // Add required to select
                statusReadonly.style.display = 'none';
                statusHidden.removeAttribute('required'); // Remove required from hidden field
                statusSelect.value = driverStatus;
            }
        } else {
            // Not a restricted status, show select normally
            console.log('Showing select for normal status:', driverStatus);
            statusSelect.style.display = 'block';
            statusSelect.setAttribute('required', 'required'); // Add required to select
            statusReadonly.style.display = 'none';
            statusHidden.removeAttribute('required'); // Remove required from hidden field
            statusSelect.value = driverStatus;
        }
    }
}

// Initialize when DOM is ready
let quickStatusUpdateInstance: QuickStatusUpdate | null = null;

export const initQuickStatusUpdate = (ajaxUrl: string) => {
    if (!quickStatusUpdateInstance) {
        quickStatusUpdateInstance = new QuickStatusUpdate(ajaxUrl);
    }
};

