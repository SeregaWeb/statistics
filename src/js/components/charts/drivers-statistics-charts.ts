/**
 * Drivers Statistics Charts Component
 * Handles Google Charts initialization for driver statistics page
 */

import {
    initGoogleCharts,
    parseChartDataFromAttribute,
    drawPieChart,
    drawBarChart,
    convertToChartDataArray,
    ChartDataRow
} from './google-charts-utils';

interface ChartDataItem extends ChartDataRow {
    label: string;
    value: number;
}

export function initChart(chartId: string, useBarChart: boolean = false): void {
    const chartElement = document.getElementById(chartId);
    if (!chartElement) {
        return;
    }

    // Check if chart is already initialized
    if (chartElement.dataset.initialized === 'true') {
        return;
    }

    const chartData = parseChartDataFromAttribute(chartElement) as ChartDataItem[] | null;
    if (!chartData || chartData.length === 0) {
        return;
    }

    // Get actual container dimensions
    const getContainerSize = () => {
        const rect = chartElement.getBoundingClientRect();
        return {
            width: rect.width || parseInt(getComputedStyle(chartElement).width, 10) || 800,
            height: parseInt(getComputedStyle(chartElement).height, 10) || 600,
        };
    };

    initGoogleCharts(() => {
        const containerSize = getContainerSize();
        const dataArray = convertToChartDataArray(chartData, ['Type', 'Count']);
        
        if (useBarChart) {
            // Use horizontal bar chart for charts with many categories
            // Calculate dynamic height based on number of data points
            const dataCount = dataArray.length - 1; // Subtract header row
            const minBarHeight = 35; // Minimum height per bar in pixels
            const calculatedHeight = Math.max(containerSize.height, dataCount * minBarHeight + 100);
            
            // Add annotations column for displaying values on bars
            const dataWithAnnotations: Array<Array<string | number>> = dataArray.map((row, index) => {
                if (index === 0) {
                    // Header row - add annotation column header
                    return [...row, 'Annotation'];
                }
                // Data rows - add value as annotation
                return [...row, String(row[1])];
            });
            
            drawBarChart(chartElement, dataWithAnnotations, {
                width: containerSize.width,
                height: calculatedHeight,
                chartArea: {
                    left: '15%',
                    top: '0%',
                    width: '82%',
                    height: '98%',
                    right: '3%',
                    bottom: '2%',
                },
                hAxis: {
                    textStyle: {
                        fontSize: 13,
                    },
                    baseline: 0,
                    gridlines: {
                        count: 5,
                    },
                    viewWindow: {
                        min: 0,
                    },
                },
                vAxis: {
                    textStyle: {
                        fontSize: 13,
                    },
                },
                bars: {
                    groupWidth: '90%',
                },
                fontSize: 13,
                colors: ['#4285f4'],
                annotations: {
                    textStyle: {
                        fontSize: 12,
                        bold: true,
                    },
                    alwaysOutside: false,
                },
            });
        } else {
            // Use pie chart for charts with few categories
            drawPieChart(chartElement, dataArray, {
                width: containerSize.width,
                height: containerSize.height,
                pieSliceText: 'value',
                pieSliceTextStyle: {
                    fontSize: 14,
                    bold: true,
                },
                legend: { 
                    position: 'right',
                    textStyle: {
                        fontSize: 12,
                    },
                },
                chartArea: { 
                    width: '75%', 
                    height: '85%',
                    left: '5%',
                    top: '5%',
                },
                fontSize: 14,
                tooltip: {
                    textStyle: {
                        fontSize: 12,
                    },
                },
            });
        }
        // Mark as initialized
        chartElement.dataset.initialized = 'true';
    });
} 

export function initDriversStatisticsCharts(): void {
    // Initialize only visible charts
    // Check which chart is currently visible based on chart container visibility
    const chartConfigs = [
        { id: 'stateChart', useBar: true, container: 'home-location' },
        { id: 'vehicleTypeChart', useBar: false, container: 'vehicle-type' },
        { id: 'nationalityChart', useBar: true, container: 'nationality' },
        { id: 'languageChart', useBar: false, container: 'languages' },
    ];
    
    chartConfigs.forEach((config) => {
        const chartElement = document.getElementById(config.id);
        if (chartElement) {
            // Check if the chart container is visible
            const container = chartElement.closest('.chart-container') as HTMLElement;
            if (container) {
                const isVisible = (container.style.display !== 'none' && 
                                 window.getComputedStyle(container).display !== 'none') ||
                                 !container.hasAttribute('style');
                if (isVisible) {
                    // Reset initialization flag to allow re-initialization
                    chartElement.dataset.initialized = 'false';
                    initChart(config.id, config.useBar);
                }
            } else {
                // If no container found, initialize anyway (for backward compatibility)
                chartElement.dataset.initialized = 'false';
                initChart(config.id, config.useBar);
            }
        }
    });
    
    // Initialize charts when tabs are shown (for hidden tabs)
    const tabElements = document.querySelectorAll('#driversStatisticsTabs button[data-bs-toggle="tab"]');
    tabElements.forEach((tabElement) => {
        tabElement.addEventListener('shown.bs.tab', (event) => {
            // Initialize charts in the newly shown tab
            const targetId = (event.target as HTMLElement).getAttribute('data-bs-target');
            if (targetId) {
                const tabPane = document.querySelector(targetId);
                if (tabPane) {
                    // Reset initialization flag to allow re-initialization with correct size
                    const chartConfigs = [
                        { id: 'stateChart', useBar: true },
                        { id: 'vehicleTypeChart', useBar: false },
                        { id: 'nationalityChart', useBar: true },
                        { id: 'languageChart', useBar: false },
                    ];
                    chartConfigs.forEach((config) => {
                        const chartElement = document.getElementById(config.id);
                        if (chartElement) {
                            chartElement.dataset.initialized = 'false';
                        }
                    });
                    // Delay to ensure tab is fully visible and container has correct size
                    setTimeout(() => {
                        chartConfigs.forEach((config) => {
                            initChart(config.id, config.useBar);
                        });
                    }, 200);
                }
            }
        });
    });
}

// Make initChart available globally for inline scripts
declare global {
    interface Window {
        initDriversChart?: (chartId: string, useBarChart?: boolean) => void;
    }
}

if (typeof window !== 'undefined') {
    window.initDriversChart = initChart;
}

// Make initChart available globally for inline scripts
declare global {
    interface Window {
        initDriversChart?: (chartId: string, useBarChart?: boolean) => void;
    }
}

if (typeof window !== 'undefined') {
    window.initDriversChart = initChart;
}
