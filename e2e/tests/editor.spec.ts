import { ChapterEditPage } from '../pages/ChapterEditPage';
import { ChapterPage } from '../pages/ChapterPage';
import { STORY } from '../support/fixtures';
import { expect, test } from '../support/test';

/**
 * Editor domain — the surfaces only a browser can prove.
 *
 * Everything here is either client-side (Quill boots, the toolbar renders, the
 * content survives the round-trip) or role-dependent (who may open the form).
 * Rendering, validation and authorisation *rules* are covered by the PHP suite
 * and deliberately not repeated.
 */

const EDITOR_ASSET = /editor(-bundle)?-[A-Za-z0-9_]+\.(js|css)$/;

test.describe('editor assets', () => {
  test('a read-only chapter loads no editor asset', async ({ guest }) => {
    const requested: string[] = [];
    guest.on('response', (response) => {
      const { pathname } = new URL(response.url());
      if (EDITOR_ASSET.test(pathname)) requested.push(pathname);
    });

    const chapter = new ChapterPage(guest);
    await chapter.goto();

    await expect(chapter.content).toContainText('premier chapitre');
    expect(requested, 'editor assets on a read-only page').toEqual([]);
  });

  test('the edit form loads the editor bundle exactly once', async ({ author }) => {
    const scripts: string[] = [];
    author.on('response', (response) => {
      const { pathname } = new URL(response.url());
      if (EDITOR_ASSET.test(pathname) && pathname.endsWith('.js')) scripts.push(pathname);
    });

    await new ChapterEditPage(author).goto();

    expect(scripts, 'editor bundle requests').toHaveLength(1);
  });
});

test.describe('the chapter editor', () => {
  test('boots both editors with a populated toolbar', async ({ author }) => {
    const edit = new ChapterEditPage(author);
    await edit.goto();

    await expect(edit.content.body).toBeVisible();
    await expect(edit.content.toolbar).toBeVisible();
    await expect(edit.content.toolbar.locator('button')).not.toHaveCount(0);

    // Quill loaded the stored content rather than starting blank.
    await expect(edit.content.body).toContainText('premier chapitre');
  });

  test('keeps what was typed through save and re-render', async ({ author }) => {
    const marker = `E2E ${Date.now()}`;
    const edit = new ChapterEditPage(author);

    await edit.goto();
    await edit.content.fill(`Contenu réécrit. ${marker}`);
    await edit.save();

    // Re-read the page rather than trusting the redirect's body.
    const chapter = new ChapterPage(author);
    await chapter.goto();
    await expect(chapter.content).toContainText(marker);
  });

  test('counts characters as you type', async ({ author }) => {
    const edit = new ChapterEditPage(author);
    await edit.goto();

    const before = Number(await edit.content.counter.innerText());
    await edit.content.type(' encore du texte');

    await expect
      .poll(async () => Number(await edit.content.counter.innerText()), { message: 'character counter' })
      .toBeGreaterThan(before);
  });
});

test.describe('who may open the editor', () => {
  const editPath = `/stories/${STORY.slug}/chapters/${STORY.publishedChapter.slug}/edit`;

  test('a guest is sent to the login form', async ({ guest }) => {
    await guest.goto(editPath);

    await expect(guest).toHaveURL(/\/login/);
  });

  test('a logged-in non-author cannot reach it', async ({ confirmed }) => {
    const response = await confirmed.goto(editPath);

    expect(response?.status(), 'a non-author must not get the form').toBeGreaterThanOrEqual(400);
  });

  test('the author can', async ({ author }) => {
    const response = await new ChapterEditPage(author).tryGoto();

    expect(response?.status()).toBe(200);
  });
});
