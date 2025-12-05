import { printMessage } from './info-messages';
import { driverPopupsInstance } from './driver-popups';
import { populateLoadSelect } from '../utils/load-select';

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
        this.handleDriverPageRatingModal();
        this.setupRatingConstraints();
        this.handleAutoBlockExclude();

        // Reset UI state on popup open and update currentDriverId
        document.addEventListener('tms:rating-popup-open', () => {
            this.resetRatingUIState();
            // Update currentDriverId from popup element when it opens
            const driverId = this.getDriverIdFromPopup('driverRatingName');
            if (driverId) {
                this.currentDriverId = driverId;
            }
        });
        
        // Update currentDriverId when notice popup opens
        document.addEventListener('tms:notice-popup-open', (e: any) => {
            if (e.detail && e.detail.driverId) {
                this.currentDriverId = e.detail.driverId;
            }
        });
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
                const rating = parseInt(target.dataset.rating || '0', 10);
                // Prevent selecting 3-5 when Canceled selected
                if (this.isCanceledSelected() && rating > 2) {
                    printMessage('For Canceled loads you can set rating 1-2 only.', 'warning', 2500);
                    return;
                }
                
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
        // Use event delegation to handle forms that may be created dynamically (e.g., in modals)
        document.addEventListener('submit', (e) => {
            const target = e.target as HTMLFormElement;
            
            // Check if this is the rating form
            if (!target || target.id !== 'ratingForm') {
                return;
            }
            
            e.preventDefault();
            e.stopPropagation();
            // Validate constraint on submit as well
            const selectedRatingInput = document.getElementById('selectedRating') as HTMLInputElement;
            const selectedRatingVal = selectedRatingInput ? parseInt(selectedRatingInput.value || '0', 10) : 0;
            if (this.isCanceledSelected() && selectedRatingVal > 2) {
                printMessage('For Canceled loads you can set rating 1-2 only.', 'danger', 3000);
                return;
            }
            
            // Priority 1: ALWAYS get driver_id from popup element FIRST (most reliable, updated immediately on click)
            // This ensures we get the current driver ID even if form field hasn't been updated yet
            let driverId = this.getDriverIdFromPopup('driverRatingName');
            
            // Priority 2: If not found in popup, check if driver_id is already in the form (for modal windows)
            if (!driverId) {
                const existingDriverId = target.querySelector('input[name="driver_id"]') as HTMLInputElement;
                driverId = existingDriverId ? existingDriverId.value : null;
            }
            
            // Priority 3: If still not found, try currentDriverId as fallback
            if (!driverId) {
                driverId = this.currentDriverId;
            }
            
            // Always update the hidden field in the form with the correct driver_id
            if (driverId) {
                const driverIdField = document.getElementById('ratingDriverId') as HTMLInputElement;
                if (driverIdField) {
                    driverIdField.value = driverId;
                }
                // Also ensure the form field has the correct value
                const existingDriverId = target.querySelector('input[name="driver_id"]') as HTMLInputElement;
                if (existingDriverId && existingDriverId.value !== driverId) {
                    existingDriverId.value = driverId;
                }
                // Also update currentDriverId for consistency
                this.currentDriverId = driverId;
            }
            
            if (driverId) {
                const formData = new FormData(target);
                
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
     * Setup constraints: when Canceled selected, disable rating buttons 3-5
     */
    private setupRatingConstraints(): void {
        const loadSelect = document.getElementById('loadNumber') as HTMLSelectElement | null;
        if (!loadSelect) return;

        const applyState = () => {
            const canceled = this.isCanceledSelected();
            const buttons = Array.from(document.querySelectorAll<HTMLButtonElement>('.rating-btn'));
            buttons.forEach(btn => {
                const val = parseInt(btn.dataset.rating || '0', 10);
                // Always restore base outline classes first
                if (val === 1) {
                    btn.className = 'btn btn-outline-danger rating-btn';
                } else if (val >= 2 && val <= 4) {
                    btn.className = 'btn btn-outline-warning rating-btn';
                } else if (val === 5) {
                    btn.className = 'btn btn-outline-success rating-btn';
                }
                // Remove active state in Canceled mode
                if (canceled) {
                    btn.classList.remove('active');
                }
                // Disable 3-5 in Canceled mode
                const shouldDisable = canceled && val > 2;
                btn.disabled = shouldDisable;
            });
            // Reset selectedRating when Canceled
            const selectedRating = document.getElementById('selectedRating') as HTMLInputElement | null;
            if (selectedRating && canceled) {
                selectedRating.value = '';
            }
        };

        // Initial apply
        applyState();
        // On change
        loadSelect.addEventListener('change', applyState);
    }

    private resetRatingUIState(): void {
        // Re-enable all rating buttons and clear selection
        document.querySelectorAll<HTMLButtonElement>('.rating-btn').forEach(btn => {
            btn.disabled = false;
            btn.classList.remove('active');
            // Restore original outline classes
            const rating = parseInt(btn.dataset.rating || '0', 10);
            if (rating === 1) {
                btn.className = 'btn btn-outline-danger rating-btn';
            } else if (rating >= 2 && rating <= 4) {
                btn.className = 'btn btn-outline-warning rating-btn';
            } else if (rating === 5) {
                btn.className = 'btn btn-outline-success rating-btn';
            }
        });
        const selectedRating = document.getElementById('selectedRating') as HTMLInputElement | null;
        if (selectedRating) selectedRating.value = '';
        // Re-apply constraints based on current select value
        const loadSelect = document.getElementById('loadNumber') as HTMLSelectElement | null;
        if (loadSelect) {
            // Trigger change to apply constraint state
            const event = new Event('change');
            loadSelect.dispatchEvent(event);
        }
    }

    private isCanceledSelected(): boolean {
        const loadSelect = document.getElementById('loadNumber') as HTMLSelectElement | null;
        return !!(loadSelect && loadSelect.value === 'Canceled');
    }

    /**
     * Handle notice form submission
     */
    private handleNoticeForm(): void {
        const form = document.getElementById('noticeForm') as HTMLFormElement;
        if (!form) return;

        form.addEventListener('submit', (e) => {
            e.preventDefault();
            
            // Always prioritize the hidden field in the form (most reliable)
            const driverIdField = document.getElementById('noticeDriverId') as HTMLInputElement;
            let driverId = driverIdField ? driverIdField.value : null;
            
            // Fallback to other methods if hidden field is empty
            if (!driverId || driverId.trim() === '') {
                // Check if driver_id is already in the form (for modal windows)
                const existingDriverId = form.querySelector('input[name="driver_id"]') as HTMLInputElement;
                driverId = existingDriverId ? existingDriverId.value : null;
                
                // If still no driver_id, try to get it from popup or current context
                if (!driverId) {
                    driverId = this.currentDriverId || this.getDriverIdFromPopup('driverNoticeName');
                    
                    // Set the driver ID in the hidden field if it exists
                    if (driverIdField && driverId) {
                        driverIdField.value = driverId;
                    }
                }
            }
            
            if (driverId && driverId.trim() !== '') {
                const formData = new FormData(form);
                
                // Ensure driver_id is always set correctly
                formData.set('driver_id', driverId);
                
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
        
        // Also check if we're in a Bootstrap modal (not a popup)
        const ratingModal = document.getElementById('ratingModal');
        const noticeModal = document.getElementById('noticeModal');
        
        // Return true only if it's a popup, not a modal
        // Modals should trigger page reload, popups should update without reload
        return !!(ratingPopup || noticePopup) && !(ratingModal || noticeModal);
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
                if (type === 'rating') {
                    // For ratings, data.data now contains both ratings and available_loads
                    this.updatePopupContent(type, data.data.ratings);
                    // Also update available loads
                    this.updateAvailableLoadsFromResponse(data.data.available_loads);
                } else {
                    this.updatePopupContent(type, data.data);
                }
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
        // Reset rating UI state when new driver ratings arrive
        this.resetRatingUIState();
        // Also reset load select to placeholder and re-apply constraints
        const loadSelect = document.getElementById('loadNumber') as HTMLSelectElement | null;
        if (loadSelect) {
            loadSelect.selectedIndex = 0; // placeholder
            const evt = new Event('change');
            loadSelect.dispatchEvent(evt);
        }

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
     * Update available loads from response data
     */
    private updateAvailableLoadsFromResponse(availableLoads: any[]): void {
        const loadSelect = document.getElementById('loadNumber') as HTMLSelectElement;
        const loadsInfo = document.getElementById('loadsInfo') as HTMLElement;
        
        if (!loadSelect || !loadsInfo) {
            return;
        }

        populateLoadSelect(loadSelect, loadsInfo, availableLoads);
    }

    /**
     * Handle rating modal on driver page
     */
    private handleDriverPageRatingModal(): void {
        const ratingModal = document.getElementById('ratingModal');
        const loadSelect = document.getElementById('loadNumber') as HTMLSelectElement;
        const loadsInfo = document.getElementById('loadsInfo') as HTMLElement;
        
        if (ratingModal && loadSelect && loadsInfo) {
            ratingModal.addEventListener('show.bs.modal', () => {
                this.loadAvailableLoadsForDriverPage(loadSelect, loadsInfo);
            });
        }
    }

    /**
     * Load available loads for driver page rating modal
     */
    private loadAvailableLoadsForDriverPage(loadSelect: HTMLSelectElement, loadsInfo: HTMLElement): void {
        // Get driver ID from hidden input in the form
        const driverIdInput = document.querySelector('input[name="driver_id"]') as HTMLInputElement;
        
        if (!driverIdInput || !driverIdInput.value) {
            loadsInfo.textContent = 'Driver ID not found';
            loadsInfo.className = 'text-danger';
            return;
        }
        
        const driverId = driverIdInput.value;

        loadsInfo.textContent = 'Loading available loads...';
        loadsInfo.className = 'text-muted';
        loadSelect.innerHTML = '<option value="">Select a load...</option>';
        
        const formData = new FormData();
        formData.append('action', 'get_driver_ratings');
        formData.append('driver_id', driverId);
        
        fetch(this.ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data && data.data.available_loads) {
                const loads = data.data.available_loads;
                populateLoadSelect(loadSelect, loadsInfo, loads);
            } else {
                loadsInfo.textContent = 'Error loading loads';
                loadsInfo.className = 'text-danger';
            }
        })
        .catch(error => {
            console.error('Error loading available loads:', error);
            loadsInfo.textContent = 'Error loading loads: ' + error.message;
            loadsInfo.className = 'text-danger';
        });
    }

    // populateLoadSelect moved to utils/load-select.ts

    /**
     * Show message to user using existing printMessage function
     */
    private showMessage(message: string, type: 'success' | 'error'): void {
        // Map 'error' to 'danger' to match your existing function signature
        const messageType = type === 'error' ? 'danger' : type;
        
        printMessage(message, messageType, 3000);
    }

    /**
     * Handle auto block exclude checkbox
     */
    private handleAutoBlockExclude(): void {
        const checkbox = document.getElementById('exclude-from-auto-block') as HTMLInputElement;
        if (!checkbox) {
            return;
        }

        checkbox.addEventListener('change', (e) => {
            const target = e.target as HTMLInputElement;
            const driverIdInput = document.querySelector('input[name="driver_id"]') as HTMLInputElement;
            
            if (!driverIdInput) {
                return;
            }

            const driverId = parseInt(driverIdInput.value, 10);
            if (!driverId) {
                return;
            }

            // Disable checkbox while saving
            checkbox.disabled = true;

            const formData = new FormData();
            formData.append('action', 'update_driver_auto_block_exclude');
            formData.append('driver_id', driverId.toString());
            formData.append('exclude_from_auto_block', target.checked ? '1' : '0');

            fetch(this.ajaxUrl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    printMessage(data.data?.message || 'Setting updated successfully', 'success', 3000);
                } else {
                    printMessage(data.data?.message || 'Failed to update setting', 'danger', 5000);
                    // Revert checkbox state on error
                    target.checked = !target.checked;
                }
            })
            .catch(error => {
                console.error('Error updating auto block exclude:', error);
                printMessage('Network error occurred', 'danger', 5000);
                // Revert checkbox state on error
                target.checked = !target.checked;
            })
            .finally(() => {
                checkbox.disabled = false;
            });
        });
    }

    /**
     * Load and display driver statistics
     */
    public loadDriverStatistics(driverId: number): void {
        const container = document.getElementById('driver-statistics-container');
        if (!container) {
            return;
        }

        // Show loading state
        container.innerHTML = `
            <div class="col-12">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading driver statistics...</p>
                </div>
            </div>
        `;

        // Create form data
        const formData = new FormData();
        formData.append('action', 'get_driver_statistics');
        formData.append('driver_id', driverId.toString());
        
        // Get nonce from hidden input
        const nonceInput = document.getElementById('driver-statistics-nonce') as HTMLInputElement;
        const nonce = nonceInput ? nonceInput.value : '';
        formData.append('nonce', nonce);
        
        // console.log('Sending AJAX request with:', {
        //     action: 'get_driver_statistics',
        //     driver_id: driverId,
        //     nonce: nonce
        // });

        // Fetch statistics
        fetch(this.ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.displayDriverStatistics(data.data);
            } else {
                this.showStatisticsError(data.data || 'Failed to load statistics');
            }
        })
        .catch(error => {
            console.error('Error loading driver statistics:', error);
            this.showStatisticsError('Network error occurred');
        });
    }

    /**
     * Display driver statistics in cards
     */
    private displayDriverStatistics(stats: any): void {
        const container = document.getElementById('driver-statistics-container');
        if (!container) return;

        const formatCurrency = (amount: number): string => {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD',
                minimumFractionDigits: 0,
                maximumFractionDigits: 2
            }).format(amount);
        };

        let html = '<div class="row">';

        // Financial Statistics
        html += `
            <div class="col-md-4 mb-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Gross</h5>
                        <h3 class="card-text">${formatCurrency(stats.total_gross)}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title">Driver Earnings</h5>
                        <h3 class="card-text">${formatCurrency(stats.total_driver_earnings)}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Profit</h5>
                        <h3 class="card-text">${formatCurrency(stats.total_profit)}</h3>
                    </div>
                </div>
            </div>
        `;

        // Load Statistics
        html += `
            <div class="col-md-3 mb-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title">Delivered</h5>
                        <h3 class="card-text">${stats.delivered_loads}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card bg-danger text-white">
                    <div class="card-body">
                        <h5 class="card-title">Cancelled</h5>
                        <h3 class="card-text">${stats.cancelled_loads}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <h5 class="card-title">TONU</h5>
                        <h3 class="card-text">${stats.tonu_loads}</h3>
                    </div>
                </div>
            </div>
        `;

        // Show loaded loads only if there are any
        if (stats.loaded_loads > 0) {
            html += `
                <div class="col-md-3 mb-3">
                    <div class="card bg-secondary text-white">
                        <div class="card-body">
                            <h5 class="card-title">Loaded</h5>
                            <h3 class="card-text">${stats.loaded_loads}</h3>
                        </div>
                    </div>
                </div>
            `;
        }

        // Show waiting on PU only if there are any
        if (stats.waiting_pu_loads > 0) {
            html += `
                <div class="col-md-3 mb-3">
                    <div class="card bg-dark text-white">
                        <div class="card-body">
                            <h5 class="card-title">Waiting on PU</h5>
                            <h3 class="card-text">${stats.waiting_pu_loads}</h3>
                        </div>
                    </div>
                </div>
            `;
        }

        html += '</div>';
        container.innerHTML = html;
    }

    /**
     * Show error message for statistics loading
     */
    private showStatisticsError(message: string): void {
        const container = document.getElementById('driver-statistics-container');
        if (!container) return;

        container.innerHTML = `
            <div class="col-12">
                <div class="alert alert-danger" role="alert">
                    <h5 class="alert-heading">Error Loading Statistics</h5>
                    <p>${message}</p>
                    <button class="btn btn-outline-danger btn-sm" onclick="location.reload()">Retry</button>
                </div>
            </div>
        `;
    }
}

export default DriverPopupForms;
