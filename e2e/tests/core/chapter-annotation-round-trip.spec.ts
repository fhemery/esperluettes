import type { Page } from '@playwright/test';
import { ChapterAnnotations } from '../../pages/ChapterAnnotations';
import { ChapterPage } from '../../pages/ChapterPage';
import { CommentThread } from '../../pages/CommentThread';
import { STORY } from '../../support/fixtures';
import { expect, test } from '../../support/test';

/**
 * CORE — chapter annotations after the root comment: the commenter ↔ author
 * round trip.
 *
 * Once a reader's root comment exists, their reactions and edits live only in
 * localStorage (the comment-draft store's `annotationChanges` slot) until the
 * sticky save banner sends them in one `PUT`; replies are written from the
 * Alpine pop-up over JSON endpoints. None of that runs in a PHP test, and all
 * of it is breakable from the comment-draft store, the shared selection
 * toolbar or the comment list — hence core, beside `chapter-annotations.spec.ts`
 * (the before-the-root half).
 *
 * Uses `chapitre-illustre-7` (story 1, written by `author`), where `confirmed`
 * has no root comment in the seeded world: the test posts it.
 */

const CHAPTER = STORY.illustratedChapter;

const ROOT_BODY =
  'Un commentaire e2e assez long pour franchir le seuil des cent quarante caractères ' +
  'exigés pour un commentaire racine, avant les réactions et annotations.';
const EDITED = 'Un cœur, et une phrase pour dire pourquoi.';
const AUTHOR_REPLY = "Merci, je garde l'italique.";
const READER_REPLY = 'Avec plaisir, et le gras aussi.';

function onChapter(page: Page) {
  return {
    chapter: new ChapterPage(page, STORY.slug, CHAPTER.slug),
    thread: new CommentThread(page),
    annotations: new ChapterAnnotations(page),
  };
}

test('reader reacts and edits; author replies; reader replies and deletes', async ({ confirmed, author }) => {
  let rootId = 0;

  await test.step('reader posts a root comment, reacts with ❤️ and saves from the banner', async () => {
    const { chapter, thread, annotations } = onChapter(confirmed);
    await chapter.goto();
    rootId = await thread.postRoot(ROOT_BODY);
    expect(await annotations.rootCommentId()).toBe(rootId);

    await chapter.selectText(chapter.textBlocks.first().locator('p'));
    await expect(annotations.annotateButton).toBeVisible();
    await annotations.react('heart');

    await expect(annotations.saveBanner).toContainText('Vous avez 1 annotation non sauvegardée');
    await annotations.saveChanges();

    await thread.scrollToBottom();
    await expect(annotations.countLabel(thread.item(rootId))).toHaveText('1 annotation');
  });

  await test.step('reader edits it from the pop-up and saves; no « Répondre » yet', async () => {
    const { thread, annotations } = onChapter(confirmed);

    await annotations.openPublished(thread.item(rootId));
    await expect(annotations.serverRows).toHaveCount(1);
    await expect(annotations.serverRows.first()).toContainText('❤️');
    await expect(annotations.replyButton(annotations.serverRows.first())).toHaveCount(0);

    await annotations.editRow(annotations.serverRows.first());
    await annotations.captureEditor.fill(EDITED);
    await annotations.captureSave.click();
    await expect(annotations.captureForm).toBeHidden();

    await expect(annotations.pendingRow('edited')).toContainText(EDITED);
    await expect(annotations.saveBanner).toContainText('Vous avez 1 annotation non sauvegardée');

    await confirmed.keyboard.press('Escape');
    await expect(annotations.serverDialog).toBeHidden();
    await annotations.saveChanges();

    await annotations.openPublished(thread.item(rootId));
    await expect(annotations.serverRows).toHaveCount(1);
    await expect(annotations.serverRows.first()).toContainText(EDITED);
    await expect(annotations.pendingRow('edited')).toHaveCount(0);
  });

  await test.step('author replies and sees the hint', async () => {
    const { chapter, thread, annotations } = onChapter(author);
    await chapter.goto();
    await thread.scrollToBottom();

    await annotations.openPublished(thread.item(rootId));
    const row = annotations.serverRows.first();
    await expect(row).toContainText(EDITED);

    await annotations.reply(row, AUTHOR_REPLY);
    await expect(annotations.replies(row)).toContainText(AUTHOR_REPLY);
    await expect(annotations.replyHint(row)).toBeVisible();
  });

  await test.step('reader sees the reply and « Répondre », replies, then deletes their own reply', async () => {
    const { thread, annotations } = onChapter(confirmed);
    await confirmed.reload();
    await thread.scrollToBottom();

    await annotations.openPublished(thread.item(rootId));
    const row = annotations.serverRows.first();
    await expect(annotations.replies(row)).toHaveCount(1);
    await expect(annotations.replies(row)).toContainText(AUTHOR_REPLY);
    await expect(annotations.replyHint(row)).toBeHidden();
    await expect(annotations.replyButton(row)).toBeVisible();

    await annotations.reply(row, READER_REPLY);
    const mine = annotations.replies(row).filter({ hasText: READER_REPLY });
    await expect(mine).toHaveCount(1);

    await annotations.deleteReply(mine);
    await expect(annotations.replies(row)).toHaveCount(1);
    await expect(annotations.replies(row)).toContainText(AUTHOR_REPLY);
  });
});
