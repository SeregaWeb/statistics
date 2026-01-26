/**
 * Loads by Route Chart Component
 * Handles filters and chart initialization for loads-by-route chart
 * Supports comparison of two periods
 */

function updateLoadsByRouteFilters(): void {
    const compareToggle = document.getElementById('routeCompareToggle') as HTMLInputElement;
    const countrySelect = document.getElementById('routeCountry') as HTMLSelectElement;
    
    const url = new URL(window.location.href);
    
    // Handle comparison toggle
    if (compareToggle) {
        if (compareToggle.checked) {
            url.searchParams.set('route_compare', '1');
            
            // Get period 1 values
            const year1Select = document.getElementById('routeYear1') as HTMLSelectElement;
            const month1Select = document.getElementById('routeMonth1') as HTMLSelectElement;
            
            // Get period 2 values
            const year2Select = document.getElementById('routeYear2') as HTMLSelectElement;
            const month2Select = document.getElementById('routeMonth2') as HTMLSelectElement;
            
            if (year1Select) {
                url.searchParams.set('route_year1', year1Select.value);
            }
            if (month1Select) {
                if (month1Select.value === '') {
                    url.searchParams.delete('route_month1');
                } else {
                    url.searchParams.set('route_month1', month1Select.value);
                }
            }
            if (year2Select) {
                url.searchParams.set('route_year2', year2Select.value);
            }
            if (month2Select) {
                if (month2Select.value === '') {
                    url.searchParams.delete('route_month2');
                } else {
                    url.searchParams.set('route_month2', month2Select.value);
                }
            }
            
            // Remove single period params
            url.searchParams.delete('route_year');
            url.searchParams.delete('route_month');
        } else {
            url.searchParams.delete('route_compare');
            
            // Get single period values
            const yearSelect = document.getElementById('routeYear') as HTMLSelectElement;
            const monthSelect = document.getElementById('routeMonth') as HTMLSelectElement;
            
            if (yearSelect) {
                url.searchParams.set('route_year', yearSelect.value);
            }
            if (monthSelect) {
                if (monthSelect.value === '') {
                    url.searchParams.delete('route_month');
                } else {
                    url.searchParams.set('route_month', monthSelect.value);
                }
            }
            
            // Remove comparison params
            url.searchParams.delete('route_year1');
            url.searchParams.delete('route_month1');
            url.searchParams.delete('route_year2');
            url.searchParams.delete('route_month2');
        }
    }
    
    if (countrySelect) {
        url.searchParams.set('route_country', countrySelect.value);
    }
    
    // Ensure correct chart tab is selected on reload
    // Ensure correct tab is selected on reload
    url.searchParams.set('tab', 'loads-by-route');
    url.searchParams.delete('chart'); // Remove chart parameter as it's no longer needed
    window.location.href = url.toString();
}

function initLoadsByRouteChart(chartId: string = 'loadsByRouteChart'): void {
    const chartElement = document.getElementById(chartId);
    if (!chartElement) {
        return;
    } 
    
    // Check if chart data exists
    const chartData = chartElement.getAttribute('data-chart-data');
    if (!chartData || chartData === '[]' || chartData === 'null') {
        return;
    }
    
    // Check if container is visible (check both old container and new tab)
    const container = chartElement.closest('.chart-container') as HTMLElement;
    const tabPane = document.getElementById('loads-by-route') as HTMLElement;
    const containerToCheck = container || tabPane;
    
    if (containerToCheck) {
        const computedStyle = window.getComputedStyle(containerToCheck);
        const isVisible = computedStyle.display !== 'none' && 
            (containerToCheck.classList.contains('show') || 
             containerToCheck.classList.contains('active') ||
             !containerToCheck.classList.contains('fade'));
        
        if (!isVisible) {
            // Wait for container to become visible
            setTimeout(() => initLoadsByRouteChart(chartId), 200);
            return;
        }
    }
    
    // Reset initialization flag
    chartElement.dataset.initialized = 'false';
    
    // Initialize chart
    if (typeof (window as any).initDriversChart === 'function') {
        (window as any).initDriversChart(chartId, true);
    } else if (typeof (window as any).initDriversStatisticsCharts === 'function') {
        (window as any).initDriversStatisticsCharts();
    }
}

export function initLoadsByRouteChartComponent(): void {
    const compareToggle = document.getElementById('routeCompareToggle') as HTMLInputElement;
    const countrySelect = document.getElementById('routeCountry') as HTMLSelectElement;
    
    // Single period selectors
    const yearSelect = document.getElementById('routeYear') as HTMLSelectElement;
    const monthSelect = document.getElementById('routeMonth') as HTMLSelectElement;
    
    // Comparison period selectors
    const year1Select = document.getElementById('routeYear1') as HTMLSelectElement;
    const month1Select = document.getElementById('routeMonth1') as HTMLSelectElement;
    const year2Select = document.getElementById('routeYear2') as HTMLSelectElement;
    const month2Select = document.getElementById('routeMonth2') as HTMLSelectElement;
    
    // Add event listeners
    if (compareToggle) {
        compareToggle.addEventListener('change', updateLoadsByRouteFilters);
    }
    if (countrySelect) {
        countrySelect.addEventListener('change', updateLoadsByRouteFilters);
    }
    
    // Single period listeners
    if (yearSelect) {
        yearSelect.addEventListener('change', updateLoadsByRouteFilters);
    }
    if (monthSelect) {
        monthSelect.addEventListener('change', updateLoadsByRouteFilters);
    }
    
    // Comparison period listeners
    if (year1Select) {
        year1Select.addEventListener('change', updateLoadsByRouteFilters);
    }
    if (month1Select) {
        month1Select.addEventListener('change', updateLoadsByRouteFilters);
    }
    if (year2Select) {
        year2Select.addEventListener('change', updateLoadsByRouteFilters);
    }
    if (month2Select) {
        month2Select.addEventListener('change', updateLoadsByRouteFilters);
    }
    
    // Check if this tab is active
    const tabPane = document.getElementById('loads-by-route') as HTMLElement;
    const isActive = tabPane && (tabPane.classList.contains('show') || tabPane.classList.contains('active'));
    
    // Try to initialize charts immediately if tab is active
    const compareMode = compareToggle && compareToggle.checked;
    
    if (isActive) {
        setTimeout(() => {
            if (compareMode) {
                // Initialize both comparison charts
                initLoadsByRouteChart('loadsByRouteChart1');
                initLoadsByRouteChart('loadsByRouteChart2');
            } else {
                // Initialize single chart
                initLoadsByRouteChart('loadsByRouteChart');
            }
        }, 100);
    } else {
        // Try to initialize anyway (for backward compatibility with old container)
        if (compareMode) {
            initLoadsByRouteChart('loadsByRouteChart1');
            initLoadsByRouteChart('loadsByRouteChart2');
        } else {
            initLoadsByRouteChart('loadsByRouteChart');
        }
    }
    
    // Also try when chart container becomes visible (check both old container and new tab)
    const chartContainer = document.querySelector('.chart-container[data-chart="loads-by-route"]') as HTMLElement;
    const containerToObserve = chartContainer || tabPane;
    
    if (containerToObserve) {
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
                    const isVisible = containerToObserve.style.display !== 'none' && 
                        window.getComputedStyle(containerToObserve).display !== 'none';
                    if (isVisible) {
                        setTimeout(() => {
                            if (compareMode) {
                                initLoadsByRouteChart('loadsByRouteChart1');
                                initLoadsByRouteChart('loadsByRouteChart2');
                            } else {
                                initLoadsByRouteChart('loadsByRouteChart');
                            }
                        }, 300);
                    }
                }
            });
        });
        observer.observe(containerToObserve, { attributes: true, attributeFilter: ['style', 'class'] });
    }
}
