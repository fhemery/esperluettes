import path from 'node:path';
import { type Browser, type Page } from '@playwright/test';
import { DashboardPromotionCard } from '../../pages/DashboardPromotionCard';
import { LoginPage } from '../../pages/LoginPage';
import { NotificationInboxPage } from '../../pages/NotificationInboxPage';
import { NotificationPreferencesPage as Prefs } from '../../pages/NotificationPreferencesPage';
import { ReportButton } from '../../pages/ReportButton';
import { ACCOUNTS, PROMOTABLE, type RoleName, storageStatePath } from '../../support/fixtures';
import { ROOT } from '../../support/sail';
import { expect, test } from '../../support/test';

/**
 * VERIFY for `role-gated-notification-settings`: the two staff notification
 * types (new report, new promotion request) and their role-gated preference
 * rows, as a browser actually renders them — in French, which the PHP suite
 * (locale `zz`) never sees — and the two send paths driven through the real
 * UI (Alpine report modal, dashboard promotion form) down to the inbox link.
 *
 * Demotion / re-promotion is not here: it is a server-side role check with no
 * client behaviour, covered by StaffNotificationRoleLifecycleTest.
 *
 * Temporary: delete at WRAP.
 */

const STAFF_ROWS = [Prefs.ROW_REPORT_SUBMITTED, Prefs.ROW_PROMOTION_REQUESTED];
const REQUESTER_ROWS = [Prefs.ROW_PROMOTION_ACCEPTED, Prefs.ROW_PROMOTION_REJECTED];

/** Evidence for the VERIFY report, only when asked for (`VERIFY_SHOTS=1`). */
async function shot(page: Page, name: string): Promise<void> {
  if (!process.env.VERIFY_SHOTS) return;
  await page.screenshot({
    path: path.join(ROOT, 'docs/Feature_Planning/role-gated-notification-settings/shots', `${name}.png`),
    fullPage: true,
  });
}

/**
 * A page logged in as `role`, for tests generated in a loop over roles —
 * Playwright insists fixtures are destructured, so `fixtures[role]` is out.
 * Same stored session the role fixtures use.
 */
async function pageAs(browser: Browser, role: RoleName): Promise<Page> {
  const context = await browser.newContext({ storageState: path.join(ROOT, storageStatePath(role)) });
  return context.newPage();
}

test.describe('preference rows by role', () => {
  for (const role of ['user', 'confirmed'] as const) {
    test(`${role} sees only the requester-facing promotion rows`, async ({ browser }) => {
      const page = await pageAs(browser, role);
      const prefs = new Prefs(page);
      await prefs.goto();

      // The group is there (so the table rendered) with exactly the two old rows.
      expect(await prefs.labelsInGroup(Prefs.GROUP_MODERATION)).toEqual(REQUESTER_ROWS);
      for (const label of STAFF_ROWS) {
        await expect(prefs.row(label)).toHaveCount(0);
      }
      await shot(page, `settings-${role}`);
      await page.context().close();
    });
  }

  for (const role of ['moderator', 'admin', 'tech_admin'] as const satisfies readonly RoleName[]) {
    test(`${role} sees the two staff rows, site on and Discord off by default`, async ({ browser }) => {
      const page = await pageAs(browser, role);
      const prefs = new Prefs(page);
      await prefs.goto();

      const labels = await prefs.labelsInGroup(Prefs.GROUP_MODERATION);
      expect(labels).toHaveLength(4);
      expect(labels).toEqual(expect.arrayContaining([...REQUESTER_ROWS, ...STAFF_ROWS]));

      for (const label of STAFF_ROWS) {
        await expect(prefs.checkbox(label, 0), `${label}: site`).toBeChecked();
        await expect(prefs.checkbox(label, 1), `${label}: Discord`).not.toBeChecked();
      }

      // Unlinked Discord: the column keeps its usual warning, Site has none.
      await expect(prefs.discordHeader).toContainText('Compte Discord non lié');
      await expect(prefs.headerCells.filter({ hasText: 'Site' })).not.toContainText('non lié');

      await shot(page, `settings-${role}`);
      await page.context().close();
    });
  }
});

test('staff rows stay readable at 375px', async ({ moderator }) => {
  await moderator.setViewportSize({ width: 375, height: 812 });
  const prefs = new Prefs(moderator);
  await prefs.goto();

  // The page itself must not scroll sideways; any overflow is the table's own.
  const pageOverflow = await moderator.evaluate(
    () => document.documentElement.scrollWidth - document.documentElement.clientWidth,
  );
  expect(pageOverflow, 'page scrolls horizontally').toBeLessThanOrEqual(0);

  for (const label of STAFF_ROWS) {
    const labelCell = prefs.row(label).locator('td').first();
    await expect(labelCell).toBeVisible();
    const box = await labelCell.boundingBox();
    expect(box!.width, `${label} label squeezed`).toBeGreaterThan(120);
    // Both toggles of the row are reachable (in view or by scrolling the table).
    await prefs.toggle(label, 1).scrollIntoViewIfNeeded();
    await expect(prefs.toggle(label, 1)).toBeInViewport();
  }
  await shot(moderator, 'settings-moderator-375');
});

test('a report reaches the other staff inbox, linking to the reports queue', async ({
  confirmed,
  admin,
  moderator,
}) => {
  await confirmed.goto(`/profile/${ACCOUNTS.author.profileSlug}`);
  await new ReportButton(confirmed).report('Signalement E2E pour notification staff');

  const line = `${ACCOUNTS.confirmed.displayName} a déposé un nouveau signalement.`;

  for (const staff of [moderator, admin]) {
    const inbox = new NotificationInboxPage(staff);
    await inbox.goto();
    await expect(inbox.item(line)).toHaveCount(1);
  }

  const inbox = new NotificationInboxPage(admin);
  await shot(admin, 'inbox-admin-report');
  await Promise.all([
    admin.waitForURL(/\/admin\/moderation\/moderation-reports$/),
    inbox.link(inbox.item(line), 'Voir les signalements').click(),
  ]);
  await expect(admin.getByRole('heading', { name: 'Signalements' })).toBeVisible();

  // The reporter is not staff and gets nothing.
  const own = new NotificationInboxPage(confirmed);
  await own.goto();
  await expect(own.item('nouveau signalement')).toHaveCount(0);
});

test('a promotion request reaches staff, linking to the promotion queue', async ({ browser, admin }) => {
  const context = await browser.newContext();
  const requester = await context.newPage();
  await new LoginPage(requester).loginAs(PROMOTABLE);
  await new DashboardPromotionCard(requester).request();
  await context.close();

  const line = `${PROMOTABLE.displayName} a déposé une nouvelle demande de promotion.`;
  const inbox = new NotificationInboxPage(admin);
  await inbox.goto();
  await expect(inbox.item(line)).toHaveCount(1);
  await shot(admin, 'inbox-admin-promotion');

  await Promise.all([
    admin.waitForURL(/\/admin\/auth\/promotion-requests$/),
    inbox.link(inbox.item(line), 'Voir les demandes de promotion').click(),
  ]);
  await expect(admin.getByRole('heading', { name: 'Demandes de promotion' })).toBeVisible();
});

test('staff toggle choices survive a save and reload', async ({ tech_admin }) => {
  const prefs = new Prefs(tech_admin);
  await prefs.goto();

  await prefs.toggle(Prefs.ROW_REPORT_SUBMITTED, 0).click(); // site off
  await prefs.toggle(Prefs.ROW_PROMOTION_REQUESTED, 1).click(); // Discord on
  await prefs.save();

  await prefs.goto();
  await expect(prefs.checkbox(Prefs.ROW_REPORT_SUBMITTED, 0)).not.toBeChecked();
  await expect(prefs.checkbox(Prefs.ROW_REPORT_SUBMITTED, 1)).not.toBeChecked();
  await expect(prefs.checkbox(Prefs.ROW_PROMOTION_REQUESTED, 0)).toBeChecked();
  await expect(prefs.checkbox(Prefs.ROW_PROMOTION_REQUESTED, 1)).toBeChecked();
});
