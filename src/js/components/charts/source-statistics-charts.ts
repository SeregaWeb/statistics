/**
 * Source Statistics Charts Component
 * Handles Google Charts initialization for source statistics page
 */

import {
    initGoogleCharts,
    parseChartDataFromAttribute,
    drawPieChart
} from './google-charts-utils';

interface SourceDataItem {
    label: string;
    post_count: number;
    total_profit: string;
}

interface SourcesData {
    [key: string]: SourceDataItem;
}

export function initSourceStatisticsCharts(): void {
    const postCountChartElement = document.getElementById('sourcePostCountChart');
    const profitChartElement = document.getElementById('sourceProfitChart');

    if (!postCountChartElement && !profitChartElement) {
        return;
    }

    // Get data from data attributes
    const sourcesData = parseChartDataFromAttribute(postCountChartElement || profitChartElement) as SourcesData | null;

    if (!sourcesData || Object.keys(sourcesData).length === 0) {
        return;
    }

    initGoogleCharts(() => {
        if (postCountChartElement) {
            const dataArray: Array<Array<string | number>> = [['Source', 'Post Count']];
            Object.keys(sourcesData).forEach((key) => {
                const source = sourcesData[key];
                dataArray.push([source.label, parseInt(String(source.post_count), 10)]);
            });

            drawPieChart(postCountChartElement, dataArray, {
                title: 'Loads',
            });
        }

        if (profitChartElement) {
            const dataArray: Array<Array<string | number>> = [['Source', 'Total Profit']];
            Object.keys(sourcesData).forEach((key) => {
                const source = sourcesData[key];
                // Remove commas and parse as float
                const profit = parseFloat(String(source.total_profit).replace(/,/g, ''));
                dataArray.push([source.label, profit]);
            });

            drawPieChart(profitChartElement, dataArray, {
                title: 'Profit',
                formatDollar: true,
            });
        }
    });
}
