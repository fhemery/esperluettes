import type { Locator, Page } from '@playwright/test';
import { expect, test } from '../../support/test';
import { STATISTICS } from '../../support/fixtures';
import { AdminStatisticsPage, type Bar, type StatisticsTab } from '../../pages/AdminStatisticsPage';

/**
 * Admin statistics — Cumulé / Par semaine switch (VERIFY for
 * admin-statistics-graph-toggle). Everything here happens after Chart.js has
 * drawn, which no PHP test can see; access rules and the rendered markup are
 * covered by StatisticsControllerTest and WeeklyChartPayloadTest.
 *
 * Only past weeks are compared to exact values: other specs of the run post
 * comments and edit chapters, which lands in the current week.
 */

const TABS: StatisticsTab[] = ['users', 'content', 'comments'];
const MODE_KEY = 'statistics.admin.graph-mode';
const WEEKS = STATISTICS.weeks;
const LAST = WEEKS - 1;

/** The Monday of each fixture week, as the chart labels it ("7 sept. 2026"). */
function mondayLabels(): string[] {
  const monday = new Date();
  monday.setHours(12, 0, 0, 0);
  monday.setDate(monday.getDate() - ((monday.getDay() + 6) % 7));
  const format = new Intl.DateTimeFormat('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' });

  return Array.from({ length: WEEKS }, (_, i) => {
    const d = new Date(monday);
    d.setDate(monday.getDate() - 7 * (LAST - i));
    return format.format(d);
  });
}

/** Chart.js inflates bar rectangles by up to half a pixel against seams. */
function expectPx(actual: number, expected: number, message?: string): void {
  expect(Math.abs(actual - expected), `${message ?? ''} (${actual} vs ${expected})`).toBeLessThanOrEqual(1);
}

function alpha(fill: string): number {
  const m = /rgba\([^)]*,\s*([\d.]+)\)/.exec(fill);
  return m ? Number(m[1]) : 1;
}

/** Bars grouped by category slot, oldest week first; each slot bottom-up. */
function slots(bars: Bar[]): Bar[][] {
  const byCentre = new Map<number, Bar[]>();
  for (const bar of bars) {
    const centre = Math.round(bar.x + bar.w / 2);
    byCentre.set(centre, [...(byCentre.get(centre) ?? []), bar]);
  }
  return [...byCentre.entries()].sort(([a], [b]) => a - b).map(([, group]) => group.sort((a, b) => b.y - a.y));
}

/** Collect uncaught errors and console errors of a page. */
function watchErrors(page: Page): string[] {
  const errors: string[] = [];
  page.on('pageerror', (e) => errors.push(`pageerror: ${e.message}`));
  page.on('console', (m) => {
    if (m.type() === 'error') errors.push(`console: ${m.text()}`);
  });
  return errors;
}

async function open(page: Page, init?: () => void): Promise<AdminStatisticsPage> {
  const stats = new AdminStatisticsPage(page);
  if (init) await page.addInitScript(init);
  await stats.recordCharts();
  await stats.goto();
  await expect(stats.modeSwitch).toBeVisible();
  return stats;
}

/** Wait for the chart to settle on the given kind of drawing, then return it. */
async function settled(stats: AdminStatisticsPage, canvas: Locator, kind: 'bar' | 'line') {
  await expect
    .poll(async () => {
      const d = await stats.drawing(canvas);
      return kind === 'bar' ? d.bars.length > 0 : d.bars.length === 0 && d.texts.length > 0;
    })
    .toBe(true);
  // Bars grow from the axis: wait for the animation to finish.
  let previous = '';
  await expect
    .poll(async () => {
      const current = JSON.stringify((await stats.drawing(canvas)).bars);
      const stable = current === previous;
      previous = current;
      return stable;
    }, { intervals: [250] })
    .toBe(true);
  return stats.drawing(canvas);
}

test('first visit opens on Cumulé, above the tabs, with line charts', async ({ admin }) => {
  const errors = watchErrors(admin);
  const stats = await open(admin);

  await expect(stats.modeOption('cumulative')).toHaveAttribute('aria-checked', 'true');
  await expect(stats.modeOption('weekly')).toHaveAttribute('aria-checked', 'false');
  expect(await stats.storedMode()).toBeNull();

  const switchBox = await stats.modeSwitch.boundingBox();
  const tabsBox = await stats.tabList.boundingBox();
  expect(switchBox!.y + switchBox!.height).toBeLessThanOrEqual(tabsBox!.y);

  for (const tab of TABS) {
    await stats.openTab(tab);
    for (const canvas of await stats.charts(tab).all()) {
      const drawing = await settled(stats, canvas, 'line');
      expect(drawing.kinds).not.toContain('bar');
    }
  }
  expect(errors).toEqual([]);
});

test('Par semaine turns the users chart into one bar per week, Mondays on the axis', async ({ admin }) => {
  const errors = watchErrors(admin);
  const stats = await open(admin);
  const tiles = await stats.tilesText();

  await stats.selectMode('weekly');
  await expect(stats.modeOption('weekly')).toHaveAttribute('aria-checked', 'true');
  expect(await stats.storedMode()).toBe('weekly');

  const canvas = stats.charts('users').first();
  const drawing = await settled(stats, canvas, 'bar');
  const weeks = slots(drawing.bars);

  expect(weeks).toHaveLength(WEEKS);
  for (const label of mondayLabels()) expect(drawing.texts).toContain(label);

  const unit = weeks[0][0].h / STATISTICS.users[0];
  for (let i = 0; i < LAST; i++) {
    expectPx(weeks[i][0].h, STATISTICS.users[i] * unit, `week ${i}`);
  }
  expect(weeks[STATISTICS.zeroWeekIndex][0].h).toBe(0);

  expect(await stats.tilesText()).toEqual(tiles);
  expect(errors).toEqual([]);
});

test('hidden tabs are already in bars, sized, with negative weeks below zero', async ({ admin }) => {
  const stats = await open(admin);
  await stats.selectMode('weekly');
  await stats.openTab('content');

  const [stories, chapters, words] = await stats.charts('content').all();
  for (const [canvas, values] of [[stories, STATISTICS.stories], [chapters, STATISTICS.chapters], [words, STATISTICS.words]] as const) {
    const drawing = await settled(stats, canvas, 'bar');
    const size = await stats.canvasSize(canvas);
    expect(size.height).toBe(280);
    expect(size.width).toBeGreaterThan(600);

    const weeks = slots(drawing.bars);
    expect(weeks).toHaveLength(WEEKS);
    // Spread over the whole width, not squeezed from a zero-size canvas.
    expect(weeks[LAST][0].x + weeks[LAST][0].w).toBeGreaterThan(size.width * 0.9);

    const zeroLine = weeks[0][0].y + weeks[0][0].h;
    const unit = weeks[0][0].h / values[0];
    for (let i = 0; i < LAST; i++) {
      const bar = weeks[i][0];
      if (values[i] < 0) {
        expectPx(bar.y, zeroLine, `negative week ${i} starts on the zero line`);
        expectPx(bar.h, -values[i] * unit, `negative week ${i} height`);
        expect(drawing.texts.some((t) => /^[-−]\d/.test(t)), 'axis extends below zero').toBe(true);
      } else {
        expectPx(bar.y + bar.h, zeroLine, `week ${i} rests on the zero line`);
        expectPx(bar.h, values[i] * unit, `week ${i} height`);
      }
    }
  }
});

test('comments: breakdown bars are stacked and add up to the comments bar', async ({ admin }) => {
  const stats = await open(admin);
  await stats.selectMode('weekly');
  await stats.openTab('comments');

  const [total, breakdown] = await stats.charts('comments').all();
  const totalWeeks = slots((await settled(stats, total, 'bar')).bars);
  const drawing = await settled(stats, breakdown, 'bar');
  const stacked = slots(drawing.bars);

  expect(totalWeeks).toHaveLength(WEEKS);
  expect(stacked).toHaveLength(WEEKS);
  expect(drawing.texts).toEqual(expect.arrayContaining(['Commentaires racines', 'Réponses']));

  const totalUnit = totalWeeks[0][0].h / STATISTICS.comments[0];
  const stackUnit = stacked[0][0].h / STATISTICS.rootComments[0];
  for (let i = 0; i < LAST; i++) {
    if (STATISTICS.comments[i] === 0) continue;
    const [root, reply] = stacked[i];
    expect(root.fill).toContain('99, 102, 241');
    expect(reply.fill).toContain('16, 185, 129');
    expectPx(reply.y + reply.h, root.y, `week ${i}: replies sit on the roots`);
    expect(root.h / stackUnit).toBeCloseTo(STATISTICS.rootComments[i], 1);
    expect((root.h + reply.h) / stackUnit, `week ${i}: stack height`).toBeCloseTo(totalWeeks[i][0].h / totalUnit, 1);
  }

  await stats.hoverSlot(breakdown, 0, WEEKS);
  const [first] = mondayLabels();
  const replies = STATISTICS.comments[0] - STATISTICS.rootComments[0];
  await expect
    .poll(async () => (await stats.drawing(breakdown)).texts)
    .toEqual(expect.arrayContaining([
      `Semaine du ${first} : ${STATISTICS.comments[0]}`,
      `Commentaires racines : ${STATISTICS.rootComments[0]}`,
      `Réponses : ${replies}`,
    ]));
});

test('the current week is lighter on every chart and says so in its tooltip', async ({ admin }) => {
  const stats = await open(admin);
  await stats.selectMode('weekly');

  for (const tab of TABS) {
    await stats.openTab(tab);
    for (const canvas of await stats.charts(tab).all()) {
      const weeks = slots((await settled(stats, canvas, 'bar')).bars);
      for (let i = 0; i < WEEKS; i++) {
        for (const bar of weeks[i]) {
          if (i === LAST) expect(alpha(bar.fill), `current week, ${tab}`).toBeLessThan(0.5);
          else expect(alpha(bar.fill), `week ${i}, ${tab}`).toBeGreaterThan(0.5);
        }
      }
    }
  }

  await stats.openTab('users');
  const users = stats.charts('users').first();
  await stats.hoverSlot(users, LAST, WEEKS);
  const current = mondayLabels()[LAST];
  await expect
    .poll(async () => (await stats.drawing(users)).texts)
    .toContain(`Semaine du ${current} : ${STATISTICS.users[LAST]} (semaine en cours)`);
});

test('a normal week tooltip reads "Semaine du … : n", single line on a single series', async ({ admin }) => {
  const stats = await open(admin);
  await stats.selectMode('weekly');
  const users = stats.charts('users').first();
  const before = await settled(stats, users, 'bar');

  await stats.hoverSlot(users, 3, WEEKS);
  const label = mondayLabels()[3];
  const title = `Semaine du ${label} : ${STATISTICS.users[3]}`;
  await expect.poll(async () => (await stats.drawing(users)).texts).toContain(title);

  const added = (await stats.drawing(users)).texts.filter((t) => !before.texts.includes(t));
  expect(added.filter((t) => t.trim() !== '')).toEqual([title]);
});

test('a reload in Par semaine draws bars from the first frame', async ({ admin }) => {
  const stats = await open(admin);
  await stats.selectMode('weekly');
  await settled(stats, stats.charts('users').first(), 'bar');

  await admin.reload();
  await expect(stats.modeOption('weekly')).toHaveAttribute('aria-checked', 'true');

  for (const tab of TABS) {
    await stats.openTab(tab);
    for (const canvas of await stats.charts(tab).all()) {
      const drawing = await settled(stats, canvas, 'bar');
      expect(drawing.kinds, `${tab}: no cumulative frame before the bars`).not.toContain('line');
    }
  }
});

test('switching back to Cumulé restores every curve', async ({ admin }) => {
  const stats = await open(admin);
  const users = stats.charts('users').first();
  const original = (await settled(stats, users, 'line')).texts;

  await stats.selectMode('weekly');
  await settled(stats, users, 'bar');
  await stats.selectMode('cumulative');
  expect(await stats.storedMode()).toBe('cumulative');

  expect((await settled(stats, users, 'line')).texts).toEqual(original);
  for (const tab of TABS) {
    await stats.openTab(tab);
    for (const canvas of await stats.charts(tab).all()) {
      await settled(stats, canvas, 'line');
    }
  }
});

test('keyboard: one tab stop, arrows move the selection and redraw, focus ring shows', async ({ admin }) => {
  const stats = await open(admin);
  const users = stats.charts('users').first();
  await settled(stats, users, 'line');

  // Start sequential navigation just before the switch.
  await admin.getByRole('heading', { level: 1 }).click();
  await admin.keyboard.press('Tab');
  await expect(stats.modeOption('cumulative')).toBeFocused();
  const ring = await stats.modeOption('cumulative').evaluate((el) => getComputedStyle(el).boxShadow);
  expect(ring).not.toBe('none');

  await admin.keyboard.press('ArrowRight');
  await expect(stats.modeOption('weekly')).toBeFocused();
  await expect(stats.modeOption('weekly')).toHaveAttribute('aria-checked', 'true');
  await expect(stats.modeSwitch).toHaveAttribute('data-value', 'weekly');
  await settled(stats, users, 'bar');

  await admin.keyboard.press('ArrowLeft');
  await expect(stats.modeOption('cumulative')).toBeFocused();
  await expect(stats.modeOption('cumulative')).toHaveAttribute('aria-checked', 'true');
  await settled(stats, users, 'line');

  await admin.keyboard.press('End');
  await expect(stats.modeOption('weekly')).toBeFocused();
  await settled(stats, users, 'bar');

  // The unselected option is not a second tab stop: Tab leaves the group.
  await admin.keyboard.press('Tab');
  await expect(admin.getByRole('tab', { name: 'Utilisateurs' })).toBeFocused();
});

test('the switch is a labelled radiogroup whose radios follow the selection', async ({ admin }) => {
  const stats = await open(admin);
  const group = admin.getByRole('radiogroup', { name: 'Mode d’affichage des graphiques' });
  await expect(group).toHaveCount(1);
  await expect(group.getByRole('radio')).toHaveCount(2);
  await expect(group.getByRole('radio', { name: 'Cumulé', checked: true })).toBeVisible();

  await stats.selectMode('weekly');
  await expect(group.getByRole('radio', { name: 'Par semaine', checked: true })).toBeVisible();
  await expect(group.getByRole('radio', { name: 'Cumulé', checked: false })).toBeVisible();
});

test('a corrupt stored mode falls back to Cumulé silently', async ({ admin }) => {
  const errors = watchErrors(admin);
  const stats = await open(admin, () => localStorage.setItem('statistics.admin.graph-mode', 'bogus'));

  await expect(stats.modeOption('cumulative')).toHaveAttribute('aria-checked', 'true');
  const users = stats.charts('users').first();
  await settled(stats, users, 'line');

  await stats.selectMode('weekly');
  await settled(stats, users, 'bar');
  expect(errors).toEqual([]);
});

test('blocked storage: opens on Cumulé, the switch still works, no error', async ({ admin }) => {
  const errors = watchErrors(admin);
  const stats = await open(admin, () => {
    const deny = () => {
      throw new DOMException('The operation is insecure.', 'SecurityError');
    };
    Storage.prototype.getItem = deny;
    Storage.prototype.setItem = deny;
  });

  await expect(stats.modeOption('cumulative')).toHaveAttribute('aria-checked', 'true');
  const users = stats.charts('users').first();
  await settled(stats, users, 'line');

  await stats.selectMode('weekly');
  await expect(stats.modeOption('weekly')).toHaveAttribute('aria-checked', 'true');
  await settled(stats, users, 'bar');
  expect(errors).toEqual([]);
});

test('mobile width: the switch fits above the tabs and a tap draws bars', async ({ admin }) => {
  await admin.setViewportSize({ width: 375, height: 812 });
  const stats = await open(admin);

  const box = await stats.modeSwitch.boundingBox();
  expect(box!.x).toBeGreaterThanOrEqual(0);
  expect(box!.x + box!.width).toBeLessThanOrEqual(375);
  const tabs = await stats.tabList.boundingBox();
  expect(box!.y + box!.height).toBeLessThanOrEqual(tabs!.y);
  const option = await stats.modeOption('weekly').boundingBox();
  expect(option!.height).toBeGreaterThanOrEqual(24);

  await stats.selectMode('weekly');
  const users = stats.charts('users').first();
  const weeks = slots((await settled(stats, users, 'bar')).bars);
  expect(weeks).toHaveLength(WEEKS);
  expect((await stats.canvasSize(users)).width).toBeLessThanOrEqual(375);
});

test('tech-admin gets the same switch and behaviour', async ({ tech_admin }) => {
  const stats = await open(tech_admin);
  await expect(stats.modeOption('cumulative')).toHaveAttribute('aria-checked', 'true');
  const users = stats.charts('users').first();
  await settled(stats, users, 'line');

  await stats.selectMode('weekly');
  expect(slots((await settled(stats, users, 'bar')).bars)).toHaveLength(WEEKS);
});
