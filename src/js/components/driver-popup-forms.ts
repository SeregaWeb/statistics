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
    private ratingButtonHandler: ((e: Event) => void) | null = null;
    private statsPopupAbortController: AbortController | null = null;
    private statsPopupPendingDriverId: string | null = null;

    constructor(ajaxUrl: string) {
        this.ajaxUrl = ajaxUrl;
        this.init();
    }

    private init(): void {
        this.handleRatingButtons();
        this.handleRatingForm();
        this.handleLoadRatingButtons();
        this.handleNoticeForm();
        this.listenForPopupOpen();
        this.handleDriverPageRatingModal();
        this.setupRatingConstraints();
        this.handleAutoBlockExclude();
        this.initDriverStatsPopup();

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
     * Confirm highest rating (5) selection
     * Returns true if confirmed, false if cancelled
     */
    private confirmHighestRating(rating: number, button: HTMLButtonElement, selectedRating: HTMLInputElement | null): boolean {
        if (rating === 5) {
            const confirmed = confirm("You're choosing the highest rating for this driver. Would you like to continue?");
            return confirmed;
        }
        return true;
    }

    /**
     * Revert rating selection changes when user cancels
     */
    private revertRatingSelection(button: HTMLButtonElement, selectedRating: HTMLInputElement | null): void {
        // Remove active class from all buttons (for popup forms)
        document.querySelectorAll('.rating-btn').forEach(b => {
            b.classList.remove('active');
        });
        
        // Reset button classes to outline versions (for driver-core.ts style)
        document.querySelectorAll('.rating-btn').forEach(b => {
            const bRating = parseInt(b.getAttribute('data-rating') || '0', 10);
            if (bRating <= 1) {
                b.className = 'btn btn-outline-danger rating-btn';
            } else if (bRating <= 4) {
                b.className = 'btn btn-outline-warning rating-btn';
            } else if (bRating > 4) {
                b.className = 'btn btn-outline-success rating-btn';
            } else {
                b.className = 'btn btn-outline-secondary rating-btn';
            }
        });
        
        // Clear selected rating input
        if (selectedRating) {
            selectedRating.value = '';
        }
        
        // Remove processing flag
        button.removeAttribute('data-processing');
    }

    /**
     * Handle rating button clicks
     */
    private handleRatingButtons(): void {
        // Remove existing handler if any
        if (this.ratingButtonHandler) {
            document.removeEventListener('click', this.ratingButtonHandler);
        }
        
        // Create new handler
        this.ratingButtonHandler = (e: Event) => {
            const target = e.target as HTMLElement;
            if (!target.classList.contains('rating-btn')) {
                return;
            }
            
            const button = target as HTMLButtonElement;
            
            // Prevent duplicate processing
            if (button.hasAttribute('data-processing')) {
                e.preventDefault();
                e.stopPropagation();
                return;
            }
            
            const rating = parseInt(button.dataset.rating || '0', 10);
            
            // Check if this button is already active
            if (button.classList.contains('active')) {
                e.preventDefault();
                e.stopPropagation();
                return;
            }
            
            // Prevent selecting 3-5 when Canceled selected
            if (this.isCanceledSelected() && rating > 2) {
                printMessage('For Canceled loads you can set rating 1-2 only.', 'warning', 2500);
                e.preventDefault();
                e.stopPropagation();
                return;
            }
            
            button.setAttribute('data-processing', 'true');
            
            // Remove active class from all buttons
            document.querySelectorAll('.rating-btn').forEach(b => {
                b.classList.remove('active');
                b.removeAttribute('data-processing');
            });
            
            // Add active class to clicked button
            button.classList.add('active');
            
            // Set the rating value
            const selectedRating = document.getElementById('selectedRating') as HTMLInputElement;
            if (selectedRating) {
                selectedRating.value = button.dataset.rating || '';
            }
            
            // Confirm if selecting highest rating (5) - at the end after all changes
            if (!this.confirmHighestRating(rating, button, selectedRating)) {
                // User cancelled - revert all changes
                this.revertRatingSelection(button, selectedRating);
                e.preventDefault();
                e.stopPropagation();
                return;
            }
            
            // Remove processing flag after event completes
            setTimeout(() => {
                button.removeAttribute('data-processing');
            }, 50);
            
            e.preventDefault();
            e.stopPropagation();
        };
        
        // Add new handler
        document.addEventListener('click', this.ratingButtonHandler);
    }

    /**
     * Handle rating form submission
     */
    private handleRatingForm(): void {
        // Use event delegation to handle forms that may be created dynamically (e.g., in modals)
        document.addEventListener('submit', (e) => {
            const target = e.target as HTMLFormElement;
            if (!target) return;

            // Load rating form (from loads table – fixed load + driver)
            if (target.id === 'loadRatingForm') {
                e.preventDefault();
                e.stopPropagation();
                this.submitLoadRatingForm(target);
                return;
            }

            // Check if this is the rating form
            if (target.id !== 'ratingForm') {
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

                this.submitForm(formData, 'rating', target);
            } else {
                printMessage('No driver ID found!', 'danger', 3000);
                return;
            }
        });
    }

    /**
     * Handle load-rating popup: click on rating buttons (1–5).
     * For cancelled loads only 1–2 are allowed (buttons 3–5 are disabled in driver-popups.ts).
     */
    private handleLoadRatingButtons(): void {
        document.addEventListener('click', (e: Event) => {
            const target = (e.target as HTMLElement).closest('.load-rating-btn');
            if (!target) return;
            const button = target as HTMLButtonElement;
            if (button.disabled) return;
            e.preventDefault();
            e.stopPropagation();
            const rating = parseInt(button.dataset.rating || '0', 10);
            const loadStatusEl = document.getElementById('loadRatingLoadStatus') as HTMLInputElement | null;
            const loadStatus = loadStatusEl ? loadStatusEl.value : '';
            if (loadStatus === 'cancelled' && rating > 2) {
                printMessage('For Canceled loads you can set rating 1-2 only.', 'warning', 2500);
                return;
            }
            const selectedInput = document.getElementById('loadRatingSelectedRating') as HTMLInputElement | null;
            if (!selectedInput) return;
            document.querySelectorAll<HTMLButtonElement>('.load-rating-btn').forEach((b) => b.classList.remove('active'));
            button.classList.add('active');
            selectedInput.value = String(rating);
            if (!this.confirmHighestRating(rating, button, selectedInput)) {
                document.querySelectorAll<HTMLButtonElement>('.load-rating-btn').forEach((b) => b.classList.remove('active'));
                selectedInput.value = '';
            }
        });
    }

    /**
     * Submit load-rating form (fixed load + driver); on success reload page so row updates.
     */
    private submitLoadRatingForm(form: HTMLFormElement): void {
        const selectedInput = document.getElementById('loadRatingSelectedRating') as HTMLInputElement | null;
        const selectedVal = selectedInput ? parseInt(selectedInput.value || '0', 10) : 0;
        if (selectedVal < 1 || selectedVal > 5) {
            printMessage('Please select a rating (1–5).', 'danger', 3000);
            return;
        }
        const loadStatusEl = document.getElementById('loadRatingLoadStatus') as HTMLInputElement | null;
        const loadStatus = loadStatusEl ? loadStatusEl.value : '';
        if (loadStatus === 'cancelled' && selectedVal > 2) {
            printMessage('For Canceled loads you can set rating 1-2 only.', 'danger', 3000);
            return;
        }
        const submitBtn = form.querySelector<HTMLButtonElement>('button[type="submit"]') ||
            form.querySelector<HTMLInputElement>('input[type="submit"]');
        if (submitBtn) submitBtn.disabled = true;

        const formData = new FormData(form);
        formData.set('action', 'add_driver_rating');
        fetch(this.ajaxUrl, {
            method: 'POST',
            body: formData
        })
            .then((response) => {
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                return response.text().then((text) => {
                    try {
                        return JSON.parse(text);
                    } catch {
                        printMessage('Failed to parse server response', 'danger', 3000);
                        throw new Error('Invalid JSON response');
                    }
                });
            })
            .then((data) => {
                if (data.success) {
                    this.showMessage('Rating added successfully!', 'success');
                    setTimeout(() => {
                        document.location.reload();
                    }, 800);
                } else {
                    if (submitBtn) submitBtn.disabled = false;
                    this.showMessage(`Error: ${data.data || 'Unknown error'}`, 'error');
                }
            })
            .catch((err) => {
                if (submitBtn) submitBtn.disabled = false;
                printMessage('Network error occurred', 'danger', 3000);
                this.showMessage(`Error: ${err.message || 'Network error'}`, 'error');
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

                this.submitForm(formData, 'notice', form);
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
     * @param submittedForm If provided, the submit button is taken from this form (avoids wrong form when duplicate ids exist, e.g. ratingForm in tab)
     */
    private submitForm(formData: FormData, type: 'rating' | 'notice', submittedForm?: HTMLFormElement | null): void {
        const form = submittedForm ?? (document.getElementById(type === 'rating' ? 'ratingForm' : 'noticeForm') as HTMLFormElement | null);
        const submitBtn = form
            ? (form.querySelector<HTMLButtonElement>('button[type="submit"]') ||
               form.querySelector<HTMLInputElement>('input[type="submit"]'))
            : null;
        if (submitBtn) {
            submitBtn.disabled = true;
        }

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
                if (submitBtn) submitBtn.disabled = false;
                this.handleError(type, data.data || 'Unknown error');
            }
        })
        .catch(error => {
            if (submitBtn) submitBtn.disabled = false;
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
     * Load available loads for driver page rating modal (or popup form).
     * @param container - optional; when provided, driver_id is searched inside this element.
     */
    private loadAvailableLoadsForDriverPage(loadSelect: HTMLSelectElement, loadsInfo: HTMLElement, container?: HTMLElement): void {
        const scope = container || document;
        const driverIdInput = scope.querySelector('input[name="driver_id"]') as HTMLInputElement;
        
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

    /**
     * Init driver Statistics popup: click on .js-driver-stats-trigger loads stats HTML and opens modal.
     * Modal is found by id or created dynamically so it works even when table is loaded via AJAX.
     */
    private initDriverStatsPopup(): void {
        const getOrCreateModal = (): { modalEl: HTMLElement; bodyEl: HTMLElement } => {
            let modalEl = document.getElementById('js-driver-stats-modal');
            let bodyEl = document.getElementById('js-driver-stats-modal-body');
            if (!modalEl || !bodyEl) {
                modalEl = document.createElement('div');
                modalEl.className = 'modal fade';
                modalEl.id = 'js-driver-stats-modal';
                modalEl.setAttribute('tabindex', '-1');
                modalEl.setAttribute('aria-labelledby', 'js-driver-stats-modal-label');
                modalEl.setAttribute('aria-hidden', 'true');
                modalEl.innerHTML = `
                    <div class="modal-dialog modal-xl modal-dialog-scrollable">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="js-driver-stats-modal-label">Driver Statistics</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body" id="js-driver-stats-modal-body"></div>
                        </div>
                    </div>
                `;
                document.body.appendChild(modalEl);
                bodyEl = document.getElementById('js-driver-stats-modal-body') as HTMLElement;
            }
            return { modalEl, bodyEl };
        };

        const bindOverlayClose = (modalEl: HTMLElement): void => {
            if (modalEl.hasAttribute('data-driver-stats-overlay-bound')) {
                return;
            }
            modalEl.setAttribute('data-driver-stats-overlay-bound', 'true');
            modalEl.addEventListener('click', (e: MouseEvent) => {
                if ((e.target as HTMLElement) !== modalEl) {
                    return;
                }
                const BootstrapModal = (window as any).bootstrap?.Modal;
                if (BootstrapModal) {
                    const inst = BootstrapModal.getInstance(modalEl);
                    if (inst) {
                        inst.hide();
                    }
                } else {
                    if (!modalEl.classList.contains('show')) {
                        return;
                    }
                    modalEl.classList.remove('show');
                    modalEl.style.display = 'none';
                    modalEl.setAttribute('aria-hidden', 'true');
                    document.body.classList.remove('modal-open');
                    const b = document.getElementById('js-driver-stats-modal-backdrop');
                    if (b) {
                        b.remove();
                    }
                }
            });
        };

        document.addEventListener('click', (e) => {
            const trigger = (e.target as HTMLElement).closest('.js-driver-stats-trigger');
            if (!trigger) {
                return;
            }
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            const driverId = (trigger as HTMLElement).getAttribute('data-driver-id');
            if (!driverId) {
                return;
            }
            const { modalEl, bodyEl } = getOrCreateModal();
            bindOverlayClose(modalEl);
            bodyEl.innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
                    <p class="mt-2">Loading driver statistics...</p>
                </div>
            `;
            const BootstrapModal = (window as any).bootstrap?.Modal;
            if (BootstrapModal) {
                const modalInstance = BootstrapModal.getInstance(modalEl) || new BootstrapModal(modalEl);
                requestAnimationFrame(() => {
                    modalInstance.show();
                });
            } else {
                modalEl.classList.add('show');
                modalEl.style.display = 'block';
                modalEl.style.zIndex = '1056';
                modalEl.setAttribute('aria-hidden', 'false');
                document.body.classList.add('modal-open');
                const backdrop = document.createElement('div');
                backdrop.className = 'modal-backdrop fade show';
                backdrop.id = 'js-driver-stats-modal-backdrop';
                (backdrop as HTMLElement).style.zIndex = '1055';
                document.body.appendChild(backdrop);
                const closeHandler = (): void => {
                    modalEl.classList.remove('show');
                    modalEl.style.display = 'none';
                    modalEl.setAttribute('aria-hidden', 'true');
                    document.body.classList.remove('modal-open');
                    const b = document.getElementById('js-driver-stats-modal-backdrop');
                    if (b) b.remove();
                    modalEl.querySelector('.btn-close')?.removeEventListener('click', closeHandler);
                    backdrop.removeEventListener('click', closeHandler);
                };
                modalEl.querySelector('.btn-close')?.addEventListener('click', closeHandler);
                backdrop.addEventListener('click', closeHandler);
            }
            if (this.statsPopupAbortController) {
                this.statsPopupAbortController.abort();
            }
            this.statsPopupAbortController = new AbortController();
            this.statsPopupPendingDriverId = driverId;

            const formData = new FormData();
            formData.append('action', 'get_driver_stats_popup_html');
            formData.append('driver_id', driverId);
            const nonceEl = document.querySelector('#_wpnonce, input[name="_wpnonce"]') as HTMLInputElement;
            if (nonceEl && nonceEl.value) {
                formData.append('_wpnonce', nonceEl.value);
            }
            fetch(this.ajaxUrl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                signal: this.statsPopupAbortController.signal,
            })
                .then((res) => res.json())
                .then((data) => {
                    if (this.statsPopupPendingDriverId !== driverId) {
                        return;
                    }
                    if (data.success && data.data && data.data.html) {
                        bodyEl.innerHTML = data.data.html;
                        this.loadDriverStatistics(parseInt(driverId, 10));
                        this.initDriverStatsPopupViewSwitcher(bodyEl);
                    } else {
                        bodyEl.innerHTML = `<div class="alert alert-danger">${(data.data && data.data.message) || 'Failed to load statistics'}</div>`;
                    }
                })
                .catch((err: Error) => {
                    if (err.name === 'AbortError') {
                        return;
                    }
                    if (this.statsPopupPendingDriverId !== driverId) {
                        return;
                    }
                    bodyEl.innerHTML = '<div class="alert alert-danger">Network error</div>';
                });
        });
    }

    /**
     * Popup-only: when load select is "Canceled", disable rating buttons 3–5 and clear selected rating if > 2.
     */
    private applyPopupRatingConstraints(container: HTMLElement): void {
        const loadSelect = container.querySelector('.js-driver-stats-popup-load-select') as HTMLSelectElement;
        const canceled = !!(loadSelect && loadSelect.value === 'Canceled');
        const buttons = Array.from(container.querySelectorAll<HTMLButtonElement>('.js-driver-stats-popup-rating-btn'));
        buttons.forEach((btn) => {
            const val = parseInt(btn.getAttribute('data-rating') || '0', 10);
            const shouldDisable = canceled && val > 2;
            btn.disabled = shouldDisable;
            if (canceled && val > 2) {
                btn.classList.remove('active');
            }
        });
        const selectedInput = container.querySelector('.js-driver-stats-popup-selected-rating') as HTMLInputElement;
        if (selectedInput && canceled) {
            const current = parseInt(selectedInput.value || '0', 10);
            if (current > 2) {
                selectedInput.value = '';
            }
        }
    }

    /**
     * Popup-only: switch between stats / rating form / notice form and handle form submit with refresh.
     * Queries panels from container each time so it works after refresh (no stale refs).
     * Replaces container with a clone to drop previous click listeners and avoid duplicate requests.
     */
    private initDriverStatsPopupViewSwitcher(container: HTMLElement): void {
        const parent = container.parentElement;
        if (parent) {
            const clone = container.cloneNode(true) as HTMLElement;
            if (container.id) {
                clone.id = container.id;
            }
            parent.replaceChild(clone, container);
            container = clone;
        }
        const root = container.querySelector('.js-driver-stats-popup-root');
        if (!root) {
            return;
        }
        const driverId = (root as HTMLElement).getAttribute('data-driver-id') || '';

        const showStats = (): void => {
            const stats = container.querySelector('.js-driver-stats-popup-stats');
            const rating = container.querySelector('.js-driver-stats-popup-rating-panel');
            const notice = container.querySelector('.js-driver-stats-popup-notice-panel');
            if (stats) (stats as HTMLElement).classList.remove('d-none');
            if (rating) (rating as HTMLElement).classList.add('d-none');
            if (notice) (notice as HTMLElement).classList.add('d-none');
        };
        const showRating = (): void => {
            const stats = container.querySelector('.js-driver-stats-popup-stats');
            const rating = container.querySelector('.js-driver-stats-popup-rating-panel');
            const notice = container.querySelector('.js-driver-stats-popup-notice-panel');
            if (stats) (stats as HTMLElement).classList.add('d-none');
            if (notice) (notice as HTMLElement).classList.add('d-none');
            if (rating) (rating as HTMLElement).classList.remove('d-none');
            const loadSelect = container.querySelector('.js-driver-stats-popup-load-select') as HTMLSelectElement;
            const loadsInfo = container.querySelector('.js-driver-stats-popup-loads-info') as HTMLElement;
            if (loadSelect && loadsInfo) {
                this.loadAvailableLoadsForDriverPage(loadSelect, loadsInfo, container);
            }
            this.applyPopupRatingConstraints(container);
        };
        const showNotice = (): void => {
            const stats = container.querySelector('.js-driver-stats-popup-stats');
            const rating = container.querySelector('.js-driver-stats-popup-rating-panel');
            const notice = container.querySelector('.js-driver-stats-popup-notice-panel');
            if (stats) (stats as HTMLElement).classList.add('d-none');
            if (rating) (rating as HTMLElement).classList.add('d-none');
            if (notice) (notice as HTMLElement).classList.remove('d-none');
        };

        const refreshPopupContent = (): void => {
            const formData = new FormData();
            formData.append('action', 'get_driver_stats_popup_html');
            formData.append('driver_id', driverId);
            fetch(this.ajaxUrl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
            })
                .then((res) => res.json())
                .then((data) => {
                    if (data.success && data.data && data.data.html) {
                        container.innerHTML = data.data.html;
                        this.loadDriverStatistics(parseInt(driverId, 10));
                    }
                })
                .catch(() => {});
        };

        container.addEventListener('click', (e) => {
            const target = e.target as HTMLElement;
            if (target.closest('.js-driver-stats-popup-show-rating')) {
                e.preventDefault();
                showRating();
                return;
            }
            if (target.closest('.js-driver-stats-popup-show-notice')) {
                e.preventDefault();
                showNotice();
                return;
            }
            if (target.closest('.js-driver-stats-popup-back')) {
                e.preventDefault();
                showStats();
                return;
            }
            const ratingBtn = target.closest('.js-driver-stats-popup-rating-btn');
            if (ratingBtn) {
                e.preventDefault();
                const btn = ratingBtn as HTMLButtonElement;
                if (btn.disabled) return;
                const ratingVal = parseInt((ratingBtn as HTMLElement).getAttribute('data-rating') || '0', 10);
                const loadSelect = container.querySelector('.js-driver-stats-popup-load-select') as HTMLSelectElement;
                const isCanceled = !!(loadSelect && loadSelect.value === 'Canceled');
                if (isCanceled && ratingVal > 2) {
                    printMessage('For Canceled loads you can set rating 1-2 only.', 'warning', 2500);
                    return;
                }
                const selectedInput = container.querySelector('.js-driver-stats-popup-selected-rating') as HTMLInputElement;
                if (selectedInput) {
                    selectedInput.value = String(ratingVal);
                }
                container.querySelectorAll('.js-driver-stats-popup-rating-btn').forEach((b) => b.classList.remove('active'));
                (ratingBtn as HTMLElement).classList.add('active');
            }
        });

        container.addEventListener('change', (e) => {
            const target = e.target as HTMLElement;
            if (target.classList.contains('js-driver-stats-popup-load-select') || target.id === 'js-driver-stats-popup-load-number') {
                this.applyPopupRatingConstraints(container);
            }
        });

        container.addEventListener('submit', (e) => {
            const form = (e.target as HTMLElement).closest('form');
            if (!form) return;
            const submitBtn = form.querySelector('button[type="submit"]') as HTMLButtonElement | null;
            if (form.classList.contains('js-driver-stats-popup-rating-form')) {
                e.preventDefault();
                const selectedInput = container.querySelector('.js-driver-stats-popup-selected-rating') as HTMLInputElement;
                const ratingVal = selectedInput ? parseInt(selectedInput.value || '0', 10) : 0;
                const loadSelect = container.querySelector('.js-driver-stats-popup-load-select') as HTMLSelectElement;
                const isCanceled = !!(loadSelect && loadSelect.value === 'Canceled');
                if (ratingVal < 1 || ratingVal > 5) {
                    printMessage('Please select a rating (1–5).', 'danger', 3000);
                    return;
                }
                if (isCanceled && ratingVal > 2) {
                    printMessage('For Canceled loads you can set rating 1-2 only.', 'danger', 3000);
                    return;
                }
                if (submitBtn) submitBtn.disabled = true;
                const formData = new FormData(form);
                formData.set('action', 'add_driver_rating');
                fetch(this.ajaxUrl, { method: 'POST', body: formData, credentials: 'same-origin' })
                    .then((res) => res.json())
                    .then((data) => {
                        if (data.success) {
                            printMessage('Rating added successfully!', 'success', 3000);
                            refreshPopupContent();
                        } else {
                            if (submitBtn) submitBtn.disabled = false;
                            printMessage((data.data && data.data.message) || 'Error adding rating', 'danger', 3000);
                        }
                    })
                    .catch(() => {
                        if (submitBtn) submitBtn.disabled = false;
                        printMessage('Network error', 'danger', 3000);
                    });
                return;
            }
            if (form.classList.contains('js-driver-stats-popup-notice-form')) {
                e.preventDefault();
                if (submitBtn) submitBtn.disabled = true;
                const formData = new FormData(form);
                formData.set('action', 'add_driver_notice');
                fetch(this.ajaxUrl, { method: 'POST', body: formData, credentials: 'same-origin' })
                    .then((res) => res.json())
                    .then((data) => {
                        if (data.success) {
                            printMessage('Notice added successfully!', 'success', 3000);
                            refreshPopupContent();
                        } else {
                            if (submitBtn) submitBtn.disabled = false;
                            printMessage((data.data && data.data.message) || 'Error adding notice', 'danger', 3000);
                        }
                    })
                    .catch(() => {
                        if (submitBtn) submitBtn.disabled = false;
                        printMessage('Network error', 'danger', 3000);
                    });
            }
        });
    }
}

export default DriverPopupForms;
