import { devices } from '@playwright/test';
import path from 'node:path';
import { FanartCreatePage } from '../../pages/FanartStoryPage';
import { FANART, storageStatePath } from '../../support/fixtures';
import { ROOT } from '../../support/sail';
import { expect, test } from '../../support/test';

/**
 * FEATURE — « Partager sur Discord » on sound fanart (temporary: deleted at WRAP).
 *
 * Only the checkbox surface is browser-side; what is stored and the feed are
 * feature tests in app/Domains/Fanart/Tests.
 */

const story = FANART.storySlug;

test.describe('reader (confirmed) sound submission form', () => {
  test('checkbox shown unchecked, and stays ticked after a failed submit', async ({ confirmed }) => {
    const create = new FanartCreatePage(confirmed, story, 'audio');
    await create.goto();

    await expect(create.shareOnDiscord).toBeVisible();
    await expect(create.shareOnDiscord).not.toBeChecked();
    await expect(confirmed.getByText('Partager sur Discord')).toBeVisible();

    // Invalid submit: ticked, but no file and no title.
    await create.shareOnDiscord.check();
    const posted = confirmed.waitForResponse((r) => r.request().method() === 'POST' && r.url().endsWith(`/stories/${story}/fanarts`));
    await create.submit.click();
    // The server refused it (redirect back with errors), the page was reloaded.
    expect((await posted).status()).toBe(302);
    await confirmed.waitForLoadState('load');
    await expect(create.shareOnDiscord).toBeVisible();
    await expect(create.shareOnDiscord).toBeChecked();
  });

  test('mobile 375 px: the row stays inside the viewport', async ({ browser }) => {
    const context = await browser.newContext({
      ...devices['Pixel 7'],
      viewport: { width: 375, height: 800 },
      storageState: path.join(ROOT, storageStatePath('confirmed')),
    });
    const page = await context.newPage();
    const create = new FanartCreatePage(page, story, 'audio');
    await create.goto();
    const box = (await create.shareOnDiscord.locator('xpath=ancestor::label[1]').boundingBox())!;
    expect(box.x).toBeGreaterThanOrEqual(0);
    expect(box.x + box.width).toBeLessThanOrEqual(375);
    await expect(create.shareOnDiscord).toBeVisible();
    await context.close();
  });
});

test.describe('author artwork upload forms', () => {
  test('sound: checkbox shown unchecked', async ({ author }) => {
    const create = new FanartCreatePage(author, story, 'audio');
    await create.goto();
    await expect(author.locator('[data-kind="audio"]')).toBeVisible();
    await expect(create.shareOnDiscord).toBeVisible();
    await expect(create.shareOnDiscord).not.toBeChecked();
  });

  test('image: checkbox still shown unchecked (unchanged)', async ({ author }) => {
    const create = new FanartCreatePage(author, story, 'image');
    await create.goto();
    await expect(author.locator('[data-kind="image"]')).toBeVisible();
    await expect(create.shareOnDiscord).toBeVisible();
    await expect(create.shareOnDiscord).not.toBeChecked();
  });
});

test.describe('reader image form (unchanged)', () => {
  test('checkbox shown unchecked', async ({ confirmed }) => {
    const create = new FanartCreatePage(confirmed, story, 'image');
    await create.goto();
    await expect(create.shareOnDiscord).toBeVisible();
    await expect(create.shareOnDiscord).not.toBeChecked();
  });
});
