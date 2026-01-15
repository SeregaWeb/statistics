/**
 * Vehicle Form Fields Toggle
 * Handles conditional field display based on vehicle type
 */
export const initVehicleFormFields = () => {
    const vehicleTypeSelect = document.getElementById('vehicle_type');
    const fieldsSemiBox = document.querySelector('.js-fields-semi-box');
    const dockHighSection = document.querySelector('.js-dock-high-section');
    const eldSection = document.querySelector('.js-eld-section');
    const dockHighCheckbox = document.getElementById('dock_high') as HTMLInputElement;
    
    if (!vehicleTypeSelect) return;
    
    function toggleVehicleFields() {
        const type = (vehicleTypeSelect as HTMLSelectElement).value;
        
        // Hide all conditional sections
        if (fieldsSemiBox) (fieldsSemiBox as HTMLElement).style.display = 'none';
        if (dockHighSection) (dockHighSection as HTMLElement).style.display = 'none';
        if (eldSection) (eldSection as HTMLElement).style.display = 'none';
        
        // Show fields for Semi truck and Box truck
        if (type === 'semi-truck' || type === 'box-truck') {
            if (fieldsSemiBox) (fieldsSemiBox as HTMLElement).style.display = 'block';
            if (dockHighSection) (dockHighSection as HTMLElement).style.display = 'block';
            if (eldSection) (eldSection as HTMLElement).style.display = 'block';
            
            // For Semi truck: Dock High always checked and disabled
            if (type === 'semi-truck' && dockHighCheckbox) {
                dockHighCheckbox.checked = true;
                dockHighCheckbox.disabled = true;
            } else if (type === 'box-truck' && dockHighCheckbox) {
                // For Box truck: Dock High can be toggled
                dockHighCheckbox.disabled = false;
            }
        } else {
            // For Cargo van, Sprinter van, Hotshot: hide Tare Weight, GVWR, Dock High, ELD Model
            // Uncheck dock_high if it was previously checked
            if (dockHighCheckbox && dockHighCheckbox.checked) {
                dockHighCheckbox.checked = false;
            }
        }
    }
    
    vehicleTypeSelect.addEventListener('change', toggleVehicleFields);
    toggleVehicleFields(); // Initialize on page load
};
