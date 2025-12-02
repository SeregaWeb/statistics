/**
 * Google Charts Utilities
 * Common functions for working with Google Charts
 */

declare global {
    interface Window {
        google?: {
            charts: {
                load: (version: string, options: { packages: string[] }) => void;
                setOnLoadCallback: (callback: () => void) => void;
            };
            visualization?: {
                arrayToDataTable: (data: Array<Array<string | number>>) => any;
                PieChart: new (element: HTMLElement) => {
                    draw: (data: any, options: any) => void;
                };
                NumberFormat: new (options: { prefix?: string }) => {
                    format: (data: any, columnIndex: number) => void;
                };
            };
        };
    }
}

export interface PieChartOptions {
    title?: string;
    pieSliceText?: string;
    legend?: {
        position?: 'center' | 'right' | 'left' | 'top' | 'bottom';
    };
}

export interface ChartDataRow {
    label: string;
    value: number | string;
}

/**
 * Check if Google Charts is loaded
 */
export function isGoogleChartsLoaded(): boolean {
    return typeof (window as any).google !== 'undefined' && !!(window as any).google.charts;
}

/**
 * Initialize Google Charts and execute callback when ready
 */
export function initGoogleCharts(callback: () => void): void {
    if (!isGoogleChartsLoaded()) {
        console.error('Google Charts not loaded');
        return;
    }

    (window as any).google.charts.load('current', { packages: ['corechart'] });
    (window as any).google.charts.setOnLoadCallback(callback);
}

/**
 * Parse chart data from data attribute
 */
export function parseChartDataFromAttribute(element: HTMLElement | null, attributeName: string = 'data-chart-data'): any {
    if (!element) {
        return null;
    }

    const dataAttr = element.getAttribute(attributeName);
    if (!dataAttr) {
        return null;
    }

    try {
        return JSON.parse(dataAttr);
    } catch (e) {
        console.error('Error parsing chart data:', e);
        return null;
    }
}

/**
 * Create and draw a Pie Chart
 */
export function drawPieChart(
    element: HTMLElement,
    dataArray: Array<Array<string | number>>,
    options: PieChartOptions & { formatDollar?: boolean } = {}
): void {
    if (!(window as any).google || !(window as any).google.visualization) {
        console.error('Google Charts visualization not available');
        return;
    }

    const chartData = (window as any).google.visualization.arrayToDataTable(dataArray);

    // Apply dollar formatting if requested
    if (options.formatDollar) {
        formatDollarColumn(chartData, 1);
    }

    const { formatDollar, ...chartOptions } = options;
    const defaultOptions: PieChartOptions = {
        pieSliceText: 'value',
        legend: { position: 'center' },
    };

    const finalOptions = { ...defaultOptions, ...chartOptions };

    const chart = new (window as any).google.visualization.PieChart(element);
    chart.draw(chartData, finalOptions);
}

/**
 * Format numeric column with dollar prefix
 */
export function formatDollarColumn(data: any, columnIndex: number = 1): void {
    if (!(window as any).google || !(window as any).google.visualization) {
        return;
    }

    const formatter = new (window as any).google.visualization.NumberFormat({
        prefix: '$',
    });

    formatter.format(data, columnIndex);
}

/**
 * Convert array of objects to chart data array
 */
export function convertToChartDataArray(
    data: ChartDataRow[] | Record<string, ChartDataRow>,
    headers: [string, string],
    valueExtractor?: (item: ChartDataRow, key?: string) => number
): Array<Array<string | number>> {
    const dataArray: Array<Array<string | number>> = [headers];

    if (Array.isArray(data)) {
        data.forEach((item) => {
            const value = valueExtractor ? valueExtractor(item) : (typeof item.value === 'number' ? item.value : parseFloat(String(item.value)));
            dataArray.push([item.label, value]);
        });
    } else {
        Object.keys(data).forEach((key) => {
            const item = data[key];
            const value = valueExtractor ? valueExtractor(item, key) : (typeof item.value === 'number' ? item.value : parseFloat(String(item.value)));
            dataArray.push([item.label, value]);
        });
    }

    return dataArray;
}

