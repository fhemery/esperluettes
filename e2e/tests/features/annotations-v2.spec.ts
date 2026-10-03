import { devices, type Browser, type Page } from '@playwright/test';
import path from 'node:path';
import { ChapterAnnotations } from '../../pages/ChapterAnnotations';
import { ChapterPage } from '../../pages/ChapterPage';
import { CommentThread } from '../../pages/CommentThread';
import { COAUTHORED_STORY, STORY, storageStatePath, type RoleName } from '../../support/fixtures';
import { ROOT } from '../../support/sail';
import { expect, test } from '../../support/test';

/**
 * FEATURE — annotations v2, the commenter ↔ author round trip in a browser:
 * emoji reactions as pending adds, the save banner, the commenter overlay of
 * the « N annotations » pop-up, and its reply thread. Everything here is Alpine
 * over localStorage and JSON endpoints; the rules behind it are PHP-tested.
 *
 * Uses `chapitre-illustre-7` (story 1, written by `author`), where `confirmed`
 * has no root comment in the seeded world: the spec posts it.
 */

const CHAPTER = STORY.illustratedChapter;

const ROOT_BODY =
  'Un commentaire e2e assez long pour franchir le seuil des cent quarante caractères ' +
  'exigés pour un commentaire racine, avant les réactions et annotations.';
const EDITED = 'Un cœur, et une phrase pour dire pourquoi.';
const AUTHOR_REPLY = "Merci, je garde l'italique.";
const READER_REPLY = 'Avec plaisir, et le gras aussi.';

// The touch run needs the root comment the round trip posts.
test.describe.configure({ mode: 'serial' });

test('reader reacts and edits; author replies; reader replies and deletes', async ({ confirmed, author }) => {
  let commentId = 0;

  await test.step('reader posts a root comment, reacts with ❤️ and saves from the banner', async () => {
    const chapter = new ChapterPage(confirmed, STORY.slug, CHAPTER.slug);
    const thread = new CommentThread(confirmed);
    const annotations = new ChapterAnnotations(confirmed);
    await chapter.goto();
    commentId = await thread.postRoot(ROOT_BODY);

    await chapter.selectText(chapter.textBlocks.first().locator('p'));
    await expect(annotations.annotateButton).toBeVisible();
    await annotations.react('heart');

    await expect(annotations.saveBanner).toContainText('Vous avez 1 annotation non sauvegardée');
    await annotations.saveChanges();

    await thread.scrollToBottom();
    await expect(annotations.countLabel(thread.item(commentId))).toHaveText('1 annotation');
  });

  await test.step('reader edits it from the pop-up and saves', async () => {
    const thread = new CommentThread(confirmed);
    const annotations = new ChapterAnnotations(confirmed);

    await annotations.openPublished(thread.item(commentId));
    await expect(annotations.serverRows).toHaveCount(1);
    await expect(annotations.serverRows.first()).toContainText('❤️');

    await annotations.editRow(annotations.serverRows.first());
    await annotations.captureEditor.fill(EDITED);
    await annotations.captureSave.click();
    await expect(annotations.captureForm).toBeHidden();

    await expect(annotations.pendingRow('edited')).toContainText(EDITED);
    await expect(annotations.saveBanner).toContainText('Vous avez 1 annotation non sauvegardée');

    await confirmed.keyboard.press('Escape');
    await expect(annotations.serverDialog).toBeHidden();
    await annotations.saveChanges();

    await annotations.openPublished(thread.item(commentId));
    await expect(annotations.serverRows).toHaveCount(1);
    await expect(annotations.serverRows.first()).toContainText(EDITED);
    await expect(annotations.pendingRow('edited')).toHaveCount(0);
  });

  await test.step('author replies and sees the hint', async () => {
    const chapter = new ChapterPage(author, STORY.slug, CHAPTER.slug);
    const thread = new CommentThread(author);
    const annotations = new ChapterAnnotations(author);
    await chapter.goto();
    await thread.scrollToBottom();

    await annotations.openPublished(thread.item(commentId));
    const row = annotations.serverRows.first();
    await expect(row).toContainText(EDITED);

    await annotations.reply(row, AUTHOR_REPLY);
    await expect(annotations.replies(row)).toContainText(AUTHOR_REPLY);
    await expect(annotations.replyHint(row)).toBeVisible();
  });

  await test.step('reader sees the reply, replies, then deletes their own reply', async () => {
    const thread = new CommentThread(confirmed);
    const annotations = new ChapterAnnotations(confirmed);
    await confirmed.reload();
    await thread.scrollToBottom();

    await annotations.openPublished(thread.item(commentId));
    const row = annotations.serverRows.first();
    await expect(annotations.replies(row)).toHaveCount(1);
    await expect(annotations.replies(row)).toContainText(AUTHOR_REPLY);
    await expect(annotations.replyHint(row)).toBeHidden();

    await annotations.reply(row, READER_REPLY);
    const mine = annotations.replies(row).filter({ hasText: READER_REPLY });
    await expect(mine).toHaveCount(1);

    await annotations.deleteReply(mine);
    await expect(annotations.replies(row)).toHaveCount(1);
    await expect(annotations.replies(row)).toContainText(AUTHOR_REPLY);
  });
});

test('touch selection: 🔥 becomes a pending add, « Tout annuler » drops it', async ({ browser }) => {
  const page = await mobilePage(browser, 'confirmed');
  const chapter = new ChapterPage(page, STORY.slug, CHAPTER.slug);
  const annotations = new ChapterAnnotations(page);
  await chapter.goto();

  await chapter.touchSelectText(chapter.textBlocks.nth(1).locator('p'));
  await annotations.react('fire', { tap: true });
  await expect(annotations.saveBanner).toContainText('Vous avez 1 annotation non sauvegardée');

  await annotations.discardChanges();
  await page.reload();
  await expect(annotations.saveBanner).toBeHidden();

  await page.context().close();
});

test('an over-cap selection hides « Annoter » and the emoji alike', async ({ admin }) => {
  const chapter = new ChapterPage(admin, COAUTHORED_STORY.slug, COAUTHORED_STORY.chapter.slug);
  const annotations = new ChapterAnnotations(admin);
  await chapter.goto();

  // The chapter's second paragraph is over the 500-character highlight cap.
  await chapter.selectText(chapter.textBlocks.first().locator('p').nth(1));
  await expect(annotations.toolbarTooLong).toBeVisible();
  await expect(annotations.annotateButton).toBeHidden();
  await expect(annotations.reactions).toBeHidden();
});

async function mobilePage(browser: Browser, role: RoleName): Promise<Page> {
  const context = await browser.newContext({
    ...devices['Pixel 7'],
    viewport: { width: 375, height: 740 },
    storageState: path.join(ROOT, storageStatePath(role)),
  });
  return context.newPage();
}
