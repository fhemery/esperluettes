import type { Page } from '@playwright/test';
import { AnnotationOverlay } from '../../pages/AnnotationOverlay';
import { ChapterPage } from '../../pages/ChapterPage';
import { ACCOUNTS, ANNOTATED } from '../../support/fixtures';
import { expect, test } from '../../support/test';

/**
 * CORE — in-chapter annotation overlay next to Quote's heat map and quotes.
 *
 * The overlay's tint (CSS Custom Highlight API) and the quote mark share the
 * chapter text and Quote's stores (`quotes`, `quoteAggregate`). Neither shows
 * in a PHP test, and a change to Quote's client code can break them silently —
 * hence core.
 *
 * World: story 6 (`ANNOTATED`).
 */

const C = ANNOTATED.confirmed;
const U = ANNOTATED.user;

/** What the author sees tinted on chapter 11 by default (processed, missing and deactivated left out). */
const AUTHOR_TINT = [C.fox, C.morning, C.movedTo, C.emptied, C.underQuote, U.clearing, U.mill, ANNOTATED.anonymised];

/** What `confirmed` sees: their own, the processed one included. */
const CONFIRMED_TINT = [C.fox, C.morning, C.processed, C.movedTo, C.emptied, C.underQuote];

async function visit(page: Page) {
  const overlay = new AnnotationOverlay(page);
  const chapter = new ChapterPage(page, ANNOTATED.storySlug, ANNOTATED.chapter.slug);
  await overlay.rewriteAssetHost();
  await chapter.goto();
  await overlay.expectHighlighted([]);
  await overlay.toggle.click();
  return { overlay, chapter };
}

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

  await overlay.toggle.click();
  await overlay.expectHighlighted(filtered);
  await expect(overlay.filterMenu).toBeVisible();
  await expect(overlay.avatars.first()).toBeVisible();
});

test('view mode: text only and quotes hide the annotations, and annotations can be chosen again', async ({ author }) => {
  const { overlay, chapter } = await visit(author);
  await overlay.expectHighlighted(AUTHOR_TINT);

  await chapter.viewMode('text').click();
  await overlay.expectHighlighted([]);
  await chapter.viewMode('annotations').click();
  await overlay.expectHighlighted(AUTHOR_TINT);

  await chapter.viewMode('quotes').click();
  await overlay.expectHighlighted([]);
  await chapter.viewMode('annotations').click();
  await overlay.expectHighlighted(AUTHOR_TINT);
});

test('quote overlap: yellow mark and underline on the same words; the mark opens the quote', async ({ confirmed }) => {
  const { overlay, chapter } = await visit(confirmed);
  await overlay.expectHighlighted(CONFIRMED_TINT);
  const mark = chapter.quoteHighlights.filter({ hasText: ANNOTATED.quote });
  await expect(mark).toBeVisible();
  await mark.click();
  await expect(chapter.quotePanel).toBeVisible();
});
