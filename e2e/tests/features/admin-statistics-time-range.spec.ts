import { AdminStatisticsPage } from '../../pages/AdminStatisticsPage';
import { STATISTICS } from '../../support/fixtures';
import { expect, test } from '../../support/test';

/**
 * admin-statistics-time-range — the « 12 derniers mois » / « Depuis le début »
 * switch. The words series has a +STATISTICS.oldWords point twenty months ago
 * (E2eStatisticsSeeder), so the two ranges draw different data: in weekly mode
 * the old week is a bar only in « Depuis le début ».
 */

const visibleBars = async (stats: AdminStatisticsPage, chart: ReturnType<AdminStatisticsPage['charts']>) =>
  (await stats.drawing(chart)).bars.filter((b) => Math.abs(b.h) > 0.5).length;

test('range defaults to 12 months, switches in place and is remembered after reload', async ({ admin }) => {
  const stats = new AdminStatisticsPage(admin);
  await stats.recordCharts();
  await stats.goto();

  await expect(stats.rangeSwitch).toBeVisible();
  await expect(stats.rangeOption('recent')).toHaveAttribute('aria-checked', 'true');

  await stats.selectMode('weekly');
  await stats.openTab('content');
  // content tab: stories, chapters, words
  const words = stats.charts('content').nth(2);
  const nonZeroWeeks = STATISTICS.words.filter((v) => v !== 0).length;

  await expect.poll(() => visibleBars(stats, words)).toBe(nonZeroWeeks);

  await stats.selectRange('all');
  await expect.poll(() => visibleBars(stats, words)).toBe(nonZeroWeeks + 1);

  await admin.reload();
  await expect(stats.rangeOption('all')).toHaveAttribute('aria-checked', 'true');
  await stats.openTab('content');
  await expect.poll(() => visibleBars(stats, words)).toBe(nonZeroWeeks + 1);

  await stats.selectRange('recent');
  await expect.poll(() => visibleBars(stats, words)).toBe(nonZeroWeeks);
});
