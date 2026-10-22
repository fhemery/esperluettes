import { devices } from '@playwright/test';
import path from 'node:path';
import { FanartCreatePage, FanartStoryPage } from '../../pages/FanartStoryPage';
import { FANART, STORY, storageStatePath } from '../../support/fixtures';
import { ROOT } from '../../support/sail';
import { expect, test } from '../../support/test';

/**
 * FEATURE — fanart / artwork module (temporary: deleted at WRAP).
 *
 * Browser-only behaviour; roles, lifecycle, notifications and stored state are
 * feature tests in app/Domains/Fanart/Tests.
 */

const story = () => FANART.storySlug;
const ids = FANART.ids;
const chapters = FANART.chapterIds;

test.describe('fanarts tab', () => {
  test('« Montrer tout » opens a collapsed group, « Cacher tout » closes an open one', async ({ confirmed }) => {
    const storyPage = new FanartStoryPage(confirmed, story());
    await storyPage.goto();
    await storyPage.openFanartsTab();
    await expect(storyPage.groups.first()).toBeVisible();
    // Let Alpine initialise the injected fragment.
    await expect(storyPage.group('work').locator('[data-fanart-id]').first()).toBeVisible();

    // A chapter the reader has not read is collapsed behind a spoiler notice.
    const collapsed = storyPage.group(chapters.simple);
    await expect(collapsed).toHaveAttribute('data-collapsed', 'true');
    await collapsed.getByRole('button', { name: 'Montrer tout' }).click();
    await expect(collapsed.locator('[data-fanart-id]').first()).toBeVisible({ timeout: 3000 });
    await expect(collapsed.getByText('Attention, cette section peut dévoiler')).toBeHidden();

    await collapsed.getByRole('button', { name: 'Cacher tout' }).click();
    await expect(collapsed.locator('[data-fanart-id]').first()).toBeHidden();

    const open = storyPage.group('work');
    await open.getByRole('button', { name: 'Cacher tout' }).click();
    await expect(open.locator('[data-fanart-id]').first()).toBeHidden({ timeout: 3000 });
  });
});

test.describe('viewer', () => {
  test('desktop: opens from a thumbnail, the image fits, Escape and the close button close it', async ({ confirmed }) => {
    const storyPage = new FanartStoryPage(confirmed, story());
    await storyPage.goto();
    await storyPage.openFanartsTab();

    await storyPage.thumbnail(ids.work).click();
    const viewer = storyPage.viewer;
    await viewer.expectOpen();
    await expect(viewer.heading).toHaveText(FANART.titles.work);
    await expect.poll(async () => viewer.image.evaluate((img: HTMLImageElement) => img.complete && img.naturalWidth > 0)).toBe(true);
    const box = await viewer.image.boundingBox();
    expect(box).not.toBeNull();
    expect(box!.height).toBeLessThanOrEqual(900);
    expect(box!.x + box!.width).toBeLessThanOrEqual(1280);

    await confirmed.keyboard.press('Escape');
    await expect(viewer.dialog).toBeHidden();

    await storyPage.thumbnail(ids.work).click();
    await viewer.expectOpen();
    await viewer.closeButton.click();
    await expect(viewer.dialog).toBeHidden();
  });

  test('mobile 375 px: image stacked above the text, closable', async ({ browser }) => {
    const context = await browser.newContext({
      ...devices['Pixel 7'],
      viewport: { width: 375, height: 800 },
      storageState: path.join(ROOT, storageStatePath('confirmed')),
    });
    const page = await context.newPage();
    const storyPage = new FanartStoryPage(page, story());
    await storyPage.goto(`?tab=fanarts&fanart=${ids.work}`);
    await storyPage.viewer.expectOpen();
    await expect.poll(async () => storyPage.viewer.image.evaluate((img: HTMLImageElement) => img.complete && img.naturalWidth > 0)).toBe(true);

    const image = (await storyPage.viewer.image.boundingBox())!;
    const heading = (await storyPage.viewer.heading.boundingBox())!;
    expect(image.x + image.width).toBeLessThanOrEqual(375 + 1);
    expect(heading.y).toBeGreaterThanOrEqual(image.y + image.height - 1);

    await storyPage.viewer.closeButton.click();
    await expect(storyPage.viewer.dialog).toBeHidden();
    await context.close();
  });
});

test.describe('create page', () => {
  test('reader proposes an image and is told it awaits validation', async ({ confirmed }) => {
    const create = new FanartCreatePage(confirmed, story());
    await create.goto();

    await create.title.fill('Proposition E2E');
    await create.imageInput.setInputFiles(path.join(ROOT, 'e2e/fixtures/gift-image.png'));
    await create.attestation.check();
    await create.submit.click();

    await expect(confirmed.getByText('Votre fanart a été envoyé.')).toBeVisible();
  });
});

// Changes the shared database (chapters get marked read): keep it last.
test.describe('story-page player', () => {
  test('speaker plays, a finished chapter is marked read, the next chapter with audio starts, then it stops', async ({ confirmed }) => {
    const storyPage = new FanartStoryPage(confirmed, story());
    await storyPage.goto();

    const markRead = confirmed.waitForRequest(
      (request) => request.method() === 'POST' && request.url().includes(`/chapters/${STORY.publishedChapter.slug}/read`),
      { timeout: 20_000 },
    );
    await storyPage.audioIcon(chapters.published).click();
    await expect(storyPage.player).toBeVisible();
    await expect(storyPage.player.locator('audio')).toHaveAttribute('src', new RegExp(`/fanarts/${ids.soundOne}/file$`));
    await expect(storyPage.audioIcon(chapters.published)).toHaveAttribute('aria-pressed', 'true');

    await markRead;
    // The next chapter with a published sound starts by itself.
    await expect(storyPage.player.locator('audio')).toHaveAttribute('src', new RegExp(`/fanarts/${ids.soundThree}/file$`), {
      timeout: 20_000,
    });
    await expect(storyPage.audioIcon(chapters.simple)).toHaveAttribute('aria-pressed', 'true');

    // After the last one the player goes away.
    await expect(storyPage.player).toBeHidden({ timeout: 20_000 });
    await expect(storyPage.audioIcon(chapters.simple)).toHaveAttribute('aria-pressed', 'false');
  });
});
