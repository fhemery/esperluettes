import { Chart, registerables } from 'chart.js';
import 'chartjs-adapter-date-fns';
import { format, parseISO } from 'date-fns';
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

const GRAPH_MODE_CONTROL = 'statistics-graph-mode';
const WEEK_ALPHA = 0.8;
const CURRENT_WEEK_ALPHA = 0.35;

/** 'rgb(r, g, b)' or 'rgba(r, g, b, a)' → 'rgba(r, g, b, alpha)'; anything else is returned as is. */
function withAlpha(color, alpha) {
    const match = /^rgba?\(([^,]+),([^,]+),([^,)]+)/.exec(color ?? '');

    if (!match) {
        return color;
    }

    return `rgba(${match[1].trim()}, ${match[2].trim()}, ${match[3].trim()}, ${alpha})`;
}

/** One bar colour per week; the last one — the current, partial week — lighter. */
function weeklyBarColors(color, count) {
    return Array.from({ length: count }, (_, index) => withAlpha(color, index === count - 1 ? CURRENT_WEEK_ALPHA : WEEK_ALPHA));
}

function weeklyDataset(label, points, color) {
    return {
        label,
        data: points.map((point) => point.value),
        backgroundColor: weeklyBarColors(color, points.length),
        borderColor: color,
    };
}

function formatWeeklyChartData(points, label, options = {}) {
    return {
        labels: points.map((point) => point.x),
        datasets: [weeklyDataset(label, points, options.color ?? 'rgb(99, 102, 241)')],
    };
}

function formatWeeklyMultiChartData(series, options = {}) {
    const longest = series.reduce(
        (best, item) => ((item.weeklyPoints?.length ?? 0) > best.length ? item.weeklyPoints : best),
        [],
    );

    return {
        labels: longest.map((point) => point.x),
        datasets: series.map((item) => weeklyDataset(item.label, item.weeklyPoints ?? [], item.color ?? 'rgb(99, 102, 241)')),
    };
}

function weeklyTooltipText(pattern, dateLabel, value, isCurrentWeek, currentWeekLabel) {
    const text = (pattern ?? '').replace(':date', dateLabel).replace(':value', value);

    return isCurrentWeek ? `${text} (${currentWeekLabel})` : text;
}

function initWeeklyBarChart(canvas, data, options = {}, { stacked = false } = {}) {
    const dateLocale = resolveDateLocale(options.locale);
    const formatMonday = (label) => format(parseISO(label), 'd MMM yyyy', { locale: dateLocale });
    const formatValue = (value) => Number(value).toLocaleString(options.locale ?? 'fr');
    const lastIndex = data.labels.length - 1;
    const multiSeries = data.datasets.length > 1;

    return new Chart(canvas, {
        type: 'bar',
        data,
        options: {
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

                            const total = tooltipItems.reduce((sum, item) => sum + (item.parsed.y ?? 0), 0);

                            return weeklyTooltipText(
                                options.weekTooltip,
                                formatMonday(data.labels[tooltipItems[0].dataIndex]),
                                formatValue(total),
                                tooltipItems[0].dataIndex === lastIndex,
                                options.currentWeekLabel,
                            );
                        },
                        label(item) {
                            return multiSeries ? `${item.dataset.label} : ${formatValue(item.parsed.y)}` : '';
                        },
                    },
                },
            },
            scales: {
                x: {
                    type: 'category',
                    stacked,
                    grid: {
                        display: false,
                    },
                    ticks: {
                        maxTicksLimit: 6,
                        callback(value) {
                            return formatMonday(this.getLabelForValue(value));
                        },
                    },
                },
                y: {
                    stacked,
                    beginAtZero: true,
                    ticks: {
                        precision: 0,
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)',
                    },
                },
            },
        },
    });
}

/** Mode selected on the page's graph-mode switch; 'cumulative' unless it says exactly 'weekly'. */
function currentMode() {
    const control = document.querySelector(`[data-segmented-control][data-name="${GRAPH_MODE_CONTROL}"]`);

    return control?.dataset.value === 'weekly' ? 'weekly' : 'cumulative';
}

/** Mounted containers → { kind: 'line'|'multi', chart, payload }, so a mode change can re-create them. */
const registry = new Map();
let renderedMode = null;

function createChart(canvas, kind, payload, mode) {
    const { options } = payload;

    if (kind === 'line') {
        if (mode === 'weekly' && payload.weeklyPoints.length > 0) {
            return initWeeklyBarChart(canvas, formatWeeklyChartData(payload.weeklyPoints, payload.label, options), options);
        }

        return initLineChart(canvas, formatChartData(payload.points, payload.label, options), options);
    }

    if (mode === 'weekly' && payload.series.some((item) => item.weeklyPoints?.length > 0)) {
        return initWeeklyBarChart(canvas, formatWeeklyMultiChartData(payload.series, options), options, { stacked: true });
    }

    return initLineChart(canvas, formatMultiChartData(payload.series, options), options);
}

function registerChart(container, canvas, kind, payload) {
    renderedMode ??= currentMode();
    const chart = createChart(canvas, kind, payload, renderedMode);

    registry.set(container, { kind, canvas, chart, payload });
    container.dataset.statisticsChartMounted = 'true';
}

function renderAll(mode) {
    renderedMode = mode;

    registry.forEach((entry) => {
        entry.chart?.destroy();
        entry.chart = createChart(entry.canvas, entry.kind, entry.payload, mode);
    });
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

    registerChart(container, canvas, 'line', {
        points,
        weeklyPoints: parseJsonDataset(container, 'weeklyPoints', []),
        label: container.dataset.label ?? '',
        options: parseJsonDataset(container, 'options', {}),
    });
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

    registerChart(container, canvas, 'multi', {
        series,
        options: parseJsonDataset(container, 'options', {}),
    });
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

// The switch has already dispatched its initial value before this module runs
// (mounting reads it from data-value); this handles later changes. Idempotent.
window.addEventListener('segmented-control-change', (event) => {
    if (event.detail?.name !== GRAPH_MODE_CONTROL) {
        return;
    }

    const mode = event.detail.value === 'weekly' ? 'weekly' : 'cumulative';

    if (mode !== renderedMode) {
        renderAll(mode);
    }
});

export {
    currentMode,
    formatChartData,
    formatMultiChartData,
    formatWeeklyChartData,
    formatWeeklyMultiChartData,
    weeklyTooltipText,
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', mountAllLineCharts, { once: true });
} else {
    mountAllLineCharts();
}

window.dispatchEvent(new CustomEvent('statistics-charts-ready'));
