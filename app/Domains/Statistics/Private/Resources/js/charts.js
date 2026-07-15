import { Chart, registerables } from 'chart.js';
import 'chartjs-adapter-date-fns';
import { format } from 'date-fns';
import { enUS, fr } from 'date-fns/locale';

Chart.register(...registerables);

const DATE_LOCALES = {
    fr,
    en: enUS,
};

function resolveDateLocale(localeCode) {
    const language = (localeCode ?? 'fr').split(/[-_]/)[0].toLowerCase();

    return DATE_LOCALES[language] ?? fr;
}

function initLineChart(canvas, data, options = {}) {
    const dateLocale = resolveDateLocale(options.locale);

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
                callbacks: {
                    title(tooltipItems) {
                        if (tooltipItems.length === 0) {
                            return '';
                        }

                        return format(new Date(tooltipItems[0].parsed.x), 'd MMMM yyyy', { locale: dateLocale });
                    },
                },
            },
        },
        scales: {
            x: {
                type: 'time',
                min: options.rangeMin ?? undefined,
                max: options.rangeMax ?? undefined,
                adapters: {
                    date: {
                        locale: dateLocale,
                    },
                },
                grid: {
                    display: false,
                },
                ticks: {
                    maxTicksLimit: 6,
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
                tension: options.stepped ? 0 : 0.3,
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
        datasets: [{
            label,
            data: points.map((point) => {
                if (!useCumulative) {
                    return {
                        x: point.x,
                        y: point.value,
                    };
                }

                let y = point.cumulativeValue;

                if (y === null || y === undefined) {
                    runningTotal += point.value;
                    y = runningTotal;
                }

                return {
                    x: point.x,
                    y,
                };
            }),
            borderColor: color,
            backgroundColor,
            fill: true,
            stepped: options.stepped ? 'before' : false,
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

function formatMultiChartData(series, options = {}) {
    const useCumulative = options.cumulative ?? false;

    return {
        datasets: series.map((item) => {
            let runningTotal = 0;

            return {
                label: item.label,
                data: item.points.map((point) => {
                    if (!useCumulative) {
                        return {
                            x: point.x,
                            y: point.value,
                        };
                    }

                    let y = point.cumulativeValue;

                    if (y === null || y === undefined) {
                        runningTotal += point.value;
                        y = runningTotal;
                    }

                    return {
                        x: point.x,
                        y,
                    };
                }),
                borderColor: item.color,
                backgroundColor: item.backgroundColor,
                fill: false,
                stepped: options.stepped ? 'before' : false,
            };
        }),
    };
}

function mountMultiLineChartContainer(container) {
    if (container.dataset.statisticsChartMounted === 'true') {
        return;
    }

    const canvas = container.querySelector('canvas');

    if (!canvas) {
        return;
    }

    const series = parseJsonDataset(container, 'series', []);
    const hasPoints = series.some((item) => item.points?.length > 0);

    if (!hasPoints) {
        return;
    }

    const options = parseJsonDataset(container, 'options', {});
    const chartData = formatMultiChartData(series, options);

    initLineChart(canvas, chartData, options);
    container.dataset.statisticsChartMounted = 'true';
}

function mountAllLineCharts() {
    document.querySelectorAll('[data-statistics-line-chart]').forEach(mountLineChartContainer);
    document.querySelectorAll('[data-statistics-multi-line-chart]').forEach(mountMultiLineChartContainer);
}

window.StatisticsCharts = {
    initLineChart,
    formatChartData,
    formatMultiChartData,
    mountAll: mountAllLineCharts,
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', mountAllLineCharts, { once: true });
} else {
    mountAllLineCharts();
}

window.dispatchEvent(new CustomEvent('statistics-charts-ready'));
