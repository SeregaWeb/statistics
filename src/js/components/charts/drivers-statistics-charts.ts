/**
 * Drivers Statistics Charts Component
 * Handles Google Charts initialization for driver statistics page
 */

import {
    initGoogleCharts,
    parseChartDataFromAttribute,
    drawPieChart,
    convertToChartDataArray,
    ChartDataRow
} from './google-charts-utils';

interface ChartDataItem extends ChartDataRow {
    label: string;
    value: number;
}

export function initDriversStatisticsCharts(): void {
    const endorsementsChartElement = document.getElementById('endorsementsChart');
    const capabilitiesChartElement = document.getElementById('capabilitiesChart');

    if (!endorsementsChartElement && !capabilitiesChartElement) {
        return;
    }

    // Get data from data attributes
    const endorsementChartData = parseChartDataFromAttribute(endorsementsChartElement) as ChartDataItem[] | null;
    const capabilitiesChartData = parseChartDataFromAttribute(capabilitiesChartElement) as ChartDataItem[] | null;

    if (!endorsementChartData && !capabilitiesChartData) {
        return;
    }

    initGoogleCharts(() => {
        if (endorsementsChartElement && endorsementChartData && endorsementChartData.length > 0) {
            const dataArray = convertToChartDataArray(endorsementChartData, ['Type', 'Count']);
            drawPieChart(endorsementsChartElement, dataArray, {
                legend: { position: 'right' },
            });
        }

        if (capabilitiesChartElement && capabilitiesChartData && capabilitiesChartData.length > 0) {
            const dataArray = convertToChartDataArray(capabilitiesChartData, ['Type', 'Count']);
            drawPieChart(capabilitiesChartElement, dataArray, {
                legend: { position: 'right' },
            });
        }
    });
}
