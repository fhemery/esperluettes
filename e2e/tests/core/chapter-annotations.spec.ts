import { ChapterAnnotations } from '../../pages/ChapterAnnotations';
import { ChapterPage } from '../../pages/ChapterPage';
import { CommentThread } from '../../pages/CommentThread';
import { STORY } from '../../support/fixtures';
import { expect, test } from '../../support/test';

/**
 * CORE — chapter annotations, the whole browser loop.
 *
 * A reader's annotations live only in localStorage (the comment-draft store's
 * `annotations` slot) until they are posted with the root comment; the
 * selection toolbar is shared with Quote; the pop-ups are Alpine over a JSON
 * endpoint. None of that runs in a PHP test, and all of it is breakable from
 * the comment-draft store, the toolbar or the comment list — hence core.
 *
 * Uses `chapitre-compte-5` (story 1, written by `author`): two paragraphs in one
 * text block, and no root comment from `confirmed` in the seeded world.
 */

const CHAPTER = STORY.countedChapter;

const KEPT = 'Une annotation gardée, à publier avec le commentaire.';
const DROPPED = 'Une annotation jetée avant la publication.';
const ROOT_BODY =
  'Un commentaire e2e assez long pour franchir le seuil des cent quarante caractères ' +
  'exigés pour un commentaire racine sur un chapitre, avec ses annotations.';

test('reader annotates, publishes with the root comment; author processes; moderator deletes', async ({
  confirmed,
  author,
  moderator,
}) => {
  let commentId = 0;

  await test.step('reader saves two drafts from two selections', async () => {
    const chapter = new ChapterPage(confirmed, STORY.slug, CHAPTER.slug);
    const annotations = new ChapterAnnotations(confirmed);
    await chapter.goto();

    const paragraphs = chapter.textBlocks.first().locator('p');
    await chapter.selectText(paragraphs.nth(0));
    await annotations.capture(KEPT);

    await chapter.selectText(paragraphs.nth(1));
    await annotations.capture(DROPPED);

    await expect(annotations.banner).toContainText('2 annotations');
  });

  await test.step('drafts survive a reload; one is deleted from the pop-up', async () => {
    const annotations = new ChapterAnnotations(confirmed);
    await confirmed.reload();

    await expect(annotations.banner).toContainText('2 annotations');
    await annotations.openDrafts();
    await expect(annotations.draftRows).toHaveCount(2);

    await annotations.deleteDraft(annotations.draftRows.filter({ hasText: DROPPED }));
    await expect(annotations.draftRows).toHaveCount(1);
    await expect(annotations.draftRows).toContainText(KEPT);

    await confirmed.keyboard.press('Escape');
    await expect(annotations.draftsDialog).toBeHidden();
    await expect(annotations.banner).toContainText('1 annotation');
  });

  await test.step('posting the root comment publishes the remaining draft', async () => {
    const thread = new CommentThread(confirmed);
    const annotations = new ChapterAnnotations(confirmed);

    commentId = await thread.postRoot(ROOT_BODY);

    await expect(annotations.banner).toBeHidden();
    await thread.scrollToBottom();
    await expect(annotations.countLabel(thread.item(commentId))).toHaveText('1 annotation');
  });

  await test.step('author marks it processed, and it sticks', async () => {
    const chapter = new ChapterPage(author, STORY.slug, CHAPTER.slug);
    const thread = new CommentThread(author);
    const annotations = new ChapterAnnotations(author);
    await chapter.goto();
    await thread.scrollToBottom();

    await annotations.openPublished(thread.item(commentId));
    const row = annotations.serverRows.filter({ hasText: KEPT });
    await expect(annotations.serverRows).toHaveCount(1);
    await expect(annotations.processedMarker(row)).toBeHidden();

    await annotations.markProcessedButton(row).click();
    await expect(annotations.processedMarker(row)).toBeVisible();
    await expect(annotations.markUnprocessedButton(row)).toBeVisible();

    await author.reload();
    await thread.scrollToBottom();
    await annotations.openPublished(thread.item(commentId));
    await expect(annotations.processedMarker(annotations.serverRows.first())).toBeVisible();
  });

  await test.step('moderator deletes it; the count button goes', async () => {
    const chapter = new ChapterPage(moderator, STORY.slug, CHAPTER.slug);
    const thread = new CommentThread(moderator);
    const annotations = new ChapterAnnotations(moderator);
    await chapter.goto();
    await thread.scrollToBottom();

    await annotations.openPublished(thread.item(commentId));
    const row = annotations.serverRows.filter({ hasText: KEPT });
    await annotations.deleteButton(row).click();
    await expect(annotations.serverRows).toHaveCount(0);

    await moderator.keyboard.press('Escape');
    await expect(annotations.countButton(thread.item(commentId))).toBeHidden();

    await moderator.reload();
    await thread.scrollToBottom();
    await expect(thread.item(commentId)).toBeVisible();
    await expect(annotations.countButton(thread.item(commentId))).toHaveCount(0);
  });
});
