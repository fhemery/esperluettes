import { ChapterEditPage } from '../../pages/ChapterEditPage';
import { ChapterPage } from '../../pages/ChapterPage';
import { expect, test } from '../../support/test';

/**
 * CORE — the rich-text editor.
 *
 * `<x-editor::rich-text>` is on every authoring surface in the app (chapter,
 * story, news, profile, static page), so if Quill stops booting or stops
 * serialising into the hidden textarea, authoring is dead everywhere. That is
 * what earns it a place in core.
 *
 * Only browser-only claims live here. Who may open the form, what the server
 * stores and how it sanitises are asserted far more cheaply in
 * app/Domains/Story/Tests/Feature/Chapters/EditChapterTest.php.
 */

const EDITOR_ASSET = /editor(-bundle)?-[A-Za-z0-9_]+\.(js|css)$/;

test('a read-only page loads no editor asset', async ({ guest }) => {
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

test('every editor on the page boots with its toolbar', async ({ author }) => {
  const edit = new ChapterEditPage(author);
  await edit.goto();

  await expect(edit.content.body).toBeVisible();
  await expect(edit.content.toolbar).toBeVisible();
  await expect(edit.content.toolbar.locator('button')).not.toHaveCount(0);

  // Quill loaded the stored content rather than starting blank.
  await expect(edit.content.body).toContainText('premier chapitre');
});

test('what is typed survives save and re-render', async ({ author }) => {
  const marker = `E2E ${Date.now()}`;
  const edit = new ChapterEditPage(author);

  await edit.goto();
  await edit.content.fill(`Contenu réécrit. ${marker}`);
  await edit.save();

  // Re-read the page rather than trusting the redirect's body. The PHP suite
  // posts content directly; only a browser proves Quill fed the textarea.
  const chapter = new ChapterPage(author);
  await chapter.goto();
  await expect(chapter.content).toContainText(marker);
});

test('the character counter follows what is typed', async ({ author }) => {
  const edit = new ChapterEditPage(author);
  await edit.goto();

  const before = Number(await edit.content.counter.innerText());
  await edit.content.type(' encore du texte');

  await expect
    .poll(async () => Number(await edit.content.counter.innerText()), { message: 'character counter' })
    .toBeGreaterThan(before);
});
