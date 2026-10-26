import { devices } from '@playwright/test';
import path from 'node:path';
import { FanartStoryPage } from '../../pages/FanartStoryPage';
import { FANART, storageStatePath } from '../../support/fixtures';
import { ROOT } from '../../support/sail';
import { expect, test } from '../../support/test';

/** FEATURE — report flag on fanart miniature (temporary: deleted at WRAP). */

const ids = FANART.ids;
const SHOTS = 'docs/Feature_Planning/fanart-miniature-report-flag/shots';
const flagOf = (page: import('@playwright/test').Page, id: number) =>
  page.locator(`[data-fanart-id="${id}"]`).first().locator('xpath=..').locator('button[title="Signaler"]');

test('story tab: flag on others only, click opens report modal not viewer, inside the miniature corner', async ({ confirmed }) => {
  const sp = new FanartStoryPage(confirmed, FANART.storySlug);
  await sp.goto();
  await sp.openFanartsTab();
  await expect(sp.thumbnail(ids.work)).toBeVisible();
  await expect(flagOf(confirmed, ids.work)).toHaveCount(0);

  // Chapter 3 group is spoiler-collapsed: expand it.
  const group = sp.group(FANART.chapterIds.simple);
  await group.getByRole('button', { name: 'Montrer tout' }).click();
  const flag = flagOf(confirmed, ids.chapterThree);
  await expect(flag).toBeVisible();

  const thumb = (await sp.thumbnail(ids.chapterThree).boundingBox())!;
  const f = (await flag.boundingBox())!;
  expect(f.x + f.width).toBeGreaterThan(thumb.x + thumb.width - 20);
  expect(f.y + f.height).toBeGreaterThan(thumb.y + thumb.height - 20);
  expect(f.x + f.width).toBeLessThanOrEqual(thumb.x + thumb.width + 1);
  await confirmed.screenshot({ path: path.join(ROOT, SHOTS, 'story-tab.png') });

  await flag.click();
  const modal = confirmed.getByRole('heading', { name: 'Signaler ce contenu' });
  await expect(modal).toBeVisible();
  await expect(sp.viewer.dialog).toBeHidden();
  const box = (await modal.boundingBox())!;
  expect(box.width).toBeGreaterThan(200);
  await confirmed.screenshot({ path: path.join(ROOT, SHOTS, 'story-modal.png') });
});

test('profile tab: other profile has flags with an unclipped modal, own has none', async ({ confirmed }) => {
  await confirmed.goto('/profile/e2e-tech-admin/fanarts');
  const thumb = confirmed.locator(`[data-fanart-id="${ids.chapterThree}"]`).first();
  if (!(await thumb.isVisible())) {
    { await confirmed.getByRole('button', { name: /^Images/ }).click(); await confirmed.getByRole('button', { name: /^Histoire E2E/ }).click(); await confirmed.getByRole('button', { name: /^Chapitre simple/ }).click(); }
  }
  await expect(thumb).toBeVisible();
  const flag = flagOf(confirmed, ids.chapterThree);
  await expect(flag).toBeVisible();
  await flag.click();
  const modal = confirmed.getByRole('heading', { name: 'Signaler ce contenu' });
  await expect(modal).toBeVisible();
  const box = (await modal.boundingBox())!;
  expect(box.width).toBeGreaterThan(200);
  expect(box.x).toBeGreaterThanOrEqual(0);
  await confirmed.screenshot({ path: path.join(ROOT, SHOTS, 'profile-modal.png') });

  await confirmed.goto('/profile/e2e-confirmed/fanarts');
  const own = confirmed.locator(`[data-fanart-id="${ids.work}"]`).first();
  if (!(await own.isVisible())) { await confirmed.getByRole('button', { name: /^Images/ }).click(); await confirmed.getByRole('button', { name: /^Histoire E2E/ }).click(); }
  await expect(own).toBeVisible();
  await expect(flagOf(confirmed, ids.work)).toHaveCount(0);
});

test('keyboard: Tab reaches the flag, Enter opens the form', async ({ confirmed }) => {
  const sp = new FanartStoryPage(confirmed, FANART.storySlug);
  await sp.goto();
  await sp.openFanartsTab();
  await sp.group(FANART.chapterIds.simple).getByRole('button', { name: 'Montrer tout' }).click();
  await sp.thumbnail(ids.chapterThree).focus();
  await confirmed.keyboard.press('Tab');
  await expect(flagOf(confirmed, ids.chapterThree)).toBeFocused();
  await confirmed.keyboard.press('Enter');
  await expect(confirmed.getByRole('heading', { name: 'Signaler ce contenu' })).toBeVisible();
});

test('mobile 375 px: flag visible and tappable', async ({ browser }) => {
  const context = await browser.newContext({
    ...devices['Pixel 7'],
    viewport: { width: 375, height: 800 },
    storageState: path.join(ROOT, storageStatePath('confirmed')),
  });
  const page = await context.newPage();
  const sp = new FanartStoryPage(page, FANART.storySlug);
  await sp.goto();
  await sp.openFanartsTab();
  await sp.group(FANART.chapterIds.simple).getByRole('button', { name: 'Montrer tout' }).click();
  const flag = flagOf(page, ids.chapterThree);
  await expect(flag).toBeVisible();
  const b = (await flag.boundingBox())!;
  console.log('flag size', b.width, b.height);
  await page.screenshot({ path: path.join(ROOT, SHOTS, 'mobile.png') });
  await flag.tap();
  await expect(page.getByRole('heading', { name: 'Signaler ce contenu' })).toBeVisible();
  await expect(sp.viewer.dialog).toBeHidden();
  await context.close();
});
