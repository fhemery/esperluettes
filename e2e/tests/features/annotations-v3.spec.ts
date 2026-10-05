import type { Page } from '@playwright/test';
import { AnnotationOverlay } from '../../pages/AnnotationOverlay';
import { ChapterAnnotations } from '../../pages/ChapterAnnotations';
import { ChapterPage } from '../../pages/ChapterPage';
import { CommentThread } from '../../pages/CommentThread';
import { ACCOUNTS, ANNOTATED } from '../../support/fixtures';
import { expect, test } from '../../support/test';

/**
 * FEATURE (annotations-v3) — in-chapter annotations: tint, gutter avatars,
 * popover, the author's filter menu, coexistence with Quote.
 *
 * Everything here runs only once Alpine and the Highlight API have run, which
 * no PHP test sees. Payload rules (who gets which list, processed flag never
 * sent to the commenter, deactivated writers filtered) are the BUILD feature
 * tests' job; this spec checks what the browser makes of them.
 *
 * World: story 6 (`ANNOTATED`). Tests that write go last, on rows nothing
 * before them reads.
 */

const C = ANNOTATED.confirmed;
const U = ANNOTATED.user;

/** What the author sees tinted on chapter 11 by default (processed, missing and deactivated left out). */
const AUTHOR_TINT = [C.fox, C.morning, C.movedTo, C.emptied, C.underQuote, U.clearing, U.mill, ANNOTATED.anonymised];

/** What `confirmed` sees: their own, the processed one included. */
const CONFIRMED_TINT = [C.fox, C.morning, C.processed, C.movedTo, C.emptied, C.underQuote];

function on(page: Page, chapterSlug: string = ANNOTATED.chapter.slug) {
  return {
    chapter: new ChapterPage(page, ANNOTATED.storySlug, chapterSlug),
    overlay: new AnnotationOverlay(page),
    annotations: new ChapterAnnotations(page),
    thread: new CommentThread(page),
  };
}

async function visit(page: Page, chapterSlug: string = ANNOTATED.chapter.slug) {
  const parts = on(page, chapterSlug);
  await parts.overlay.rewriteAssetHost();
  await parts.chapter.goto();
  return parts;
}

/** The overlay has nothing to show: no tint, no gutter avatar, no filter menu. */
async function expectNoOverlay(overlay: AnnotationOverlay): Promise<void> {
  await expect(overlay.avatars).toHaveCount(0);
  expect(await overlay.highlighted()).toEqual([]);
  expect(await overlay.highlighted('annotation-processed')).toEqual([]);
  await expect(overlay.filterMenu).toBeHidden();
}

test.describe('no in-chapter display', () => {
  test('guest: no tint, no avatar, no filter menu, no in-entity request', async ({ guest }) => {
    const { overlay, chapter } = await visit(guest);
    await expect(chapter.content).toContainText('Le renard roux');
    await guest.waitForLoadState('networkidle');
    await expectNoOverlay(overlay);
    await expect(overlay.root).toHaveCount(0);
    expect(overlay.inEntityRequests).toBe(0);
  });

  /** A reader with no root on the chapter: nothing of the overlay; the selection toolbar as before. */
  async function readerWithoutAnnotation(page: Page, canQuote: boolean): Promise<void> {
    const { overlay, chapter } = await visit(page, ANNOTATED.emptyChapter.slug);
    await page.waitForLoadState('networkidle');
    await expectNoOverlay(overlay);
    expect(overlay.inEntityRequests).toBe(0);

    await chapter.selectPassage('Iota deux.');
    await expect(new ChapterAnnotations(page).annotateButton).toBeVisible();
    await expect(chapter.quoteButton).toHaveCount(canQuote ? 1 : 0);
  }

  test('confirmed without annotation on the chapter: nothing shown, quoting as before', async ({ confirmed }) => {
    await readerWithoutAnnotation(confirmed, true);
  });

  test('non-confirmed without annotation on the chapter: nothing shown', async ({ user }) => {
    await readerWithoutAnnotation(user, false);
  });

  test('beta reader (moderator) without annotation on the chapter: nothing shown, quoting as before', async ({ moderator }) => {
    await readerWithoutAnnotation(moderator, true);
  });

  test('moderator (beta reader, not author): nothing in the chapter; pop-up has « Vider le contenu » on roots only', async ({ moderator }) => {
    const { overlay, annotations, thread } = await visit(moderator);
    await moderator.waitForLoadState('networkidle');
    await expectNoOverlay(overlay);
    expect(overlay.inEntityRequests).toBe(0);

    await thread.scrollToBottom();
    await annotations.openPublished(thread.item(ANNOTATED.roots.confirmed));
    const fox = annotations.serverRows.filter({ hasText: ANNOTATED.bodies.fox });
    await expect(annotations.deleteButton(fox)).toBeVisible();
    await expect(fox.getByRole('button', { name: 'Vider le contenu', exact: true })).toBeVisible();
    const reply = annotations.replies(fox).first();
    await expect(reply).toContainText(ANNOTATED.bodies.authorReply);
    await expect(reply.getByRole('button', { name: 'Vider le contenu' })).toHaveCount(0);
    await expect(annotations.deleteReplyButton(reply)).toBeVisible();
  });
});

test.describe('author', () => {
  test('happy path: every visible annotation tinted, « +N », popover « 1 / N » with arrows', async ({ author }) => {
    const { overlay } = await visit(author);
    await overlay.expectHighlighted(AUTHOR_TINT);
    expect(overlay.inEntityRequests).toBe(1);
    await expect(overlay.filterMenu).toBeVisible();

    const line = await overlay.avatarFor(C.fox);
    await expect(line).toHaveAttribute('aria-label', 'Annotation de E2E Confirmed et 2 autres');
    await expect(overlay.more(line)).toHaveText('+2');

    await line.click();
    await expect(overlay.popover).toBeVisible();
    await expect(overlay.popoverPosition).toHaveText('1 / 3');
    await expect(overlay.popoverAuthor).toHaveText(ACCOUNTS.confirmed.displayName);
    await expect(overlay.popoverBody).toHaveText(ANNOTATED.bodies.fox);
    await expect(overlay.popoverButton('Répondre')).toBeVisible();
    await expect(overlay.popoverButton('Marquer comme traitée')).toBeVisible();
    await overlay.shot('author-popover-1-of-3');

    // Reading order: Le renard roux, traverse la clairière, au matin.
    await overlay.nextButton.click();
    await expect(overlay.popoverPosition).toHaveText('2 / 3');
    await expect(overlay.popoverBody).toHaveText(ANNOTATED.bodies.clearing);
    await overlay.nextButton.click();
    await expect(overlay.popoverPosition).toHaveText('3 / 3');
    await expect(overlay.popoverBody).toHaveText(ANNOTATED.bodies.morning);
    await overlay.previousButton.click();
    await expect(overlay.popoverPosition).toHaveText('2 / 3');
  });

  for (const width of [768, 1024, 1280]) {
    test(`avatars sit level with their passage at ${width}px, on load`, async ({ author }) => {
      await author.setViewportSize({ width, height: 900 });
      const { overlay, chapter } = await visit(author);
      await overlay.expectHighlighted(AUTHOR_TINT);
      await expect(overlay.avatars).not.toHaveCount(0);
      await chapter.waitForImages();
      await overlay.shot(`author-${width}-gutter`);
      await overlay.expectAvatarsAligned();
    });
  }

  test('processed: hidden at once, popover moves on / closes; shown dimmed; « non traitée » restores', async ({ author }) => {
    const { overlay } = await visit(author);
    await overlay.expectHighlighted(AUTHOR_TINT);

    await overlay.open(C.fox);
    await expect(overlay.popoverPosition).toHaveText('1 / 3');
    await overlay.popoverButton('Marquer comme traitée').click();
    await overlay.expectHighlighted(AUTHOR_TINT.filter((t) => t !== C.fox));
    await expect(overlay.popover).toBeVisible();
    await expect(overlay.popoverPosition).toHaveText('1 / 2');
    await expect(overlay.popoverBody).toHaveText(ANNOTATED.bodies.clearing);
    await expect(overlay.more(await overlay.avatarFor(C.morning))).toHaveText('+1');

    // A lone annotation: marking it closes the popover.
    await overlay.open(C.underQuote);
    await overlay.popoverButton('Marquer comme traitée').click();
    await expect(overlay.popover).toBeHidden();
    await overlay.expectHighlighted(AUTHOR_TINT.filter((t) => t !== C.fox && t !== C.underQuote));

    await overlay.toggleShowProcessed();
    await overlay.expectHighlighted([C.fox, C.underQuote, C.processed], 'annotation-processed');
    await overlay.expectHighlighted(AUTHOR_TINT.filter((t) => t !== C.fox && t !== C.underQuote));
    await overlay.filterButton.click();
    const lone = await overlay.avatarFor(C.underQuote);
    await expect(overlay.avatarImage(lone)).toHaveClass(/opacity-50/);
    await expect(overlay.avatarImage(await overlay.avatarFor(C.fox))).toHaveClass(/opacity-50/);
    await overlay.shot('author-processed-shown-dimmed');

    await lone.click();
    await overlay.popoverButton('Marquer comme non traitée').click();
    await expect(overlay.avatarImage(await overlay.avatarFor(C.underQuote))).not.toHaveClass(/opacity-50/);
    await overlay.open(C.fox);
    await overlay.popoverButton('Marquer comme non traitée').click();
    await overlay.expectHighlighted([C.processed], 'annotation-processed');
    await overlay.expectHighlighted(AUTHOR_TINT);
  });

  test('filter: untick a commenter → tint and avatars gone, groups recompute; pop-up not filtered; reload resets', async ({ author }) => {
    const { overlay, annotations, thread } = await visit(author);
    await overlay.expectHighlighted(AUTHOR_TINT);

    await overlay.openFilter();
    await expect(overlay.filterCommenterNames).toHaveText([
      ACCOUNTS.confirmed.displayName, ACCOUNTS.user.displayName, ANNOTATED.anonymisedName,
    ]);
    await overlay.toggleCommenter(ACCOUNTS.user.displayName);
    await overlay.expectHighlighted(AUTHOR_TINT.filter((t) => t !== U.clearing && t !== U.mill));
    await overlay.filterButton.click();
    await expect(overlay.more(await overlay.avatarFor(C.fox))).toHaveText('+1');
    await expect(overlay.more(await overlay.avatarFor(ANNOTATED.anonymised))).toBeHidden();
    await overlay.shot('author-filter-user-unticked');

    await thread.scrollToBottom();
    await annotations.openPublished(thread.item(ANNOTATED.roots.user));
    await expect(annotations.serverRows).toHaveCount(2);
    await author.keyboard.press('Escape');

    await author.reload();
    await overlay.expectHighlighted(AUTHOR_TINT);
  });

  test('heat map: on hides tint, avatars, filter menu and popover; off restores the same filter state', async ({ author }) => {
    const { overlay, chapter } = await visit(author);
    await overlay.expectHighlighted(AUTHOR_TINT);
    await overlay.toggleCommenter(ACCOUNTS.user.displayName);
    await overlay.filterButton.click();
    const filtered = AUTHOR_TINT.filter((t) => t !== U.clearing && t !== U.mill);
    await overlay.expectHighlighted(filtered);
    await overlay.open(C.fox);

    await chapter.heatToggle.click();
    await expect(chapter.heatMarks.first()).toBeVisible();
    await overlay.expectHighlighted([]);
    await expect(overlay.popover).toBeHidden();
    await expect(overlay.filterMenu).toBeHidden();
    await expect(overlay.avatars.first()).toBeHidden();
    await overlay.shot('author-heat-on');

    await chapter.heatToggle.click();
    await overlay.expectHighlighted(filtered);
    await expect(overlay.filterMenu).toBeVisible();
    await expect(overlay.avatars.first()).toBeVisible();
  });

  test('empty chapter: no filter menu, no avatar', async ({ author }) => {
    const { overlay, chapter } = await visit(author, ANNOTATED.emptyChapter.slug);
    await expect(chapter.content).toContainText('Iota un.');
    await author.waitForLoadState('networkidle');
    await expectNoOverlay(overlay);
  });

  test('stale data: missing passage untinted and badged in the pop-up; moved passage tinted, no badge', async ({ author }) => {
    const { overlay, annotations, thread } = await visit(author);
    await overlay.expectHighlighted(AUTHOR_TINT);
    expect(await overlay.highlighted()).not.toContain(C.missing);

    await thread.scrollToBottom();
    await annotations.openPublished(thread.item(ANNOTATED.roots.confirmed));
    const missing = annotations.serverRows.filter({ hasText: C.missing });
    await expect(missing.getByTestId('annotation-passage-missing')).toBeVisible();
    const moved = annotations.serverRows.filter({ hasText: 'La boulangère a changé.' });
    await expect(moved.getByTestId('annotation-passage-missing')).toBeHidden();
  });

  test('deleted commenter: default avatar and anonymised name in gutter, popover and filter', async ({ author }) => {
    const { overlay } = await visit(author);
    await overlay.expectHighlighted(AUTHOR_TINT);
    const avatar = await overlay.avatarFor(ANNOTATED.anonymised);
    await expect(overlay.avatarImage(avatar)).toHaveAttribute('src', /default-avatar\.svg$/);
    await overlay.openFilter();
    await expect(overlay.filterCommenterNames.filter({ hasText: ANNOTATED.anonymisedName })).toHaveCount(1);
    await overlay.filterButton.click();

    await avatar.click();
    await expect(overlay.popover).toBeVisible();
    await overlay.shot('author-anonymised-popover');
    await expect.soft(overlay.popoverAuthor, 'popover names the deleted commenter').toHaveText(ANNOTATED.anonymisedName);
    await expect.soft(avatar, 'gutter label names the deleted commenter')
      .toHaveAttribute('aria-label', `Annotation de ${ANNOTATED.anonymisedName} et 1 autre`);
  });

  test('deactivated commenter and reply writer: nothing of theirs shows', async ({ author }) => {
    const { overlay } = await visit(author);
    await overlay.expectHighlighted(AUTHOR_TINT);
    expect(await overlay.highlighted()).not.toContain(ANNOTATED.deactivated);
    await overlay.open(C.fox);
    await expect(overlay.popoverReplies).toHaveCount(1);
    await expect(overlay.popoverReplies).toContainText(ANNOTATED.bodies.authorReply);
  });

  test('co-author sees the author overlay', async ({ tech_admin }) => {
    const { overlay } = await visit(tech_admin);
    await overlay.expectHighlighted(AUTHOR_TINT);
    await expect(overlay.filterMenu).toBeVisible();
    await overlay.open(C.fox);
    await expect(overlay.popoverPosition).toHaveText('1 / 3');
    await expect(overlay.popoverButton('Marquer comme traitée')).toBeVisible();
  });
});

test.describe('commenter', () => {
  test('quote overlap: yellow mark and underline on the same words; the mark opens the quote', async ({ confirmed }) => {
    const { overlay, chapter } = await visit(confirmed);
    await overlay.expectHighlighted(CONFIRMED_TINT);
    const mark = chapter.quoteHighlights.filter({ hasText: ANNOTATED.quote });
    await expect(mark).toBeVisible();
    await overlay.shot('confirmed-quote-and-annotation');
    await mark.click();
    await expect(chapter.quotePanel).toBeVisible();
  });

  test('privacy: own annotations only, no processed hint', async ({ confirmed }) => {
    const { overlay } = await visit(confirmed);
    await overlay.expectHighlighted(CONFIRMED_TINT);
    expect(await overlay.highlighted('annotation-processed')).toEqual([]);
    await expect(overlay.filterMenu).toHaveCount(0);

    const processed = await overlay.avatarFor(C.processed);
    await expect(overlay.avatarImage(processed)).not.toHaveClass(/opacity-50/);
    await processed.click();
    await expect(overlay.popover).toBeVisible();
    await expect(overlay.popover).not.toContainText(/traitée/i);
    await expect(overlay.popoverButton('Modifier')).toBeVisible();

    // The author's reply shows; the deactivated writer's does not.
    await overlay.open(C.fox);
    await expect(overlay.popoverReplies).toHaveCount(1);
  });

  test('emptied annotation: placeholder, « Supprimer » only (popover and pop-up)', async ({ confirmed }) => {
    const { overlay, annotations, thread } = await visit(confirmed);
    await overlay.expectHighlighted(CONFIRMED_TINT);
    await overlay.open(C.emptied);
    await expect(overlay.popoverBody).toHaveText('<Annotation supprimée>');
    await expect(overlay.popoverButton('Supprimer')).toBeVisible();
    await expect(overlay.popoverButton('Modifier')).toHaveCount(0);
    await overlay.closeButton.click();

    await thread.scrollToBottom();
    await annotations.openPublished(thread.item(ANNOTATED.roots.confirmed));
    const row = annotations.serverRows.filter({ hasText: '<Annotation supprimée>' });
    await expect(annotations.deleteButton(row)).toBeVisible();
    await expect(row.getByRole('button', { name: 'Modifier', exact: true })).toHaveCount(0);
  });

  test('draft mode: new draft tinted with its avatar at once; « Modifier » opens the form; « Supprimer » removes it', async ({ confirmed }) => {
    const { overlay, chapter, annotations } = await visit(confirmed, ANNOTATED.emptyChapter.slug);

    await chapter.selectPassage('Iota un.');
    await annotations.capture('Premier brouillon.');
    await chapter.selectPassage('Une seconde phrase');
    await annotations.capture('Second brouillon.');
    await overlay.expectHighlighted(['Iota un.', 'Une seconde phrase']);
    await expect(overlay.avatars).toHaveCount(2);
    await expect(annotations.banner).toContainText('2 annotations');
    await overlay.shot('confirmed-draft-tinted');

    await overlay.open('Une seconde phrase');
    await expect(overlay.popoverBody).toHaveText('Second brouillon.');
    await expect(overlay.popoverAuthor).toHaveText(ACCOUNTS.confirmed.displayName);
    await overlay.popoverButton('Modifier').click();
    await expect(overlay.popover).toBeHidden();
    await expect(annotations.captureForm).toBeVisible();
    await annotations.captureEditor.waitUntilReady();
    expect(await annotations.captureEditor.text()).toBe('Second brouillon.');
    await annotations.captureEditor.fill('Second brouillon, revu.');
    await annotations.captureSave.click();
    await expect(annotations.captureForm).toBeHidden();
    await overlay.open('Une seconde phrase');
    await expect(overlay.popoverBody).toHaveText('Second brouillon, revu.');

    await overlay.popoverButton('Supprimer').click();
    await overlay.expectHighlighted(['Iota un.']);
    await expect(overlay.avatars).toHaveCount(1);
    await expect(annotations.banner).toContainText('1 annotation');
  });

  test('pending mode: add tinted, edit shown, delete hidden, save banner, undo restores', async ({ user }) => {
    const R = ANNOTATED.reviewed;
    const { overlay, chapter, annotations, thread } = await visit(user, ANNOTATED.reviewedChapter.slug);
    await overlay.expectHighlighted([R.answered, R.unanswered, R.toDelete]);

    await chapter.selectPassage(R.free);
    await annotations.capture('Ajout en attente.');
    await overlay.expectHighlighted([R.answered, R.unanswered, R.toDelete, R.free]);
    await expect(overlay.avatars).toHaveCount(4);
    await expect(annotations.saveBanner).toBeVisible();

    await overlay.open(R.unanswered);
    await overlay.popoverButton('Modifier').click();
    await expect(annotations.captureForm).toBeVisible();
    await annotations.captureEditor.waitUntilReady();
    await annotations.captureEditor.fill('Deuxième remarque, revue.');
    await annotations.captureSave.click();
    await expect(annotations.captureForm).toBeHidden();
    await overlay.open(R.unanswered);
    await expect(overlay.popoverBody).toHaveText('Deuxième remarque, revue.');
    await overlay.closeButton.click();

    await overlay.open(R.toDelete);
    await overlay.popoverButton('Supprimer').click();
    await overlay.expectHighlighted([R.answered, R.unanswered, R.free]);
    await expect(overlay.avatars).toHaveCount(3);
    await expect(annotations.saveBanner).toContainText('3 annotations');
    await overlay.shot('user-pending-changes');

    await thread.scrollToBottom();
    await annotations.openPublished(thread.item(ANNOTATED.roots.reviewedUser));
    await annotations.pendingRow('deleted').getByRole('button', { name: 'Annuler', exact: true }).click();
    await user.keyboard.press('Escape');
    await overlay.expectHighlighted([R.answered, R.unanswered, R.toDelete, R.free]);
    await expect(annotations.saveBanner).toContainText('2 annotations');
  });

  test('reply: « Répondre » only after an author reply; failure keeps the text; reply posts', async ({ user }) => {
    const R = ANNOTATED.reviewed;
    const { overlay } = await visit(user, ANNOTATED.reviewedChapter.slug);
    await overlay.expectHighlighted([R.answered, R.unanswered, R.toDelete]);

    await overlay.open(R.unanswered);
    await expect(overlay.popoverButton('Répondre')).toHaveCount(0);
    await overlay.closeButton.click();

    await overlay.open(R.answered);
    await expect(overlay.popoverReplies).toContainText(R.authorReply);
    await overlay.popoverButton('Répondre').click();
    await overlay.replyEditor.waitUntilReady();
    await overlay.replyEditor.fill('Merci, je vois.');

    await user.route('**/comments/annotations/*/replies', (route) =>
      route.fulfill({ status: 500, contentType: 'application/json', body: '{}' }));
    await overlay.sendReply.click();
    await expect(overlay.popoverError).toBeVisible();
    expect(await overlay.replyEditor.text()).toBe('Merci, je vois.');
    await overlay.shot('user-reply-failed');

    await user.unroute('**/comments/annotations/*/replies');
    await overlay.sendReply.click();
    await expect(overlay.popoverReplies).toHaveCount(2);
    await expect(overlay.popoverReplies.last()).toContainText('Merci, je vois.');
  });
});

test.describe('layout and access', () => {
  /** Below md: the tint, nothing else; the « N annotations » pop-up still opens. */
  async function mobile(page: Page, tint: readonly string[], name: string): Promise<void> {
    await page.setViewportSize({ width: 390, height: 844 });
    const { overlay, annotations, thread } = await visit(page);
    await overlay.expectHighlighted(tint);
    await expect(overlay.avatars).toHaveCount(0);
    await expect(overlay.filterMenu).toBeHidden();
    await overlay.clickPassage(C.fox);
    await expect(overlay.popover).toBeHidden();
    await overlay.shot(`${name}-390-tint-only`);

    await thread.scrollToBottom();
    await annotations.openPublished(thread.item(ANNOTATED.roots.confirmed));
    await expect(annotations.serverRows.first()).toBeVisible();
  }

  test('mobile (390px), author: tint only; tapping it does nothing; pop-up as before', async ({ author }) => {
    await mobile(author, AUTHOR_TINT, 'author');
  });

  test('mobile (390px), commenter: tint only; tapping it does nothing; pop-up as before', async ({ confirmed }) => {
    await mobile(confirmed, CONFIRMED_TINT, 'confirmed');
  });

  test('avatars follow passages after a resize and after the image loads', async ({ confirmed }) => {
    // Hold the image back so the article reflows after the avatars are placed.
    let release: () => void = () => {};
    const held = new Promise<void>((resolve) => { release = resolve; });
    await confirmed.route('**/storage/chapters/e2e/illustration*', async (route) => {
      await held;
      await route.fallback();
    });
    const { overlay, chapter } = await visit(confirmed);
    await overlay.expectHighlighted(CONFIRMED_TINT);
    await overlay.expectAvatarsAligned();
    const before = await overlay.passageTop(C.underQuote);
    release();
    await chapter.waitForImages();
    expect(await overlay.passageTop(C.underQuote), 'the image load moved the text below it').toBeGreaterThan(before + 100);
    await overlay.expectAvatarsAligned();

    await confirmed.setViewportSize({ width: 900, height: 900 });
    await overlay.expectAvatarsAligned();
    await overlay.shot('confirmed-900-after-resize');
  });

  test('keyboard: avatars are labelled tab stops; Enter opens; arrows labelled; Escape closes and returns focus', async ({ author }) => {
    const { overlay } = await visit(author);
    await overlay.expectHighlighted(AUTHOR_TINT);
    const first = await overlay.avatarFor(C.fox);
    await first.focus();
    await author.keyboard.press('Tab');
    await expect(await overlay.avatarFor(C.underQuote)).toBeFocused();
    await author.keyboard.press('Shift+Tab');
    await expect(first).toBeFocused();

    await author.keyboard.press('Enter');
    await expect(overlay.popover).toBeVisible();
    await expect(overlay.nextButton).toBeVisible();
    await expect(overlay.previousButton).toBeVisible();
    await expect(overlay.closeButton).toBeVisible();
    await author.keyboard.press('Escape');
    await expect(overlay.popover).toBeHidden();
    await expect(first).toBeFocused();
  });

  test('dark mode: underline colours follow the theme; popover readable', async ({ author }) => {
    const { overlay, chapter } = await visit(author);
    await overlay.expectHighlighted(AUTHOR_TINT);
    expect(await overlay.underlineColour('annotation')).toBe('rgb(29, 78, 216)');
    await chapter.setTheme('autumn', 'dark');
    expect(await overlay.underlineColour('annotation')).toBe('rgb(147, 197, 253)');
    expect(await overlay.underlineColour('annotation-processed')).toBe('rgba(147, 197, 253, 0.4)');
    await overlay.toggleShowProcessed();
    await overlay.filterButton.click();
    await overlay.open(C.fox);
    await overlay.shot('author-dark-popover');
  });

  test('exclusivity: the quote mark and a Shared popover close the annotation popover, and the avatar closes them', async ({ confirmed }) => {
    const { overlay, chapter } = await visit(confirmed);
    await overlay.expectHighlighted(CONFIRMED_TINT);

    await overlay.open(C.fox);
    await chapter.quoteHighlights.filter({ hasText: ANNOTATED.quote }).click();
    await expect(chapter.quotePanel).toBeVisible();
    await expect(overlay.popover).toBeHidden();
    await confirmed.keyboard.press('Escape');
    await expect(chapter.quotePanel).toBeHidden();

    await overlay.open(C.fox);
    await chapter.openHeaderPopover();
    await expect(overlay.popover).toBeHidden();
    await expect.poll(() => chapter.headerPopoverOpen()).toBe(true);

    await (await overlay.avatarFor(C.fox)).click();
    await expect(overlay.popover).toBeVisible();
    await expect.poll(() => chapter.headerPopoverOpen()).toBe(false);
  });
});

test.describe('moderation (writes last)', () => {
  test('« Vider le contenu »: placeholder in place, reply and anchor kept; author and commenter see it', async ({ moderator, author, confirmed }) => {
    const mod = await visit(moderator);
    await mod.thread.scrollToBottom();
    await mod.annotations.openPublished(mod.thread.item(ANNOTATED.roots.confirmed));
    const fox = mod.annotations.serverRows.filter({ hasText: C.fox });
    moderator.once('dialog', (dialog) => dialog.accept());
    await fox.getByRole('button', { name: 'Vider le contenu', exact: true }).click();
    await expect(fox).toContainText('<Annotation supprimée>');
    await expect(mod.annotations.replies(fox)).toContainText(ANNOTATED.bodies.authorReply);

    const own = await visit(author);
    await own.overlay.expectHighlighted(AUTHOR_TINT);
    await own.overlay.open(C.fox);
    await expect(own.overlay.popoverBody).toHaveText('<Annotation supprimée>');
    await expect(own.overlay.popoverReplies).toContainText(ANNOTATED.bodies.authorReply);

    const reader = await visit(confirmed);
    await reader.overlay.expectHighlighted(CONFIRMED_TINT);
    await reader.overlay.open(C.fox);
    await expect(reader.overlay.popoverBody).toHaveText('<Annotation supprimée>');
    await expect(reader.overlay.popoverButton('Supprimer')).toBeVisible();
    await expect(reader.overlay.popoverButton('Modifier')).toHaveCount(0);
  });
});
