import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

function initLineChart(canvas, data, options = {}) {
    const chartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: options.showLegend ?? false,
            },
            tooltip: {
                mode: 'index',
                intersect: false,
            },
        },
        scales: {
            x: {
                grid: {
                    display: false,
                },
            },
            y: {
                beginAtZero: true,
                ticks: {
                    precision: 0,
                },
                grid: {
                    color: 'rgba(0, 0, 0, 0.1)',
                },
            },
        },
        elements: {
            line: {
                tension: 0.3,
            },
            point: {
                radius: 3,
                hoverRadius: 5,
            },
        },
    };

    return new Chart(canvas, {
        type: 'line',
        data,
        options: chartOptions,
    });
}

function formatChartData(points, label, options = {}) {
    const color = options.color ?? 'rgb(99, 102, 241)';
    const backgroundColor = options.backgroundColor ?? 'rgba(99, 102, 241, 0.1)';
    const useCumulative = options.cumulative ?? false;
    let runningTotal = 0;

    return {
        labels: points.map((point) => point.label),
        datasets: [{
            label,
            data: points.map((point) => {
                if (!useCumulative) {
                    return point.value;
                }

                if (point.cumulativeValue !== null && point.cumulativeValue !== undefined) {
                    return point.cumulativeValue;
                }

                runningTotal += point.value;
                return runningTotal;
            }),
            borderColor: color,
            backgroundColor,
            fill: true,
        }],
    };
}

function parseJsonDataset(element, key, fallback) {
    const raw = element.dataset[key];

    if (!raw) {
        return fallback;
    }

    try {
        return JSON.parse(raw);
    } catch {
        return fallback;
    }
}

function mountLineChartContainer(container) {
    if (container.dataset.statisticsChartMounted === 'true') {
        return;
    }

    const canvas = container.querySelector('canvas');

    if (!canvas) {
        return;
    }

    const points = parseJsonDataset(container, 'points', []);

    if (points.length === 0) {
        return;
    }

    const label = container.dataset.label ?? '';
    const options = parseJsonDataset(container, 'options', {});
    const chartData = formatChartData(points, label, options);

    initLineChart(canvas, chartData, options);
    container.dataset.statisticsChartMounted = 'true';
}

function mountAllLineCharts() {
    document.querySelectorAll('[data-statistics-line-chart]').forEach(mountLineChartContainer);
}

window.StatisticsCharts = {
    initLineChart,
    formatChartData,
    mountAll: mountAllLineCharts,
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', mountAllLineCharts, { once: true });
} else {
    mountAllLineCharts();
}

window.dispatchEvent(new CustomEvent('statistics-charts-ready'));
