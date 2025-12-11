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
                BarChart: new (element: HTMLElement) => {
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
    width?: number;
    height?: number;
    pieSliceText?: string;
    pieSliceTextStyle?: {
        fontSize?: number;
        bold?: boolean;
    };
    legend?: {
        position?: 'center' | 'right' | 'left' | 'top' | 'bottom';
        textStyle?: {
            fontSize?: number;
        };
    };
    chartArea?: {
        width?: string;
        height?: string;
        left?: string;
        top?: string;
    };
    fontSize?: number;
    tooltip?: {
        textStyle?: {
            fontSize?: number;
        };
    };
}

export interface BarChartOptions {
    title?: string;
    width?: number;
    height?: number;
    legend?: {
        position?: 'center' | 'right' | 'left' | 'top' | 'bottom' | 'none';
        textStyle?: {
            fontSize?: number;
        };
    };
    chartArea?: {
        width?: string;
        height?: string;
        left?: string;
        top?: string;
        right?: string;
        bottom?: string;
    };
    hAxis?: {
        title?: string;
        textStyle?: {
            fontSize?: number;
        };
        baseline?: number;
        gridlines?: {
            count?: number;
        };
        viewWindow?: {
            min?: number;
            max?: number;
        };
    };
    vAxis?: {
        title?: string;
        textStyle?: {
            fontSize?: number;
        };
    };
    bars?: {
        groupWidth?: string;
    };
    fontSize?: number;
    tooltip?: {
        textStyle?: {
            fontSize?: number;
        };
    };
    colors?: string[];
    annotations?: {
        textStyle?: {
            fontSize?: number;
            bold?: boolean;
        };
        alwaysOutside?: boolean;
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
 * Create and draw a Horizontal Bar Chart
 */
export function drawBarChart(
    element: HTMLElement,
    dataArray: Array<Array<string | number>>,
    options: BarChartOptions = {}
): void {
    if (!(window as any).google || !(window as any).google.visualization) {
        console.error('Google Charts visualization not available');
        return;
    }

    // Convert annotations column to proper format if present
    // Google Charts requires annotation column header to be an object with type and role
    const processedData: any[] = [];
    dataArray.forEach((row, index) => {
        if (index === 0 && row.length > 2 && row[row.length - 1] === 'Annotation') {
            // Replace 'Annotation' header with proper annotation role object
            const newRow: any[] = [...row];
            newRow[row.length - 1] = { type: 'string', role: 'annotation' };
            processedData.push(newRow);
        } else {
            processedData.push(row);
        }
    });

    const chartData = (window as any).google.visualization.arrayToDataTable(processedData);

    const defaultOptions: BarChartOptions = {
        legend: { position: 'none' },
        chartArea: {
            left: '20%',
            top: '5%',
            width: '70%',
            height: '90%',
        },
        hAxis: {
            textStyle: {
                fontSize: 12,
            },
        },
        vAxis: {
            textStyle: {
                fontSize: 12,
            },
        },
        fontSize: 12,
    };

    const finalOptions = { ...defaultOptions, ...options };

    const chart = new (window as any).google.visualization.BarChart(element);
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

