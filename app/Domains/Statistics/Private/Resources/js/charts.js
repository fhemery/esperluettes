import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

function initLineChart(canvas, data, options = {}) {
    const defaultOptions = {
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
        data: data,
        options: { ...defaultOptions, ...options },
    });
}

function formatChartData(points, label, options = {}) {
    const color = options.color ?? 'rgb(99, 102, 241)';
    const backgroundColor = options.backgroundColor ?? 'rgba(99, 102, 241, 0.1)';
    const useCumulative = options.cumulative ?? false;

    return {
        labels: points.map(p => p.label),
        datasets: [{
            label: label,
            data: points.map(p => useCumulative && p.cumulativeValue !== null ? p.cumulativeValue : p.value),
            borderColor: color,
            backgroundColor: backgroundColor,
            fill: true,
        }],
    };
}

const statisticsLineChartComponent = (pointsJson, label, options = {}) => ({
    chart: null,
    
    init() {
        const points = typeof pointsJson === 'string' ? JSON.parse(pointsJson) : pointsJson;
        const chartData = formatChartData(points, label, options);
        this.chart = initLineChart(this.$refs.canvas, chartData, options);
    },
    
    destroy() {
        if (this.chart) {
            this.chart.destroy();
            this.chart = null;
        }
    },
});

if (window.Alpine) {
    window.Alpine.data('statisticsLineChart', statisticsLineChartComponent);
    
    // Re-initialize any uninitialized chart elements that were rendered before this script loaded
    document.querySelectorAll('[x-data^="statisticsLineChart"]').forEach(el => {
        if (!el._x_dataStack) {
            window.Alpine.initTree(el);
        }
    });
} else {
    document.addEventListener('alpine:init', () => {
        window.Alpine.data('statisticsLineChart', statisticsLineChartComponent);
    });
}

window.StatisticsCharts = {
    initLineChart,
    formatChartData,
};
