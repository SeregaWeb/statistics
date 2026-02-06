// eslint-disable-next-line import/prefer-default-export
import { printMessage } from './info-messages';
import { disabledBtnInForm } from './disabled-btn-in-form';
import Popup from '../parts/popup-window';

/**
 * Create new vehicle
 */
export const createVehicle = (urlAjax) => {
    const form = document.querySelector('.js-create-vehicle');
    if (!form) return;

    form.addEventListener('submit', (e: Event) => {
        e.preventDefault();

        const target = e.target as HTMLFormElement;
        
        // Prevent double submission
        if (target.dataset.submitting === 'true') {
            return false;
        }
        
        // Validate required fields before submission
        if (!target.checkValidity()) {
            // Trigger browser's native validation UI
            target.reportValidity();
            return false;
        }
        
        // Mark form as submitting
        target.dataset.submitting = 'true';
        disabledBtnInForm(target);

        // Include all form fields, even hidden ones
        const formData = new FormData(target);
        
        // First, process visible inputs (they have priority)
        // Then process hidden inputs, but don't overwrite non-empty values with empty ones
        const allInputs = target.querySelectorAll('input[type="number"], input[type="text"], input[type="checkbox"], input[type="date"], select');
        allInputs.forEach((input) => {
            const htmlInput = input as HTMLInputElement | HTMLSelectElement;
            if (htmlInput.name && !htmlInput.disabled) {
                // Check if input is in a visible block
                const parentBlock = htmlInput.closest('.js-fields-semi-box, .js-dock-high-section, .js-eld-section');
                const isVisible = !parentBlock || (parentBlock as HTMLElement).style.display !== 'none';
                
                if (htmlInput.type === 'checkbox') {
                    formData.set(htmlInput.name, (htmlInput as HTMLInputElement).checked ? '1' : '');
                } else {
                    const value = htmlInput.value || '';
                    // If field already exists with a non-empty value, don't overwrite with empty
                    // But always overwrite if current input is visible or has a non-empty value
                    const existingValue = formData.get(htmlInput.name);
                    if (isVisible || value !== '' || !existingValue || existingValue === '') {
                        formData.set(htmlInput.name, value);
                    }
                }
            }
        });
        
        formData.append('action', 'add_vehicle');
        formData.append('nonce', (form.querySelector('input[name="nonce"]') as HTMLInputElement)?.value || '');

        const options = {
            method: 'POST',
            body: formData,
        };

        fetch(urlAjax, options)
            .then((res) => res.json())
            .then((requestStatus) => {
                if (requestStatus.success) {
                    const newUrl = new URL(window.location.href);
                    newUrl.searchParams.set('vehicle', requestStatus.data.vehicle_id);
                    window.location.href = newUrl.toString();
                    return true;
                }
                printMessage(`${requestStatus.data.message}`, 'danger', 8000);
                target.dataset.submitting = 'false';
                disabledBtnInForm(target, true);
                return false;
            })
            .catch((error) => {
                printMessage(`'Request failed' ${error}`, 'danger', 8000);
                target.dataset.submitting = 'false';
                disabledBtnInForm(target, true);
                return false;
            });
    });
};

/**
 * Upload vehicle file helper
 */
export const uploadFileVehicle = (ajaxUrl) => {
    const forms = document.querySelectorAll('.js-upload-vehicle-helper');

    forms.forEach((form) => {
        // Use capture phase (true) to handle event before it reaches container handlers
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();

            const target = e.target as HTMLFormElement;
            
            // Disable submit button to prevent multiple requests
            const submitBtn = target.querySelector<HTMLButtonElement>('button[type="submit"], button.btn-success');
            let originalText: string | null = null;
            if (submitBtn) {
                submitBtn.disabled = true;
                originalText = submitBtn.textContent;
                if (originalText) {
                    submitBtn.textContent = 'Uploading...';
                }
            }

            const action = 'upload_vehicle_helper';
            const popupInstance = new Popup();
            const formData = new FormData(target);

            formData.append('action', action);

            const options = {
                method: 'POST',
                body: formData,
            };

            fetch(ajaxUrl, options)
                .then((res) => res.json())
                .then((requestStatus) => {
                    if (requestStatus.success) {
                        printMessage(requestStatus.data.message, 'success', 8000);

                        // @ts-ignore
                        const mainPopup = e.target.closest('.js-upload-popup');
                        let searchBtn: HTMLButtonElement | null = null;

                        if (mainPopup) {
                            const { id } = mainPopup;
                            searchBtn = document.querySelector<HTMLButtonElement>(`button[data-href="#${id}"]`);
                        }

                        popupInstance.forceCloseAllPopup();
                        
                        // Update UI without reload - show uploaded icon next to label
                        if (searchBtn) {
                            // Get field name from form data
                            const form = e.target as HTMLFormElement;
                            const fileInput = form.querySelector<HTMLInputElement>('input[name="file_name"]');
                            const fieldName = fileInput?.value;
                            
                            if (fieldName) {
                                // For multiple files (dot_inspection), get total file count from response
                                // For single files, use 1
                                const fileCount = fieldName === 'dot_inspection' 
                                    ? (requestStatus.data?.total_count || requestStatus.data?.file_ids?.length || 0)
                                    : 1;
                                updateFileUploadUI(fieldName, true, fileCount);
                            }
                        }
                    } else {
                        // eslint-disable-next-line no-alert
                        printMessage(`Error upload file: ${requestStatus.data.message}`, 'danger', 8000);
                        
                        // Re-enable button on error
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            if (originalText) {
                                submitBtn.textContent = originalText;
                            }
                        }
                    }
                })
                .catch((error) => {
                    printMessage(`Request failed: ${error}`, 'danger', 8000);
                    console.error('Request failed:', error);
                    
                    // Re-enable button on error
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        if (originalText) {
                            submitBtn.textContent = originalText;
                        }
                    }
                });
        }, true); // Capture phase - handle before bubbling
    });
};

import { confirmDeleteIfNeeded } from './file-delete-confirm';

/**
 * Field names (image-fields value) for which delete confirmation is required (vehicle files).
 */
const VEHICLE_FILE_FIELDS_REQUIRING_CONFIRM = new Set<string>([
    'vehicle_registration',
    'fleet_registration_id_card',
]);

/**
 * Remove one vehicle file
 */
export const removeOneVehicleFile = (ajaxUrl) => {
    const deleteForms = document.querySelectorAll('.js-remove-one-vehicle');
    const deleteFormsNoFormBtn = document.querySelectorAll('.js-remove-one-no-form-btn');

    // Handle form-based deletion
    deleteForms &&
        deleteForms.forEach((item) => {
            item.addEventListener('submit', (event) => {
                event.preventDefault();
                const { target } = event;

                if (!confirmDeleteIfNeeded(target as Element, VEHICLE_FILE_FIELDS_REQUIRING_CONFIRM)) {
                    return;
                }

                // @ts-ignore
                disabledBtnInForm(target);
                // @ts-ignore
                const formData = new FormData(target);
                const action = 'remove_one_vehicle';

                formData.append('action', action);

                const options = {
                    method: 'POST',
                    body: formData,
                };

                fetch(ajaxUrl, options)
                    .then((res) => res.json())
                    .then((requestStatus) => {
                        if (requestStatus.success) {
                            const message = requestStatus.data?.message || 'File removed successfully';
                            printMessage(message, 'success', 8000);
                            // @ts-ignore
                            // Get field name from form
                            const form = target as HTMLFormElement;
                            const fieldInput = form.querySelector('input[name="image-fields"]') as HTMLInputElement;
                            const fieldName = fieldInput?.value;
                            
                            // For dot_inspection (multiple files), update UI without reload
                            if (fieldName === 'dot_inspection') {
                                // Get remaining file count from response or reload to get accurate count
                                // For now, reload to ensure accurate count
                                setTimeout(() => {
                                    window.location.reload();
                                }, 100);
                            } else {
                                // For single file fields, update UI and reload
                                updateFileUploadUI(fieldName, false);
                                setTimeout(() => {
                                    window.location.reload();
                                }, 100);
                            }
                        } else {
                            // @ts-ignore
                            disabledBtnInForm(target, true);
                            const errorMessage = requestStatus.data?.message || requestStatus.message || 'Error removing file';
                            printMessage(`Error: ${errorMessage}`, 'danger', 8000);
                        }
                    })
                    .catch((error) => {
                        printMessage(`Request failed: ${error}`, 'danger', 8000);
                        // @ts-ignore
                        disabledBtnInForm(target, true);
                        console.error('Request failed:', error);
                    });
            });
        });

    // Handle div-based deletion
    deleteFormsNoFormBtn &&
        deleteFormsNoFormBtn.forEach((button) => {
            button.addEventListener('click', (event) => {
                event.preventDefault();
                
                // Find the parent div with data
                const parentDiv = button.closest('.js-remove-one-no-form');
                if (!parentDiv) return;

                // Check if this is a vehicle file
                const isVehicle = parentDiv.classList.contains('js-remove-one-vehicle') ||
                    parentDiv.querySelector('.js-remove-one-vehicle') !== null;

                if (!isVehicle) return; // Skip if not a vehicle

                if (!confirmDeleteIfNeeded(parentDiv, VEHICLE_FILE_FIELDS_REQUIRING_CONFIRM)) {
                    return;
                }

                // Disable the button to prevent multiple clicks
                const btn = button as HTMLButtonElement;
                if (btn.disabled) return; // Already processing
                
                btn.disabled = true;
                const originalText = btn.textContent;
                if (originalText) {
                    btn.textContent = 'Deleting...';
                }

                // Collect data from hidden inputs in the div
                const formData = new FormData();
                const hiddenInputs = parentDiv.querySelectorAll<HTMLInputElement>('input[type="hidden"]');
                
                hiddenInputs.forEach((input) => {
                    formData.append(input.name, input.value);
                });

                formData.append('action', 'remove_one_vehicle');

                const options = {
                    method: 'POST',
                    body: formData,
                };

                fetch(ajaxUrl, options)
                    .then((res) => res.json())
                    .then((requestStatus) => {
                        if (requestStatus.success) {
                            const message = requestStatus.data?.message || 'File removed successfully';
                            printMessage(message, 'success', 8000);
                            
                            // Reload page to update file list (button will be reset after reload)
                            setTimeout(() => {
                                window.location.reload();
                            }, 1000);
                        } else {
                            // Re-enable button on error
                            btn.disabled = false;
                            if (originalText) {
                                btn.textContent = originalText;
                            }
                            const errorMessage = requestStatus.data?.message || requestStatus.message || 'Error removing file';
                            printMessage(errorMessage, 'danger', 8000);
                        }
                    })
                    .catch((error) => {
                        printMessage(`Request failed: ${error}`, 'danger', 8000);
                        // Re-enable button on error
                        btn.disabled = false;
                        if (originalText) {
                            btn.textContent = originalText;
                        }
                        console.error('Request failed:', error);
                    });
            });
        });
};

/**
 * Handle create button click
 */
const handleCreateButton = (urlAjax) => {
    const createBtn = document.querySelector('.js-submit-create-vehicle');
    if (!createBtn) return;

    createBtn.addEventListener('click', (e) => {
        e.preventDefault();
        
        // Prevent double click
        const btn = createBtn as HTMLButtonElement;
        if (btn.disabled) {
            return;
        }
        
        const form = document.querySelector('.js-create-vehicle') as HTMLFormElement;
        if (!form) return;
        
        // Check if form is already submitting
        if (form.dataset.submitting === 'true') {
            return;
        }

        // Trigger form submit event
        form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
    });
};

/**
 * Update file upload UI after upload/delete
 * @param fieldName - Name of the field (e.g., 'vehicle_registration')
 * @param isUploaded - Whether file is uploaded (true) or deleted (false)
 * @param fileCount - Number of files (for multiple file fields like dot_inspection)
 */
const updateFileUploadUI = (fieldName: string, isUploaded: boolean, fileCount: number = 1) => {
    // Map field names to popup IDs
    const fieldToPopupMap: Record<string, string> = {
        'vehicle_registration': 'popup_upload_vehicle_registration',
        'fleet_registration_id_card': 'popup_upload_fleet_registration_id_card',
        'annual_vehicle_inspection': 'popup_upload_annual_vehicle_inspection',
        'dot_inspection': 'popup_upload_dot_inspection',
    };
    
    const popupId = fieldToPopupMap[fieldName];
    if (!popupId) return;
    
    // Find the upload button by popup ID
    const uploadBtn = document.querySelector<HTMLButtonElement>(`button[data-href="#${popupId}"]`);
    
    if (isUploaded) {
        // Special handling for multiple file field (dot_inspection)
        if (fieldName === 'dot_inspection') {
            // For multiple files: update label text with count, keep button visible
            if (uploadBtn) {
                const container = uploadBtn.closest('.js-add-new-report') || null;
                const label = container ? container.querySelector('.form-label') : null;
                
                if (label) {
                    // Update label text with file count
                    const baseText = 'DOT Inspection';
                    const countText = fileCount > 0 ? ` (${fileCount})` : '';
                    
                    // Remove existing icon to avoid duplicates
                    const existingIcon = label.querySelector('.uploaded-icon');
                    if (existingIcon) {
                        existingIcon.remove();
                    }
                    
                    // Update text content (preserve any existing HTML structure)
                    const textNode = Array.from(label.childNodes).find(node => 
                        node.nodeType === Node.TEXT_NODE || 
                        (node.nodeType === Node.ELEMENT_NODE && !(node as Element).classList.contains('uploaded-icon'))
                    );
                    
                    // Clear and rebuild label content
                    label.innerHTML = baseText + countText;
                    
                    // Add icon
                    const icon = document.createElement('span');
                    icon.className = 'uploaded-icon d-flex ms-1';
                    icon.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" fill="green" width="18px" height="18px" viewBox="0 0 14 14"><path d="M0 7a7 7 0 1 1 14 0A7 7 0 0 1 0 7z M6.278 7.697L5.045 6.464a.296.296 0 0 0-.42-.002l-.613.614a.298.298 0 0 0 .002.42l1.91 1.909a.5.5 0 0 0 .703.005l.265-.265L9.997 6.04a.291.291 0 0 0-.009-.408l-.614-.614a.29.29 0 0 0-.408-.009L6.278 7.697z" fill-rule="evenodd"></path></svg>';
                    label.appendChild(icon);
                }
            }
        } else {
            // For single file fields: hide button, show icon
            if (uploadBtn) {
                uploadBtn.style.display = 'none';
                
                // Find the label and add icon
                const container = uploadBtn.closest('.js-add-new-report') || null;
                const label = container ? container.querySelector('.form-label') : null;
                
                if (label && !label.querySelector('.uploaded-icon')) {
                    const icon = document.createElement('span');
                    icon.className = 'uploaded-icon d-flex ms-1';
                    icon.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" fill="green" width="18px" height="18px" viewBox="0 0 14 14"><path d="M0 7a7 7 0 1 1 14 0A7 7 0 0 1 0 7z M6.278 7.697L5.045 6.464a.296.296 0 0 0-.42-.002l-.613.614a.298.298 0 0 0 .002.42l1.91 1.909a.5.5 0 0 0 .703.005l.265-.265L9.997 6.04a.291.291 0 0 0-.009-.408l-.614-.614a.29.29 0 0 0-.408-.009L6.278 7.697z" fill-rule="evenodd"></path></svg>';
                    label.appendChild(icon);
                }
            }
        }
    } else {
        // After delete: show button, hide icon
        if (uploadBtn) {
            uploadBtn.style.display = '';
            
            // Find and remove icon
            const container = uploadBtn.closest('.js-add-new-report') || null;
            const label = container ? container.querySelector('.form-label') : null;
            
            if (label) {
                const icon = label.querySelector('.uploaded-icon');
                if (icon) {
                    icon.remove();
                }
                
                // For dot_inspection, reset label text
                if (fieldName === 'dot_inspection') {
                    label.textContent = 'DOT Inspection';
                }
            }
        }
    }
};

/**
 * Delete vehicle (Admin only)
 */
const deleteVehicle = (ajaxUrl: string) => {
    const deleteButtons = document.querySelectorAll('.js-delete-vehicle, .js-delete-vehicle-single');
    
    deleteButtons.forEach((button) => {
        button.addEventListener('click', (e) => {
            e.preventDefault();
            
            const btn = button as HTMLButtonElement;
            const vehicleId = btn.getAttribute('data-vehicle-id');
            
            if (!vehicleId) {
                printMessage('Vehicle ID not found', 'danger', 5000);
                return;
            }
            
            // Confirmation dialog
            const confirmed = confirm(
                'Are you sure you want to delete this vehicle?\n\n' +
                'This action cannot be undone. All vehicle data and files will be permanently deleted.\n\n' +
                'Click OK to confirm deletion.'
            );
            
            if (!confirmed) {
                return;
            }
            
            // Disable button
            btn.disabled = true;
            const originalText = btn.textContent;
            btn.textContent = 'Deleting...';
            
            const formData = new FormData();
            formData.append('action', 'delete_vehicle');
            formData.append('vehicle_id', vehicleId);
            formData.append('nonce', (document.querySelector('input[name="nonce"]') as HTMLInputElement)?.value || '');
            
            const options = {
                method: 'POST',
                body: formData,
            };
            
            fetch(ajaxUrl, options)
                .then((res) => res.json())
                .then((requestStatus) => {
                    if (requestStatus.success) {
                        printMessage(requestStatus.data.message || 'Vehicle deleted successfully', 'success', 5000);
                        
                        // Check if we're on list page or single page
                        const isListPage = btn.classList.contains('js-delete-vehicle');
                        const isSinglePage = btn.classList.contains('js-delete-vehicle-single');
                        
                        if (isListPage) {
                            // Remove row from table
                            const row = btn.closest('tr');
                            if (row) {
                                row.remove();
                            }
                            // Reload page after short delay to update list
                            setTimeout(() => {
                                window.location.reload();
                            }, 1500);
                        } else if (isSinglePage) {
                            // Redirect to vehicles list page after deletion
                            const vehiclesUrlInput = document.querySelector('#vehicles-list-url') as HTMLInputElement;
                            const vehiclesListUrl = vehiclesUrlInput?.value;
                            
                            if (vehiclesListUrl) {
                                setTimeout(() => {
                                    window.location.href = vehiclesListUrl;
                                }, 1500);
                            } else {
                                // Fallback: try to find link or construct URL
                                const vehiclesLink = document.querySelector('a[href*="vehicles"]:not([href*="vehicle-add"])');
                                if (vehiclesLink) {
                                    setTimeout(() => {
                                        window.location.href = (vehiclesLink as HTMLAnchorElement).href;
                                    }, 1500);
                                } else {
                                    // Last fallback: reload page
                                    setTimeout(() => {
                                        window.location.reload();
                                    }, 1500);
                                }
                            }
                        }
                    } else {
                        btn.disabled = false;
                        if (originalText) {
                            btn.textContent = originalText;
                        }
                        const errorMessage = requestStatus.data?.message || 'Error deleting vehicle';
                        printMessage(errorMessage, 'danger', 8000);
                    }
                })
                .catch((error) => {
                    btn.disabled = false;
                    if (originalText) {
                        btn.textContent = originalText;
                    }
                    printMessage(`Request failed: ${error}`, 'danger', 8000);
                    console.error('Request failed:', error);
                });
        });
    });
};

/**
 * Initialize all vehicle actions
 */
export const vehiclesActions = (urlAjax) => {
    createVehicle(urlAjax);
    handleCreateButton(urlAjax);
    uploadFileVehicle(urlAjax);
    removeOneVehicleFile(urlAjax);
    deleteVehicle(urlAjax);
};
