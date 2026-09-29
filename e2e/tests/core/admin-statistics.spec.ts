import { AdminStatisticsPage } from '../../pages/AdminStatisticsPage';
import { STATISTICS } from '../../support/fixtures';
import { expect, test } from '../../support/test';

/**
 * CORE — `/admin/statistics` renders for an admin.
 *
 * The tiles are Blade, but every graph is drawn by Chart.js from the page's own
 * bundled module, on a canvas: a PHP test can only see the JSON payload, never
 * that a chart was actually drawn. A broken bundle or a Chart.js upgrade would
 * leave the page blank without failing anything else — that is what earns it a
 * place in core. Access rules are covered by StatisticsControllerTest.
 *
 * Values come from E2eStatisticsSeeder (`STATISTICS`). Users are never created
 * during the run, so their total and past weeks are stable; the current week
 * is left alone because other specs add to it.
 */

const sum = (values: readonly number[]): number => values.reduce((a, b) => a + b, 0);

test('admin sees the tiles and the charts of the statistics page, with seeded values', async ({ admin }) => {
  const errors: string[] = [];
  admin.on('pageerror', (e) => errors.push(e.message));

  const stats = new AdminStatisticsPage(admin);
  await stats.recordCharts();
  await stats.goto();

  await expect(stats.modeSwitch).toBeVisible();
  await expect(stats.modeOption('cumulative')).toHaveAttribute('aria-checked', 'true');
  await expect(stats.tabList).toBeVisible();
  await expect(stats.emptyCharts).toHaveCount(0);
  expect(await stats.allCharts.count()).toBeGreaterThan(0);

  const [usersTile] = await stats.tilesText();
  expect(usersTile).toMatch(new RegExp(`\\b${sum(STATISTICS.users)}\\b`));

  // Cumulé: the users chart is drawn as a line, with its axis labels.
  const users = stats.charts('users').first();
  await expect.poll(async () => (await stats.drawing(users)).texts.length).toBeGreaterThan(0);
  expect((await stats.drawing(users)).kinds).not.toContain('bar');

  // Par semaine: one bar per week, and a past week's tooltip shows its seeded value.
  await stats.selectMode('weekly');
  await expect.poll(async () => (await stats.drawing(users)).bars.length).toBeGreaterThan(0);
  const week = 3;
  await stats.hoverSlot(users, week, STATISTICS.weeks);
  await expect
    .poll(async () => (await stats.drawing(users)).texts)
    .toContainEqual(expect.stringMatching(new RegExp(`^Semaine du .+ : ${STATISTICS.users[week]}$`)));

  expect(errors).toEqual([]);
});
