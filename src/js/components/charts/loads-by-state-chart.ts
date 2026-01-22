/**
 * Loads by State Chart Component
 * Handles filters and chart initialization for loads-by-state chart
 */

function updateLoadsByStateFilters(): void {
    const typeSelect = document.getElementById('loadsLocationType') as HTMLSelectElement;
    const countrySelect = document.getElementById('loadsCountry') as HTMLSelectElement;
    const yearSelect = document.getElementById('loadsYear') as HTMLSelectElement;
    const monthSelect = document.getElementById('loadsMonth') as HTMLSelectElement;
    
    const url = new URL(window.location.href);
    if (typeSelect) {
        url.searchParams.set('loads_location_type', typeSelect.value);
    }
    if (countrySelect) {
        url.searchParams.set('loads_country', countrySelect.value);
    }
    if (yearSelect) {
        url.searchParams.set('loads_year', yearSelect.value);
    }
    if (monthSelect) {
        if (monthSelect.value === '') {
            url.searchParams.delete('loads_month');
        } else {
            url.searchParams.set('loads_month', monthSelect.value);
        }
    }
    // Ensure correct chart tab is selected on reload
    url.searchParams.set('chart', 'loads-by-state');
    window.location.href = url.toString();
}

function initLoadsByStateChart(): void {
    const chartElement = document.getElementById('loadsByStateChart');
    if (!chartElement) {
        return;
    }
    
    // Check if chart data exists
    const chartData = chartElement.getAttribute('data-chart-data');
    if (!chartData || chartData === '[]' || chartData === 'null') {
        return;
    }
    
    // Check if container is visible
    const container = chartElement.closest('.chart-container') as HTMLElement;
    if (container && container.style.display === 'none') {
        // Wait for container to become visible
        setTimeout(initLoadsByStateChart, 200);
        return;
    }
    
    // Reset initialization flag
    chartElement.dataset.initialized = 'false';
    
    // Initialize chart
    if (typeof (window as any).initDriversChart === 'function') {
        (window as any).initDriversChart('loadsByStateChart', true);
    } else if (typeof (window as any).initDriversStatisticsCharts === 'function') {
        (window as any).initDriversStatisticsCharts();
    }
}

export function initLoadsByStateChartComponent(): void {
    const typeSelect = document.getElementById('loadsLocationType') as HTMLSelectElement;
    const countrySelect = document.getElementById('loadsCountry') as HTMLSelectElement;
    const yearSelect = document.getElementById('loadsYear') as HTMLSelectElement;
    const monthSelect = document.getElementById('loadsMonth') as HTMLSelectElement;
    
    if (typeSelect) {
        typeSelect.addEventListener('change', updateLoadsByStateFilters);
    }
    if (countrySelect) {
        countrySelect.addEventListener('change', updateLoadsByStateFilters);
    }
    if (yearSelect) {
        yearSelect.addEventListener('change', updateLoadsByStateFilters);
    }
    if (monthSelect) {
        monthSelect.addEventListener('change', updateLoadsByStateFilters);
    }
    
    // Try to initialize immediately
    initLoadsByStateChart();
    
    // Also try when chart container becomes visible
    const chartContainer = document.querySelector('.chart-container[data-chart="loads-by-state"]') as HTMLElement;
    if (chartContainer) {
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
                    const isVisible = chartContainer.style.display !== 'none' && 
                        window.getComputedStyle(chartContainer).display !== 'none';
                    if (isVisible) {
                        setTimeout(initLoadsByStateChart, 300);
                    }
                }
            });
        });
        observer.observe(chartContainer, { attributes: true, attributeFilter: ['style'] });
    }
}
