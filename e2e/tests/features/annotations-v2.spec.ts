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
 * has no root comment in the seeded world: the round trip posts it, and every
 * later test on that chapter builds on what the previous one left (serial).
 * `chapitre-simple-3` and `chapitre-avance-4` are only read: their selections
 * stay in the browser.
 *
 * Set `E2E_SHOTS_DIR` to collect VERIFY evidence screenshots.
 */

const CHAPTER = STORY.illustratedChapter;

const ROOT_BODY =
  'Un commentaire e2e assez long pour franchir le seuil des cent quarante caractères ' +
  'exigés pour un commentaire racine, avant les réactions et annotations.';
const EDITED = 'Un cœur, et une phrase pour dire pourquoi.';
const AUTHOR_REPLY = "Merci, je garde l'italique.";
const READER_REPLY = 'Avec plaisir, et le gras aussi.';
const AUTHOR_REPLY_2 = 'Le pouce, je le prends.';
const THUMB_EDITED = 'Le pouce, avec une explication.';
const STALE_EDIT = 'Une modification qui arrivera trop tard.';
const KEYBOARD_REPLY = 'Réponse tapée au clavier.';

/** "Epsilon un." — the start of the chapter's second text block. */
const SHORT = 11;

test.describe.configure({ mode: 'serial' });

/** `confirmed`'s root comment on the chapter, posted by the round trip. */
let rootId = 0;

function onChapter(page: Page) {
  return {
    chapter: new ChapterPage(page, STORY.slug, CHAPTER.slug),
    thread: new CommentThread(page),
    annotations: new ChapterAnnotations(page),
  };
}

/** Open the pop-up of `confirmed`'s root comment from a fresh load of the chapter. */
async function openRootPopup(page: Page) {
  const ctx = onChapter(page);
  await ctx.chapter.goto();
  await loadThread(ctx.thread);
  await ctx.annotations.openPublished(ctx.thread.item(rootId));
  return ctx;
}

/** Scroll down step by step until the lazy fragments landed (see `CommentThread.scrollDown`). */
async function loadThread(thread: CommentThread): Promise<void> {
  await expect(async () => {
    await thread.scrollDown();
    await expect(thread.items.first()).toBeVisible({ timeout: 500 });
  }).toPass({ timeout: 15000 });
}

test('reader reacts and edits; author replies; reader replies and deletes', async ({ confirmed, author }) => {
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
    await annotations.evidence('author-reply-hint', annotations.serverDialog);
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

test('without a root comment: « Citer », « Annoter » and the emoji; ❤️ is a draft in the v1 banner', async ({
  confirmed,
}) => {
  const chapter = new ChapterPage(confirmed, STORY.slug, STORY.simpleChapter.slug);
  const annotations = new ChapterAnnotations(confirmed);
  await chapter.goto();

  await chapter.selectText(chapter.textBlocks.first().locator('p').first());
  await expect(chapter.citeButton).toBeVisible();
  await expect(annotations.annotateButton).toBeVisible();
  for (const reaction of ['heart', 'fire', 'thumbs_up'] as const) {
    await expect(annotations.reactionButton(reaction)).toBeVisible();
  }
  await annotations.evidence('toolbar-no-root', annotations.toolbar);

  await annotations.react('heart');
  await expect(annotations.banner).toContainText('1 annotation, écrivez votre commentaire pour la sauvegarder');
  await expect(annotations.saveBanner).toBeHidden();
  await annotations.openDrafts();
  await expect(annotations.draftRows).toHaveCount(1);
  await expect(annotations.draftRows.first()).toContainText('❤️');
});

async function selectAcrossBlocks(chapter: ChapterPage): Promise<void> {
  await chapter.selectAcross(
    chapter.textBlocks.nth(0).locator('p').first(),
    chapter.textBlocks.nth(1).locator('p').first(),
  );
}

test('a cross-block selection hides « Annoter » and the emoji alike, « Citer » stays', async ({ confirmed }) => {
  const chapter = new ChapterPage(confirmed, STORY.slug, STORY.advancedChapter.slug);
  const annotations = new ChapterAnnotations(confirmed);
  await chapter.goto();

  // Within one block first, so the hidden state below is not the toolbar never showing up.
  await chapter.selectText(chapter.textBlocks.nth(0).locator('p').first());
  await expect(annotations.reactionButton('heart')).toBeVisible();

  await selectAcrossBlocks(chapter);
  await expect(chapter.citeButton).toBeVisible();
  await expect(annotations.annotateButton).toBeHidden();
  for (const reaction of ['heart', 'fire', 'thumbs_up'] as const) {
    await expect(annotations.reactionButton(reaction)).toBeHidden();
  }
  await annotations.evidence('cross-block-toolbar');
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

test('save banner: plural, survives a reload, « Tout annuler » asks then clears', async ({ confirmed }) => {
  const { chapter, annotations } = onChapter(confirmed);
  await chapter.goto();

  await chapter.selectText(chapter.textBlocks.nth(0).locator('p'));
  await annotations.react('fire');
  await chapter.selectText(chapter.textBlocks.nth(1).locator('p'));
  await annotations.react('thumbs_up');
  await expect(annotations.saveBanner).toContainText('Vous avez 2 annotations non sauvegardées');
  await expect(annotations.saveBanner).toHaveAttribute('role', 'status');
  await expect(annotations.saveBanner).toHaveAttribute('aria-live', 'polite');
  await annotations.evidence('save-banner-plural');

  await confirmed.reload();
  await expect(annotations.saveBanner).toContainText('Vous avez 2 annotations non sauvegardées');

  const asked = await annotations.discardChanges();
  expect(asked).toBe("Abandonner toutes vos modifications d'annotations non sauvegardées ?");
  await confirmed.reload();
  await expect(annotations.saveBanner).toBeHidden();
});

test('save banner: « Enregistrer » updates « N annotations » on the comment', async ({ confirmed }) => {
  const { chapter, thread, annotations } = onChapter(confirmed);
  await chapter.goto();
  await thread.scrollToBottom();
  await expect(annotations.countLabel(thread.item(rootId))).toHaveText('1 annotation');

  await chapter.selectText(chapter.textBlocks.nth(1).locator('p'), SHORT);
  await annotations.react('thumbs_up');
  await annotations.saveChanges();

  await expect(annotations.countLabel(thread.item(rootId))).toHaveText('2 annotations');
  await annotations.evidence('save-success-count', thread.item(rootId));
});

test('author sees every row and reply, « Répondre » on each, the processed toggle; the commenter gets « Répondre » after', async ({
  confirmed,
  author,
}) => {
  await test.step('commenter: « Répondre » only under the row an author answered', async () => {
    const { annotations } = await openRootPopup(confirmed);
    const heart = annotations.serverRows.filter({ hasText: EDITED });
    const thumb = annotations.serverRows.filter({ hasText: '👍' });
    await expect(annotations.replyButton(heart)).toBeVisible();
    await expect(annotations.replyButton(thumb)).toHaveCount(0);
    await annotations.evidence('popup-commenter-before-reply', annotations.serverDialog);
  });

  await test.step('author: rows, replies, « Répondre » on every root, marks 👍 processed and replies', async () => {
    const { annotations } = await openRootPopup(author);
    await expect(annotations.serverRows).toHaveCount(2);
    const heart = annotations.serverRows.filter({ hasText: EDITED });
    const thumb = annotations.serverRows.filter({ hasText: '👍' });
    await expect(annotations.replies(heart)).toContainText(AUTHOR_REPLY);
    await expect(annotations.replyButton(heart)).toBeVisible();
    await expect(annotations.replyButton(thumb)).toBeVisible();

    await annotations.markProcessedButton(thumb).click();
    await expect(annotations.processedMarker(thumb)).toBeVisible();
    await annotations.reply(thumb, AUTHOR_REPLY_2);
    await expect(annotations.replyHint(thumb)).toBeVisible();
    await annotations.evidence('popup-author', annotations.serverDialog);
  });

  await test.step('commenter: « Répondre » now under 👍 too, and no processed marker', async () => {
    const { annotations } = await openRootPopup(confirmed);
    const thumb = annotations.serverRows.filter({ hasText: '👍' });
    await expect(annotations.replies(thumb)).toContainText(AUTHOR_REPLY_2);
    await expect(annotations.replyButton(thumb)).toBeVisible();
    await expect(annotations.processedMarker(thumb)).toBeHidden();
    await annotations.evidence('popup-commenter-after-reply', annotations.serverDialog);
  });
});

test('moderator sees the replies, deletes one reply only, never gets « Répondre »', async ({ moderator }) => {
  const { chapter, thread, annotations } = await openRootPopup(moderator);
  const heart = annotations.serverRows.filter({ hasText: EDITED });
  const thumb = annotations.serverRows.filter({ hasText: '👍' });
  await expect(annotations.replies(heart)).toContainText(AUTHOR_REPLY);
  await expect(annotations.replies(thumb)).toContainText(AUTHOR_REPLY_2);
  await expect(annotations.serverDialog.getByRole('button', { name: 'Répondre', exact: true })).toHaveCount(0);
  // Readable, like « Supprimer l'annotation » next to it (WCAG AA for normal text).
  expect(await chapter.contrast(annotations.deleteReplyButton(annotations.replies(thumb)))).toBeGreaterThanOrEqual(4.5);
  await annotations.evidence('popup-moderator', annotations.serverDialog);

  await annotations.deleteReply(annotations.replies(thumb));
  await expect(annotations.replies(thumb)).toHaveCount(0);
  await expect(annotations.serverRows).toHaveCount(2);
  await expect(annotations.replies(heart)).toHaveCount(1);

  await moderator.reload();
  await thread.scrollToBottom();
  await annotations.openPublished(thread.item(rootId));
  await expect(annotations.replies(annotations.serverRows.filter({ hasText: '👍' }))).toHaveCount(0);
  await expect(annotations.replies(annotations.serverRows.filter({ hasText: EDITED }))).toHaveCount(1);
});

test('mobile (375 px): banner and pop-up', async ({ browser }) => {
  const page = await mobilePage(browser, 'confirmed');
  const { chapter, thread, annotations } = onChapter(page);
  await chapter.goto();

  await chapter.touchSelectText(chapter.textBlocks.nth(1).locator('p'));
  await expect(annotations.reactionButton('heart')).toBeVisible();
  await annotations.evidence('mobile-toolbar');
  await annotations.react('heart', { tap: true });
  await expect(annotations.saveBanner).toBeVisible();

  // A reply form on another reader's root comment, its submit centred on screen:
  // the sticky banner must not sit on top of it.
  await loadThread(thread);
  const other = thread.items.filter({ hasText: 'Commentaire de promotion E2E' }).first();
  const otherId = await thread.idOf(other);
  await thread.openReply(otherId);
  const submit = thread.replySubmit(otherId);
  await submit.evaluate((el) => el.scrollIntoView({ block: 'center' }));
  await annotations.evidence('mobile-banner-reply-form');
  const box = (await submit.boundingBox())!;
  const bannerBox = (await annotations.saveBanner.boundingBox())!;
  expect(box.y + box.height <= bannerBox.y || box.y >= bannerBox.y + bannerBox.height).toBe(true);

  await annotations.openFromSaveBanner();
  await expect(annotations.pendingRow('added')).toHaveCount(1);
  await annotations.evidence('mobile-popup-commenter');
  await page.keyboard.press('Escape');
  await annotations.discardChanges();
  await page.context().close();

  const authorPage = await mobilePage(browser, 'author');
  const authorSide = await openRootPopup(authorPage);
  await expect(authorSide.annotations.serverRows).toHaveCount(2);
  await authorSide.annotations.evidence('mobile-popup-author');
  await authorPage.context().close();
});

test('commenter pop-up: pending edit, delete and add, per-row undo; deleting with replies asks first', async ({
  confirmed,
}) => {
  const { chapter, thread, annotations } = await openRootPopup(confirmed);
  const heart = () => annotations.serverRows.filter({ hasText: AUTHOR_REPLY });

  await test.step('« Modifier » reopens the form on the passage and the body', async () => {
    await annotations.editRow(annotations.serverRows.filter({ hasText: '👍' }));
    await expect(annotations.captureHighlight).toHaveText('Epsilon un.');
    expect(await annotations.captureEditor.text()).toBe('👍');
    await annotations.evidence('popup-commenter-edit-form');
    await annotations.captureEditor.fill(THUMB_EDITED);
    await annotations.captureSave.click();
    await expect(annotations.pendingRow('edited')).toContainText(THUMB_EDITED);
  });

  await test.step('deleting a row with replies asks; refusing keeps it', async () => {
    const asked = await annotations.deleteRowPending(heart(), { accept: false });
    expect(asked).toBe('Les réponses seront aussi supprimées.');
    await expect(annotations.pendingRow('deleted')).toHaveCount(0);

    await annotations.deleteRowPending(heart());
    await expect(annotations.pendingRow('deleted')).toHaveCount(1);
  });

  await test.step('a pending add shows in the pop-up', async () => {
    await confirmed.keyboard.press('Escape');
    await chapter.selectText(chapter.textBlocks.nth(0).locator('p'), 9);
    await annotations.react('fire');
    await annotations.openPublished(thread.item(rootId));
    await expect(annotations.pendingRow('added')).toContainText('🔥');
    await expect(annotations.saveBanner).toContainText('Vous avez 3 annotations non sauvegardées');
    await annotations.evidence('popup-commenter-pending');
  });

  await test.step('per-row undo drops the add and the delete, keeps the edit', async () => {
    await annotations.undoButton(annotations.pendingRow('added')).click();
    await expect(annotations.pendingRow('added')).toHaveCount(0);
    await annotations.undoButton(annotations.pendingRow('deleted')).click();
    await expect(annotations.pendingRow('deleted')).toHaveCount(0);
    await expect(annotations.undoButton(annotations.pendingRow('edited'))).toHaveText(/Annuler la modification/);
    await expect(annotations.saveBanner).toContainText('Vous avez 1 annotation non sauvegardée');
  });

  await test.step('delete again and save: the row and its replies are gone', async () => {
    await annotations.deleteRowPending(heart());
    await confirmed.keyboard.press('Escape');
    await annotations.saveChanges();
    await expect(annotations.countLabel(thread.item(rootId))).toHaveText('1 annotation');

    await annotations.openPublished(thread.item(rootId));
    await expect(annotations.serverRows).toHaveCount(1);
    await expect(annotations.serverRows.first()).toContainText(THUMB_EDITED);
    await expect(annotations.serverDialog).not.toContainText(AUTHOR_REPLY);
    await expect(annotations.serverDialog.getByTestId('annotation-reply')).toHaveCount(0);
  });
});

test('author: the commenter’s edit reset « Traitée »', async ({ author }) => {
  const { annotations } = await openRootPopup(author);
  const row = annotations.serverRows.filter({ hasText: THUMB_EDITED });
  await expect(row).toHaveCount(1);
  await expect(annotations.processedMarker(row)).toBeHidden();
  await expect(annotations.markProcessedButton(row)).toBeVisible();
});

test('stale item: a moderator deletes the row being edited; the save names it, « Retirer », save again', async ({
  confirmed,
  moderator,
}) => {
  const { chapter, thread, annotations } = await openRootPopup(confirmed);

  await annotations.editRow(annotations.serverRows.filter({ hasText: THUMB_EDITED }));
  await annotations.captureEditor.fill(STALE_EDIT);
  await annotations.captureSave.click();
  await confirmed.keyboard.press('Escape');
  await chapter.selectText(chapter.textBlocks.nth(0).locator('p'), 9);
  await annotations.react('heart');
  await expect(annotations.saveBanner).toContainText('Vous avez 2 annotations non sauvegardées');

  const mod = await openRootPopup(moderator);
  await mod.annotations.deleteButton(mod.annotations.serverRows.filter({ hasText: THUMB_EDITED })).click();
  await expect(mod.annotations.serverRows).toHaveCount(0);

  await annotations.attemptSave();
  await expect(annotations.saveBannerError).toContainText("Rien n'a été enregistré");
  await expect(annotations.saveBannerError).toContainText(
    "Modification d'une annotation : Cette annotation n'existe plus. Retirez-la de vos modifications.",
  );
  await annotations.evidence('stale-banner-error', annotations.saveBanner);

  await annotations.openFromSaveBanner();
  const stale = annotations.pendingRow('edited');
  await expect(annotations.rowError(stale)).toContainText("Cette annotation n'existe plus");
  await annotations.evidence('stale-row-flagged', annotations.serverDialog);

  // After a reload the server list no longer has the row: the stale edit still
  // gets one of its own, flagged before any save, with « Retirer ».
  await confirmed.keyboard.press('Escape');
  await confirmed.reload();
  await loadThread(thread);
  await annotations.openFromSaveBanner();
  await expect(annotations.rowError(stale)).toContainText("Cette annotation n'existe plus");
  await annotations.evidence('stale-row-after-reload', annotations.serverDialog);
  await annotations.removeStaleButton(stale).click();
  await expect(annotations.pendingRow('edited')).toHaveCount(0);
  await expect(annotations.pendingRow('added')).toHaveCount(1);
  await expect(annotations.saveBannerError).toBeHidden();
  await expect(annotations.saveBanner).toContainText('Vous avez 1 annotation non sauvegardée');

  await confirmed.keyboard.press('Escape');
  await annotations.saveChanges();
  await expect(annotations.countLabel(thread.item(rootId))).toHaveText('1 annotation');
});

test('emptied root comment: the reader still reacts and saves', async ({ confirmed, moderator }) => {
  const mod = onChapter(moderator);
  await mod.chapter.goto();
  await mod.thread.scrollToBottom();
  await mod.thread.emptyContent(rootId);

  const { chapter, thread, annotations } = onChapter(confirmed);
  await chapter.goto();
  await thread.scrollToBottom();
  await expect(annotations.countButton(thread.item(rootId))).toBeHidden();

  await chapter.selectText(chapter.textBlocks.nth(1).locator('p'), SHORT);
  await annotations.react('thumbs_up');
  await expect(annotations.saveBanner).toContainText('Vous avez 1 annotation non sauvegardée');
  await annotations.saveChanges();
  await expect(annotations.countLabel(thread.item(rootId))).toHaveText('1 annotation');
  await annotations.evidence('emptied-root-saved', thread.item(rootId));
});

test('keyboard: « Répondre » and « Envoyer » work without a mouse', async ({ author }) => {
  const { annotations } = await openRootPopup(author);
  const row = annotations.serverRows.first();

  await annotations.replyButton(row).focus();
  await author.keyboard.press('Enter');
  await expect(annotations.replyEditor.body).toBeVisible();
  await annotations.replyEditor.body.focus();
  await author.keyboard.type(KEYBOARD_REPLY);
  await annotations.sendReplyButton(row).focus();
  await author.keyboard.press('Enter');
  await expect(annotations.replies(row)).toContainText(KEYBOARD_REPLY);
});

async function mobilePage(browser: Browser, role: RoleName): Promise<Page> {
  const context = await browser.newContext({
    ...devices['Pixel 7'],
    viewport: { width: 375, height: 740 },
    storageState: path.join(ROOT, storageStatePath(role)),
  });
  return context.newPage();
}
