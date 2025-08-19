/**
 * Driver Capabilities Filter Component
 * Manages the dropdown filter for driver capabilities
 */

export const initCapabilitiesFilter = () => {
    // Check if Bootstrap is available
    if (!(window as any).bootstrap) {
        console.warn('Bootstrap is not available. Capabilities filter may not work properly.');
    }

    const capabilitiesDropdown = document.getElementById('capabilitiesDropdown') as HTMLButtonElement | null;
    const capabilitiesMenu = document.querySelector('.js-capabilities-menu') as HTMLUListElement | null;
    const capabilitiesCount = document.querySelector('.js-capabilities-count') as HTMLSpanElement | null;
    const capabilityCheckboxes = document.querySelectorAll('.js-capability-checkbox') as NodeListOf<HTMLInputElement>;
    const resetButton = document.querySelector('a[href*="Reset"]') as HTMLAnchorElement | null;

    if (!capabilitiesDropdown || !capabilitiesMenu || !capabilitiesCount) {
        return;
    }

    // Function to update the capabilities count
    const updateCapabilitiesCount = () => {
        const checkedBoxes = document.querySelectorAll('.js-capability-checkbox:checked') as NodeListOf<HTMLInputElement>;
        const count = checkedBoxes.length;
        capabilitiesCount.textContent = count.toString();
        
        // Update button text based on count
        if (count === 0) {
            capabilitiesDropdown.innerHTML = 'Capabilities <span class="badge bg-primary ms-1 js-capabilities-count">0</span>';
        } else {
            capabilitiesDropdown.innerHTML = `Capabilities (${count}) <span class="badge bg-primary ms-1 js-capabilities-count">${count}</span>`;
        }
    };

    // Function to clear all capabilities
    const clearCapabilities = () => {
        capabilityCheckboxes.forEach(checkbox => {
            checkbox.checked = false;
        });
        updateCapabilitiesCount();
    };

    // Initialize count on page load
    updateCapabilitiesCount();

    // Add event listeners to checkboxes
    capabilityCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateCapabilitiesCount);
    });

    // Clear capabilities when search input changes (new address search)
    const searchInputs = document.querySelectorAll('input[name="my_search"], input[name="extended_search"]') as NodeListOf<HTMLInputElement>;
    searchInputs.forEach(input => {
        input.addEventListener('input', () => {
            // Clear capabilities after a short delay to allow for new search
            setTimeout(clearCapabilities, 100);
        });
    });

    // Clear capabilities when reset button is clicked
    if (resetButton) {
        resetButton.addEventListener('click', clearCapabilities);
    }

    // Prevent dropdown from closing when clicking on checkboxes
    capabilitiesMenu.addEventListener('click', (event) => {
        if (event.target instanceof HTMLInputElement && event.target.type === 'checkbox') {
            event.stopPropagation();
        }
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', (event) => {
        if (!capabilitiesDropdown.contains(event.target as Node) && !capabilitiesMenu.contains(event.target as Node)) {
            // Check if Bootstrap is available
            if ((window as any).bootstrap && (window as any).bootstrap.Dropdown) {
                const dropdown = new (window as any).bootstrap.Dropdown(capabilitiesDropdown);
                dropdown.hide();
            } else {
                // Fallback: manually hide the dropdown
                capabilitiesMenu.classList.remove('show');
                capabilitiesDropdown.setAttribute('aria-expanded', 'false');
            }
        }
    });
}; 