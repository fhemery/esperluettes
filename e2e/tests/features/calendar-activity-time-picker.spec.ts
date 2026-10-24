import { type Browser, type BrowserContext, type Page } from '@playwright/test';
import path from 'node:path';
import { AdminActivityFormPage } from '../../pages/AdminActivityFormPage';
import { ChapterCreatePage } from '../../pages/ChapterCreatePage';
import { ChapterEditPage } from '../../pages/ChapterEditPage';
import { DateTimeField } from '../../pages/DateTimeField';
import { GIFTS, STORY, storageStatePath, type RoleName } from '../../support/fixtures';
import { artisan, ROOT } from '../../support/sail';
import { expect, test } from '../../support/test';

/**
 * FEATURE (temporary) — calendar-activity-time-picker: `<x-shared::datetime-local-input>`
 * is now a date input + a time input over one hidden input.
 *
 * Browser in Europe/Paris (UTC+2 until 2026-10-25, UTC+1 after), fr-FR. The
 * fixed dates below are winter ones (UTC+1) so the expected UTC is a constant.
 *
 * Evidence shots only when E2E_SHOTS_DIR is set.
 */

const CONTEST_NAME = 'Concours de citations — soumissions ouvertes';
const CREATED = 'Activité horaire E2E';

async function parisContext(
  browser: Browser,
  role: RoleName,
  extra: Record<string, unknown> = {},
): Promise<BrowserContext> {
  return browser.newContext({
    storageState: path.join(ROOT, storageStatePath(role)),
    timezoneId: 'Europe/Paris',
    locale: 'fr-FR',
    ...extra,
  });
}

async function shot(page: Page, name: string): Promise<void> {
  const dir = process.env.E2E_SHOTS_DIR;
  if (dir) await page.screenshot({ path: path.join(ROOT, dir, `${name}.png`), fullPage: true });
}

/** Read a column straight from the e2e database. Returns the raw `Y-m-d H:i:s` (UTC) or `null`. */
function stored(table: string, where: [string, string], column: string): string {
  const code =
    `$v = DB::table('${table}')->where('${where[0]}', '${where[1]}')->value('${column}'); ` +
    `echo '<<' . ($v === null ? 'NULL' : $v) . '>>';`;
  const res = artisan(['tinker', `--execute=${code}`]);
  const match = res.output.match(/<<(.*?)>>/);
  if (!match || match[1] === undefined) throw new Error(`tinker gave no value: ${res.output}`);
  return match[1];
}

/** `2026-12-01 09:30:00` (UTC) -> `{date: '2026-12-01', time: '10:30'}` in Paris. */
function utcToParis(utc: string): { date: string; time: string } {
  const d = new Date(utc.replace(' ', 'T') + 'Z');
  const parts = new Intl.DateTimeFormat('sv-SE', {
    timeZone: 'Europe/Paris',
    year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hour12: false,
  }).format(d);
  const [date = '', time = ''] = parts.split(' ');
  return { date, time };
}

test.describe('Activity create (admin)', () => {
  test('core dates: empty, label focuses date, 00:00 prefill, free minutes, clearing', async ({ browser }) => {
    const ctx = await parisContext(browser, 'admin');
    const page = await ctx.newPage();
    const form = new AdminActivityFormPage(page);
    expect(await form.gotoCreate()).toBe(200);

    for (const id of ['preview_starts_at', 'active_starts_at', 'active_ends_at', 'archived_at']) {
      const f = form.field(id);
      await expect(f.date, `${id} date`).toHaveAttribute('type', 'date');
      await expect(f.time, `${id} time`).toHaveAttribute('type', 'time');
      expect(await f.values(), `${id} empty on create`).toEqual({ date: '', time: '' });
      expect(await f.submitted()).toBe('');
      await expect(f.time).not.toHaveAttribute('step', /.*/);
      await expect(f.time).toHaveAttribute('aria-label', 'Heure');
    }

    // Label click focuses the date input.
    const start = form.field('active_starts_at');
    await start.label.click();
    await expect(start.date).toBeFocused();

    // Date chosen, time empty -> 00:00, hidden carries the combined local value.
    await start.date.fill('2026-12-01');
    await expect(start.time).toHaveValue('00:00');
    expect(await start.submitted()).toBe('2026-12-01T00:00');

    // Time editable to any minute.
    await start.time.fill('13:47');
    expect(await start.submitted()).toBe('2026-12-01T13:47');

    // Clearing the date clears time and the hidden value.
    await start.clearDate();
    await expect(start.time).toHaveValue('');
    expect(await start.submitted()).toBe('');
    await shot(page, 'create-core-dates');
    await ctx.close();
  });

  test('saving converts Paris to UTC, a cleared field stays null', async ({ browser }) => {
    const ctx = await parisContext(browser, 'admin');
    const page = await ctx.newPage();
    const form = new AdminActivityFormPage(page);
    await form.gotoCreate();

    await form.nameInput.fill(CREATED);
    await form.typeSelect.selectOption({ index: 1 });
    await form.field('preview_starts_at').fill('2026-12-01', '08:15');
    await form.field('active_starts_at').fill('2026-12-02'); // time prefilled 00:00
    await form.field('active_ends_at').fill('2026-12-20', '23:59');
    // archived_at: filled then cleared -> must stay null
    await form.field('archived_at').fill('2027-01-01', '10:00');
    await form.field('archived_at').clearDate();

    await form.submitButton.click();
    await page.waitForLoadState('networkidle');
    await expect(page).not.toHaveURL(/\/create$/);

    expect(stored('calendar_activities', ['name', CREATED], 'preview_starts_at')).toBe('2026-12-01 07:15:00');
    expect(stored('calendar_activities', ['name', CREATED], 'active_starts_at')).toBe('2026-12-01 23:00:00');
    expect(stored('calendar_activities', ['name', CREATED], 'active_ends_at')).toBe('2026-12-20 22:59:00');
    expect(stored('calendar_activities', ['name', CREATED], 'archived_at')).toBe('NULL');
    await ctx.close();
  });
});

for (const role of ['admin', 'moderator'] as const) {
  test.describe(`Activity edit (${role})`, () => {
    test('stored UTC shows as Paris time; untouched save is stable; time-only change persists', async ({ browser }) => {
      const ctx = await parisContext(browser, role);
      const page = await ctx.newPage();
      const form = new AdminActivityFormPage(page);
      // Each role edits its own activity so the two runs never see each other's changes.
      const name = role === 'admin' ? CREATED : GIFTS.toShuffle.name;
      await form.gotoEditByName(name);

      const ids = ['preview_starts_at', 'active_starts_at', 'active_ends_at'];
      const before: Record<string, { date: string; time: string }> = {};
      for (const id of ids) {
        const f = form.field(id);
        const utc = stored('calendar_activities', ['name', name], id);
        const expected = utcToParis(utc);
        expect(await f.values(), `${id}: ${utc} UTC shown in Paris`).toEqual(expected);
        expect(await f.submitted()).toBe(`${expected.date}T${expected.time}`);
        before[id] = expected;
      }
      await shot(page, `edit-${role}-dates`);

      // Untouched save
      // Seeded rows carry seconds the field never had: compare to the minute.
      const minute = (v: string) => v.slice(0, 16);
      const utcBefore = ids.map((id) => minute(stored('calendar_activities', ['name', name], id)));
      await form.submitButton.click();
      await page.waitForLoadState('networkidle');
      const utcAfter = ids.map((id) => minute(stored('calendar_activities', ['name', name], id)));
      expect(utcAfter, 'untouched save leaves UTC values identical (minutes, DST-free)').toEqual(utcBefore);

      // Change only the time of active_starts_at
      await form.gotoEditByName(name);
      const f = form.field('active_starts_at');
      await f.time.fill('06:31');
      await form.submitButton.click();
      await page.waitForLoadState('networkidle');

      await form.gotoEditByName(name);
      const after = await form.field('active_starts_at').values();
      expect(after).toEqual({ date: before['active_starts_at']?.date, time: '06:31' });
      await ctx.close();
    });
  });
}

test.describe('Type panels', () => {
  test('Secret Gift deadline pair behaves as the core fields', async ({ browser }) => {
    const ctx = await parisContext(browser, 'admin');
    const page = await ctx.newPage();
    const form = new AdminActivityFormPage(page);
    await form.gotoEditByName(GIFTS.open.name);

    const f = form.field('sg_registration_ends_at');
    expect((await f.values()).date).not.toBe('');
    await f.clearDate();
    await expect(f.time).toHaveValue('');
    expect(await f.submitted()).toBe('');
    await f.date.fill('2026-12-05');
    await expect(f.time).toHaveValue('00:00');
    await f.time.fill('09:05');
    expect(await f.submitted()).toBe('2026-12-05T09:05');
    await shot(page, 'secret-gift-config');
    await ctx.close();
  });

  test('validation error: deadline after activity start redisplays the pair unshifted', async ({ browser }) => {
    const ctx = await parisContext(browser, 'admin');
    const page = await ctx.newPage();
    const form = new AdminActivityFormPage(page);
    await form.gotoEditByName(GIFTS.open.name);

    const start = await form.field('active_starts_at').values();
    // One day after the activity start, at a distinctive minute.
    const next = new Date(`${start.date}T12:00:00Z`);
    next.setUTCDate(next.getUTCDate() + 1);
    const deadlineDate = next.toISOString().slice(0, 10);

    const f = form.field('sg_registration_ends_at');
    await f.fill(deadlineDate, '17:43');
    await form.submitButton.click();
    await page.waitForLoadState('networkidle');

    await expect(form.registrationEndsAtError).toBeVisible();
    const again = form.field('sg_registration_ends_at');
    expect(await again.values(), 'entered date/time redisplayed unshifted').toEqual({
      date: deadlineDate,
      time: '17:43',
    });
    await shot(page, 'secret-gift-validation-error');
    await ctx.close();
  });

  test('Quote Contest config: own pairs behave, mirrors are greyed and follow the core dates', async ({ browser }) => {
    const ctx = await parisContext(browser, 'admin');
    const page = await ctx.newPage();
    const form = new AdminActivityFormPage(page);
    await form.gotoEditByName(CONTEST_NAME);

    for (const f of [form.contestSubmissionsEnd, form.contestVotesStart]) {
      const v = await f.values();
      expect(v.date, `${f.id} has a date`).not.toBe('');
      await f.clearDate();
      await expect(f.time).toHaveValue('');
      await f.date.fill('2026-12-09');
      await expect(f.time).toHaveValue('00:00');
      await f.time.fill('11:11');
      expect(await f.submitted()).toBe('2026-12-09T11:11');
    }

    const startMirror = form.contestSubmissionsStartMirror;
    const endMirror = form.contestVotesEndMirror;
    for (const m of [startMirror, endMirror]) {
      await expect(m.date).toBeDisabled();
      await expect(m.time).toBeDisabled();
      await expect(m.hidden).toHaveCount(0); // nothing submitted
    }

    // Mirrors equal the core fields on load, then follow live edits.
    const coreStart = form.field('active_starts_at');
    const coreEnd = form.field('active_ends_at');
    expect(await startMirror.values()).toEqual(await coreStart.values());
    expect(await endMirror.values()).toEqual(await coreEnd.values());

    await coreStart.fill('2026-12-03', '10:20');
    expect(await startMirror.values()).toEqual({ date: '2026-12-03', time: '10:20' });
    await coreEnd.fill('2027-01-10', '18:45');
    expect(await endMirror.values()).toEqual({ date: '2027-01-10', time: '18:45' });
    await coreStart.clearDate();
    expect(await startMirror.values()).toEqual({ date: '', time: '' });
    await shot(page, 'quote-contest-config');
    await ctx.close();
  });
});

test.describe('Chapter form (author)', () => {
  test('toggle off reveals the pair; date picker bounded by min', async ({ browser }) => {
    const ctx = await parisContext(browser, 'author');
    const page = await ctx.newPage();
    const create = new ChapterCreatePage(page);
    await create.goto();

    await expect(create.publishAt.date).toBeHidden();
    await create.publishedToggle.click();
    await expect(create.publishAt.date).toBeVisible();
    await expect(create.publishAt.time).toBeVisible();

    const min = await create.publishAt.date.getAttribute('min');
    expect(min, 'min is a server-side YYYY-MM-DD').toMatch(/^\d{4}-\d{2}-\d{2}$/);
    // A day before min is below the bound: the browser's own validity check refuses it.
    const refused = await create.publishAt.date.evaluate((el: HTMLInputElement, minDate: string) => {
      const d = new Date(`${minDate}T12:00:00Z`);
      d.setUTCDate(d.getUTCDate() - 1);
      el.value = d.toISOString().slice(0, 10);
      return el.validity.rangeUnderflow;
    }, min!);
    expect(refused).toBe(true);
    await shot(page, 'chapter-create-publish-pair');
    await ctx.close();
  });

  test('schedule round trip: same local time on reopen, stored as that moment in UTC', async ({ browser }) => {
    const ctx = await parisContext(browser, 'author');
    const page = await ctx.newPage();
    const edit = new ChapterEditPage(page, STORY.slug, STORY.draftChapter.slug);
    await edit.goto();

    // Draft chapter: toggle is off, the pair is visible.
    await expect(edit.publishAt.date).toBeVisible();
    const year = new Date().getUTCFullYear() + 1;
    await edit.publishAt.fill(`${year}-02-10`, '21:37');
    expect(await edit.publishAt.submitted()).toBe(`${year}-02-10T21:37`);
    await edit.saveButton.click();
    await page.waitForLoadState('networkidle');

    expect(stored('story_chapters', ['slug', STORY.draftChapter.slug], 'publish_at')).toBe(`${year}-02-10 20:37:00`);

    await edit.goto();
    expect(await edit.publishAt.values()).toEqual({ date: `${year}-02-10`, time: '21:37' });
    await ctx.close();
  });

  test('validation error: the typed publish_at is redisplayed unshifted', async ({ browser }) => {
    const ctx = await parisContext(browser, 'author');
    const page = await ctx.newPage();
    const edit = new ChapterEditPage(page, STORY.slug, STORY.draftChapter.slug);
    await edit.goto();

    // Today (Paris), 00:01: passes the date `min`, fails the server's `after:now`.
    const today = await page.evaluate(() => new Intl.DateTimeFormat('sv-SE').format(new Date()));
    await edit.publishAt.fill(today, '00:01');
    await edit.saveButton.click();
    await page.waitForLoadState('networkidle');

    await expect(page).toHaveURL(/\/edit$/);
    const shown = await edit.publishAt.values();
    expect(shown).toEqual({ date: today, time: '00:01' });
    await shot(page, 'chapter-validation-error');
    await ctx.close();
  });
});

test.describe('Mobile 375 px', () => {
  for (const [label, role, open] of [
    ['calendar form', 'admin', async (p: Page) => { await new AdminActivityFormPage(p).gotoCreate(); }],
    ['chapter form', 'author', async (p: Page) => {
      const e = new ChapterEditPage(p, STORY.slug, STORY.draftChapter.slug);
      await e.goto();
    }],
  ] as const) {
    test(`${label}: pairs fit or wrap, no horizontal overflow, both tappable`, async ({ browser }) => {
      const ctx = await parisContext(browser, role, { viewport: { width: 375, height: 800 }, hasTouch: true });
      const page = await ctx.newPage();
      await open(page);

      const id = role === 'admin' ? 'active_starts_at' : 'publish_at';
      const f = new DateTimeField(page, id);
      await f.date.scrollIntoViewIfNeeded();
      const overflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
      expect(overflow, 'no horizontal overflow').toBeLessThanOrEqual(0);
      for (const input of [f.date, f.time]) {
        const box = await input.boundingBox();
        expect(box, 'input has a box').not.toBeNull();
        expect(box!.x).toBeGreaterThanOrEqual(0);
        expect(box!.x + box!.width).toBeLessThanOrEqual(375);
        expect(box!.width).toBeGreaterThan(60);
        expect(box!.height).toBeGreaterThan(24);
      }
      await f.date.fill('2026-12-01');
      await f.time.tap();
      await expect(f.time).toBeFocused();
      await shot(page, `mobile-${role}`);
      await ctx.close();
    });
  }
});

test.describe('Keyboard and a11y', () => {
  test('time is labelled Heure and follows the date; mirrors are disabled', async ({ browser }) => {
    const ctx = await parisContext(browser, 'admin');
    const page = await ctx.newPage();
    const form = new AdminActivityFormPage(page);
    await form.gotoEditByName(CONTEST_NAME);

    const f = form.field('qc_submissions_end_at');
    await expect(f.time).toHaveAccessibleName('Heure');
    await expect(f.group).toHaveAttribute('role', 'group');
    // The date input is named by the <label for>.
    await expect(f.date).toHaveAccessibleName(/Fin des soumissions|soumissions/i);

    // Tab from the date input eventually lands in the time input (date has day/month/year segments).
    await f.date.focus();
    let reached = false;
    for (let i = 0; i < 8 && !reached; i++) {
      await page.keyboard.press('Tab');
      reached = await f.time.evaluate((el) => el === document.activeElement || el.contains(document.activeElement));
    }
    expect(reached, 'Tab reaches the time input after the date input').toBe(true);

    // Mirrors: not editable, not focusable.
    for (const m of [form.contestSubmissionsStartMirror, form.contestVotesEndMirror]) {
      await expect(m.date).toBeDisabled();
      await expect(m.time).toBeDisabled();
      await m.date.evaluate((el: HTMLInputElement) => el.focus());
      expect(await m.date.evaluate((el) => el === document.activeElement)).toBe(false);
    }
    await ctx.close();
  });
});

test.describe('Dark theme', () => {
  test('both inputs are legible on the Calendar and chapter forms', async ({ browser }) => {
    for (const [role, open, id] of [
      ['admin', async (p: Page) => { await new AdminActivityFormPage(p).gotoCreate(); }, 'active_starts_at'],
      ['author', async (p: Page) => {
        await new ChapterEditPage(p, STORY.slug, STORY.draftChapter.slug).goto();
      }, 'publish_at'],
    ] as const) {
      const ctx = await parisContext(browser, role);
      const page = await ctx.newPage();
      await open(page);
      await page.evaluate(() => document.documentElement.setAttribute('data-appearance', 'dark'));
      const f = new DateTimeField(page, id);
      await f.date.fill('2026-12-01');
      await f.date.scrollIntoViewIfNeeded();
      const colors = await f.date.evaluate((el) => {
        const cs = getComputedStyle(el);
        return { color: cs.color, background: cs.backgroundColor, scheme: cs.colorScheme };
      });
      console.log(`[dark ${role}] ${JSON.stringify(colors)}`);
      expect(colors.color).not.toBe(colors.background);
      await shot(page, `dark-${role}`);
      await ctx.close();
    }
  });
});

test.describe('Out-of-scope surfaces', () => {
  test('Auth activation codes and Events log keep a single datetime-local', async ({ admin }) => {
    await admin.goto('/admin/auth/activation-codes/create');
    await expect(admin.locator('input#expires_at')).toHaveAttribute('type', 'datetime-local');
    await expect(admin.locator('input[type="time"]')).toHaveCount(0);

    await admin.goto('/admin/events/domain-events');
    await expect(admin.locator('input#filter_after')).toHaveAttribute('type', 'datetime-local');
    await expect(admin.locator('input#filter_before')).toHaveAttribute('type', 'datetime-local');
    await expect(admin.locator('input[type="time"]')).toHaveCount(0);
  });
});
