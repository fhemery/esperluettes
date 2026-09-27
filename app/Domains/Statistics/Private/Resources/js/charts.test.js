import { describe, it, expect, afterEach, vi } from 'vitest';
import { Chart } from 'chart.js';
import {
    currentMode,
    formatChartData,
    formatMultiChartData,
    formatWeeklyChartData,
    formatWeeklyMultiChartData,
    weeklyTooltipText,
} from './charts.js';

const weeks = [
    { x: '2026-09-07', value: 4 },
    { x: '2026-09-14', value: -2 },
    { x: '2026-09-21', value: 3 },
];

const color = { color: 'rgb(99, 102, 241)' };

describe('statistics charts — weekly mode', () => {
    afterEach(() => {
        vi.restoreAllMocks();
        document.body.innerHTML = '';
    });

    it('formats weekly points as one bar per week labelled with the Monday', () => {
        const data = formatWeeklyChartData(weeks, 'Utilisateurs', color);

        expect(data.labels).toEqual(['2026-09-07', '2026-09-14', '2026-09-21']);
        expect(data.datasets).toHaveLength(1);
        expect(data.datasets[0].label).toBe('Utilisateurs');
        expect(data.datasets[0].data).toEqual([4, -2, 3]);
    });

    it('gives the last bar a lighter colour than the others', () => {
        const { backgroundColor } = formatWeeklyChartData(weeks, 'x', color).datasets[0];

        expect(backgroundColor).toEqual([
            'rgba(99, 102, 241, 0.8)',
            'rgba(99, 102, 241, 0.8)',
            'rgba(99, 102, 241, 0.35)',
        ]);
    });

    it('keeps negative weekly values', () => {
        const data = formatWeeklyChartData(weeks, 'x', color);

        expect(data.datasets[0].data[1]).toBe(-2);
    });

    it('formats the comment breakdown as one bar dataset per series', () => {
        const data = formatWeeklyMultiChartData([
            { label: 'Racines', color: 'rgb(99, 102, 241)', weeklyPoints: weeks },
            { label: 'Réponses', color: 'rgb(16, 185, 129)', weeklyPoints: weeks.map((p) => ({ ...p, value: 1 })) },
        ]);

        expect(data.labels).toEqual(['2026-09-07', '2026-09-14', '2026-09-21']);
        expect(data.datasets.map((d) => d.label)).toEqual(['Racines', 'Réponses']);
        expect(data.datasets[0].data).toEqual([4, -2, 3]);
        expect(data.datasets[1].data).toEqual([1, 1, 1]);
        expect(data.datasets[1].backgroundColor.at(-1)).toBe('rgba(16, 185, 129, 0.35)');
    });

    it('builds the tooltip title "Semaine du <date> : <value>"', () => {
        expect(weeklyTooltipText('Semaine du :date : :value', '15 sept. 2026', '37', false, 'semaine en cours'))
            .toBe('Semaine du 15 sept. 2026 : 37');
    });

    it('appends the current-week label for the last bar', () => {
        expect(weeklyTooltipText('Semaine du :date : :value', '21 sept. 2026', '3', true, 'semaine en cours'))
            .toBe('Semaine du 21 sept. 2026 : 3 (semaine en cours)');
    });

    it('keeps the existing cumulative formatter output unchanged', () => {
        const points = [
            { x: '2026-09-01', value: 2, cumulativeValue: 10 },
            { x: '2026-09-02', value: 3, cumulativeValue: null },
        ];

        expect(formatChartData(points, 'Mots', { cumulative: true, stepped: true, color: 'c', backgroundColor: 'b' }))
            .toEqual({
                datasets: [{
                    label: 'Mots',
                    data: [{ x: '2026-09-01', y: 10 }, { x: '2026-09-02', y: 3 }],
                    borderColor: 'c',
                    backgroundColor: 'b',
                    fill: true,
                    stepped: 'before',
                }],
            });

        expect(formatMultiChartData([{ label: 'R', color: 'c', backgroundColor: 'b', points }], { cumulative: false }))
            .toEqual({
                datasets: [{
                    label: 'R',
                    data: [{ x: '2026-09-01', y: 2 }, { x: '2026-09-02', y: 3 }],
                    borderColor: 'c',
                    backgroundColor: 'b',
                    fill: false,
                    stepped: false,
                }],
            });
    });

    it('reads the initial mode from the control data-value, defaulting to cumulative', () => {
        expect(currentMode()).toBe('cumulative');

        document.body.innerHTML = '<div data-segmented-control data-name="other" data-value="weekly"></div>'
            + '<div data-segmented-control data-name="statistics-graph-mode" data-value="weekly"></div>';
        expect(currentMode()).toBe('weekly');

        document.body.innerHTML = '<div data-segmented-control data-name="statistics-graph-mode" data-value="garbage"></div>';
        expect(currentMode()).toBe('cumulative');
    });

    it('re-creates every mounted chart as bars on the switch event, and back; no weekly data stays a line', () => {
        vi.spyOn(console, 'error').mockImplementation(() => {});
        const points = JSON.stringify([{ x: '2026-09-07', value: 1, cumulativeValue: 1 }]);
        const options = JSON.stringify({ cumulative: true });
        document.body.innerHTML = `
            <div id="with-weekly" data-statistics-line-chart data-label="A" data-points='${points}'
                 data-weekly-points='${JSON.stringify(weeks)}' data-options='${options}'><canvas></canvas></div>
            <div id="breakdown" data-statistics-multi-line-chart data-options='${options}'
                 data-series='${JSON.stringify([{ label: 'R', points: JSON.parse(points), weeklyPoints: weeks }])}'><canvas></canvas></div>
            <div id="no-weekly" data-statistics-line-chart data-label="C" data-points='${points}'
                 data-weekly-points='[]' data-options='${options}'><canvas></canvas></div>`;
        // happy-dom has no 2D context, so Chart.js keeps no canvas: find live charts by dataset label.
        const labels = { 'with-weekly': 'A', breakdown: 'R', 'no-weekly': 'C' };
        const chartOf = (id) => Object.values(Chart.instances)
            .find((chart) => chart.config.data.datasets[0]?.label === labels[id]);
        const typeOf = (id) => chartOf(id)?.config.type;
        const switchTo = (value, name = 'statistics-graph-mode') => window.dispatchEvent(
            new CustomEvent('segmented-control-change', { detail: { name, value } }),
        );

        window.StatisticsCharts.mountAll();
        window.StatisticsCharts.mountAll();
        expect([typeOf('with-weekly'), typeOf('breakdown'), typeOf('no-weekly')]).toEqual(['line', 'line', 'line']);
        expect(Object.keys(Chart.instances)).toHaveLength(3);

        switchTo('weekly', 'another-control');
        expect(typeOf('with-weekly')).toBe('line');

        switchTo('weekly');
        expect([typeOf('with-weekly'), typeOf('breakdown'), typeOf('no-weekly')]).toEqual(['bar', 'bar', 'line']);
        expect(chartOf('breakdown').config.options.scales.y.stacked).toBe(true);
        expect(Object.keys(Chart.instances)).toHaveLength(3);

        switchTo('cumulative');
        expect([typeOf('with-weekly'), typeOf('breakdown'), typeOf('no-weekly')]).toEqual(['line', 'line', 'line']);
        expect(Object.keys(Chart.instances)).toHaveLength(3);
    });
});
