/**
 * Location Vehicle Charts Component
 * Handles chart filter dropdown and chart switching for location-vehicle tab
 */

interface ChartConfig {
    id: string;
    useBar: boolean;
}

const chartMap: Record<string, ChartConfig> = {
    'home-location': { id: 'stateChart', useBar: true },
    'vehicle-type': { id: 'vehicleTypeChart', useBar: false },
    'nationality': { id: 'nationalityChart', useBar: true },
    'languages': { id: 'languageChart', useBar: false },
    'loads-by-state': { id: 'loadsByStateChart', useBar: true },
};

export function initLocationVehicleCharts(): void {
    const chartFilter = document.getElementById('chartFilter') as HTMLSelectElement;
    
    if (!chartFilter) {
        return;
    }
    
    chartFilter.addEventListener('change', function() {
        const selectedChart = this.value;
        const chartContainers = document.querySelectorAll('.chart-container');
        
        // Hide all charts
        chartContainers.forEach((container) => {
            (container as HTMLElement).style.display = 'none';
        });
        
        // Show selected chart
        const selectedContainer = document.querySelector(`.chart-container[data-chart="${selectedChart}"]`) as HTMLElement;
        if (selectedContainer) {
            selectedContainer.style.display = 'block';
            
            // Re-initialize the selected chart
            setTimeout(() => {
                const chartConfig = chartMap[selectedChart];
                if (chartConfig) {
                    const chartElement = document.getElementById(chartConfig.id);
                    if (chartElement) {
                        // Reset initialization flag
                        chartElement.dataset.initialized = 'false';
                        
                        // Directly initialize the chart using global function
                        if (typeof (window as any).initDriversChart === 'function') {
                            (window as any).initDriversChart(chartConfig.id, chartConfig.useBar);
                        } else {
                            // Fallback: trigger window resize and call initDriversStatisticsCharts
                            window.dispatchEvent(new Event('resize'));
                            if (typeof (window as any).initDriversStatisticsCharts === 'function') {
                                (window as any).initDriversStatisticsCharts();
                            }
                        }
                    }
                }
            }, 300);
        }
        
        // Update URL without reload
        const url = new URL(window.location.href);
        url.searchParams.set('chart', selectedChart);
        window.history.replaceState(null, '', url);
    });
}
