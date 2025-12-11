/**
 * Drivers Statistics Tabs Handler
 * Handles tab switching with URL parameters and page reload
 */

export function initDriversStatisticsTabs(): void {
    // Handle tab switching with URL parameter and page reload
    // Prevent Bootstrap default behavior and reload immediately
    const tabButtons = document.querySelectorAll('#driversStatisticsTabs button[data-tab-name]');
    
    tabButtons.forEach((button) => {
        button.addEventListener('click', function(event) {
            event.preventDefault(); // Prevent Bootstrap default tab switching
            event.stopPropagation(); // Stop event bubbling
            
            const tabName = this.getAttribute('data-tab-name');
            if (tabName) {
                const url = new URL(window.location.href);
                url.searchParams.set('tab', tabName);
                // Reload page immediately to load data for the new tab
                window.location.href = url.toString();
            }
        });
    });
}

