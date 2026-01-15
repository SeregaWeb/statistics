/**
 * Trailer Form Fields Toggle
 * Handles conditional field display based on trailer type and lease option
 */
export const initTrailerFormFields = () => {
    const trailerTypeSelect = document.getElementById('trailer_type');
    const dimensionsDefault = document.querySelector('.js-dimensions-default');
    const dimensionsRGN = document.querySelector('.js-dimensions-rgn');
    const dimensionsStepDeck = document.querySelector('.js-dimensions-step-deck');
    const dimensionsFlatbedHotshot = document.querySelector('.js-dimensions-flatbed-hotshot');
    const leaseSwitch = document.getElementById('lease') as HTMLInputElement;
    const leaseAgreement = document.querySelector('.js-lease-agreement');
    
    if (!trailerTypeSelect) return;
    
    function toggleDimensions() {
        const type = (trailerTypeSelect as HTMLSelectElement).value;
        
        // Hide all dimension sections
        if (dimensionsDefault) (dimensionsDefault as HTMLElement).style.display = 'none';
        if (dimensionsRGN) (dimensionsRGN as HTMLElement).style.display = 'none';
        if (dimensionsStepDeck) (dimensionsStepDeck as HTMLElement).style.display = 'none';
        if (dimensionsFlatbedHotshot) (dimensionsFlatbedHotshot as HTMLElement).style.display = 'none';
        
        // Show appropriate section
        if (type === 'rgn' && dimensionsRGN) {
            (dimensionsRGN as HTMLElement).style.display = 'block';
        } else if (type === 'step-deck' && dimensionsStepDeck) {
            (dimensionsStepDeck as HTMLElement).style.display = 'block';
        } else if ((type === 'flatbed' || type === 'hot-shot') && dimensionsFlatbedHotshot) {
            (dimensionsFlatbedHotshot as HTMLElement).style.display = 'block';
        } else if (type && dimensionsDefault) {
            (dimensionsDefault as HTMLElement).style.display = 'block';
        }
    }
    
    function toggleLeaseAgreement() {
        if (leaseSwitch && leaseAgreement) {
            if (leaseSwitch.checked) {
                (leaseAgreement as HTMLElement).style.display = 'block';
            } else {
                (leaseAgreement as HTMLElement).style.display = 'none';
            }
        }
    }
    
    trailerTypeSelect.addEventListener('change', toggleDimensions);
    toggleDimensions(); // Initialize on page load
    
    if (leaseSwitch) {
        leaseSwitch.addEventListener('change', toggleLeaseAgreement);
        toggleLeaseAgreement(); // Initialize on page load
    }
};
