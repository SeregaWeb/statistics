// Function to get the maximum pick up date from existing checkpoints
const getMaxPickUpDate = (): string | null => {
    const pickUpDateInputs = document.querySelectorAll<HTMLInputElement>('input[name="pick_up_location_date[]"]');
    let maxDate: string | null = null;

    pickUpDateInputs.forEach((input) => {
        const dateValue = input.value;
        if (dateValue) {
            if (!maxDate || new Date(dateValue) > new Date(maxDate)) {
                maxDate = dateValue;
            }
        }
    });

    return maxDate;
};

// Function to validate delivery date against pick up dates
const validateDeliveryDate = (deliveryDate: string): boolean => {
    const maxPickUpDate = getMaxPickUpDate();
    
    if (!maxPickUpDate || !deliveryDate) {
        return true; // Allow if no pick up dates exist or delivery date is empty
    }

    return new Date(deliveryDate) >= new Date(maxPickUpDate);
};

// Function to show validation error
const showDateValidationError = (shipperDate: HTMLInputElement) => {
    shipperDate.classList.add('is-invalid');
    
    // Remove existing error message if any
    const existingError = shipperDate.parentElement?.querySelector('.invalid-feedback');
    if (existingError) {
        existingError.remove();
    }

    // Add error message
    const errorDiv = document.createElement('div');
    errorDiv.className = 'invalid-feedback';
    errorDiv.textContent = 'Delivery date cannot be earlier than the latest pick up date';
    shipperDate.parentElement?.appendChild(errorDiv);
};

// Function to clear validation error
const clearDateValidationError = (shipperDate: HTMLInputElement) => {
    shipperDate.classList.remove('is-invalid');
    const existingError = shipperDate.parentElement?.querySelector('.invalid-feedback');
    if (existingError) {
        existingError.remove();
    }
};

// Function to update date input min attribute
const updateDateInputMin = (shipperDate: HTMLInputElement, stopType: HTMLSelectElement) => {
    const selectedValue = stopType.value;
    
    if (selectedValue === 'delivery_location') {
        const maxPickUpDate = getMaxPickUpDate();
        if (maxPickUpDate) {
            shipperDate.min = maxPickUpDate;
        } else {
            shipperDate.removeAttribute('min');
        }
    } else {
        shipperDate.removeAttribute('min');
    }
};

// eslint-disable-next-line import/prefer-default-export
export const changeStopType = () => {
    const stopType = document.querySelector<HTMLSelectElement>('.js-shipper-stop-type');
    // eslint-disable-next-line @wordpress/no-unused-vars-before-return
    const dateDelivery = document.querySelector<HTMLInputElement>('.js-delivery-date-setup');
    // eslint-disable-next-line @wordpress/no-unused-vars-before-return
    const datePickUp = document.querySelector<HTMLInputElement>('.js-pick-up-date-setup');
    const shipperDate = document.querySelector<HTMLInputElement>('.js-shipper-date');

    if (!stopType || !shipperDate) return;

    // Add event listener for date input changes
    shipperDate.addEventListener('change', () => {
        const selectedStopType = stopType.value;
        
        // Update min attribute in case pick up dates have changed
        updateDateInputMin(shipperDate, stopType);
        
        if (selectedStopType === 'delivery_location') {
            const isValid = validateDeliveryDate(shipperDate.value);
            if (!isValid) {
                showDateValidationError(shipperDate);
            } else {
                clearDateValidationError(shipperDate);
            }
        } else {
            clearDateValidationError(shipperDate);
        }
    });

    stopType.addEventListener('change', (event) => {
        const selectedValue = (event.target as HTMLSelectElement).value;

        // Clear any existing validation errors
        clearDateValidationError(shipperDate);

        // Update min attribute for date input
        updateDateInputMin(shipperDate, stopType);

        if (selectedValue === 'pick_up_location' && datePickUp) {
            shipperDate.value = datePickUp.value;
        } else if (selectedValue === 'delivery_location' && dateDelivery) {
            shipperDate.value = dateDelivery.value;
            
            // Validate delivery date when switching to delivery type
            if (shipperDate.value) {
                const isValid = validateDeliveryDate(shipperDate.value);
                if (!isValid) {
                    showDateValidationError(shipperDate);
                }
            }
        }
    });
};
