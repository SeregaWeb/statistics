/**
 * Finance Statistics Charts Component
 * Handles Google Charts initialization for finance statistics page
 */

import {
    initGoogleCharts,
    parseChartDataFromAttribute,
    drawPieChart
} from './google-charts-utils';

interface DispatcherDataItem {
    dispatcher_initials: string;
    post_count: number;
    total_profit: number;
    average_profit: number;
}

export function initFinanceStatisticsCharts(): void {
    const loadsChartElement = document.getElementById('mainChart');
    const profitChartElement = document.getElementById('mainChartPrise');

    if (!loadsChartElement && !profitChartElement) {
        return;
    }

    // Get data from data attributes
    const dispatcherData = parseChartDataFromAttribute(loadsChartElement || profitChartElement) as DispatcherDataItem[] | null;

    if (!dispatcherData || dispatcherData.length === 0) {
        return;
    }

    initGoogleCharts(() => {
        if (loadsChartElement) {
            const dataArray: Array<Array<string | number>> = [['Dispatcher', 'Post Count']];
            dispatcherData.forEach((item) => {
                let postCount = parseInt(String(item.post_count), 10);
                if (postCount < 0) {
                    postCount = 0;
                }
                dataArray.push([
                    `${item.dispatcher_initials} \n${item.post_count}`,
                    postCount
                ]);
            });

            drawPieChart(loadsChartElement, dataArray, {
                title: 'Loads',
            });
        }

        if (profitChartElement) {
            const dataArray: Array<Array<string | number>> = [['Dispatcher', 'Profit']];
            dispatcherData.forEach((item) => {
                let itemTotal = parseFloat(String(item.total_profit || 0));
                const itemAverage = parseFloat(String(item.average_profit || 0));

                if (itemTotal < 0) {
                    itemTotal = 0;
                }

                const formattedTotal = itemTotal.toFixed(2);
                const formattedAverage = itemAverage.toFixed(2);

                dataArray.push([
                    `${item.dispatcher_initials}\n $${formattedTotal}\n $${formattedAverage}`,
                    itemTotal
                ]);
            });

            drawPieChart(profitChartElement, dataArray, {
                title: 'Profit',
                formatDollar: true,
            });
        }
    });
}
