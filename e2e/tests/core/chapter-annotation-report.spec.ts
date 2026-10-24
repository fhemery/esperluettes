import type { Locator, Page } from '@playwright/test';
import { AnnotationOverlay } from '../../pages/AnnotationOverlay';
import { ChapterAnnotations } from '../../pages/ChapterAnnotations';
import { ChapterPage } from '../../pages/ChapterPage';
import { CommentThread } from '../../pages/CommentThread';
import { ANNOTATED } from '../../support/fixtures';
import { expect, test } from '../../support/test';

/**
 * CORE — « Signaler » on a single annotation, from both surfaces.
 *
 * The button is an Alpine `reportButton` nested inside two `x-for` templates (the
 * « N annotations » pop-up and the in-chapter popover), and the report modal is
 * fetched and injected into the container beside it — inside an already-open
 * modal, or inside a teleported popover that closes on outside clicks. Who may
 * report what is covered by PHP tests; what only a browser shows is that the
 * modal opens, is usable and leaves the surface under it intact.
 *
 * World: story 6 (`ANNOTATED`). `confirmed`'s annotation on `Le renard roux`
 * has a reply by `author`. Reports are the only thing written.
 */

const C = ANNOTATED.confirmed;
const ROOT = ANNOTATED.roots.confirmed;
const SUBMITTED = 'Votre signalement a été envoyé avec succès';

async function visit(page: Page) {
  const overlay = new AnnotationOverlay(page);
  const chapter = new ChapterPage(page, ANNOTATED.storySlug, ANNOTATED.chapter.slug);
  await chapter.goto();
  return { overlay, chapter, thread: new CommentThread(page), annotations: new ChapterAnnotations(page) };
}

/** Picks the seeded « Other » reason in the open report modal and sends it. */
async function submitReport(page: Page): Promise<void> {
  const reason = page.locator('#reason_id');
  await expect(reason).toBeVisible();
  await reason.selectOption({ index: 1 });
  await page.getByRole('button', { name: 'Envoyer le signalement' }).click();
  await expect(page.getByText(SUBMITTED)).toBeVisible();
}

/** « Fermer » of the success state, not the pop-up's or popover's own close button. */
async function closeReport(page: Page): Promise<void> {
  await page.locator('[x-data^="reportForm"]').getByRole('button', { name: 'Fermer', exact: true }).click();
  await expect(page.locator('#reason_id')).toBeHidden();
}

/** The row of the pop-up whose body contains `text`. */
function rowWith(annotations: ChapterAnnotations, text: string): Locator {
  return annotations.serverRows.filter({ hasText: text });
}

test('author reports a reader annotation from the pop-up, not their own reply', async ({ author }) => {
  const { thread, annotations } = await visit(author);
  await thread.scrollToBottom();
  await annotations.openPublished(thread.item(ROOT));

  const fox = rowWith(annotations, ANNOTATED.bodies.fox);
  const reply = annotations.replies(fox).filter({ hasText: ANNOTATED.bodies.authorReply });
  await expect(reply).toHaveCount(1);
  await expect(reply.getByTestId('annotation-report')).toHaveCount(0);
  await expect(rowWith(annotations, 'Annotation supprimée').getByTestId('annotation-report')).toHaveCount(0);

  await fox.getByTestId('annotation-report').first().click();
  await submitReport(author);
  await closeReport(author);
  await expect(annotations.serverDialog).toBeVisible();
});

test('reader reports the author reply from the popover, and the popover stays usable', async ({ confirmed }) => {
  const { overlay } = await visit(confirmed);
  await overlay.toggle.click();
  await overlay.open(C.fox);

  const buttons = overlay.popover.getByTestId('annotation-popover-report');
  const reply = overlay.popover.getByTestId('annotation-popover-reply').filter({ hasText: ANNOTATED.bodies.authorReply });
  await expect(reply).toHaveCount(1);
  // Only the author's reply is reportable: the reader's own annotation carries no button.
  await expect(buttons).toHaveCount(1);
  await expect(reply.getByTestId('annotation-popover-report')).toHaveAccessibleName('Signaler');

  await reply.getByTestId('annotation-popover-report').click();
  await submitReport(confirmed);

  // Clicking inside the modal must not have closed the popover under it.
  await expect(overlay.popover).toBeVisible();
  await closeReport(confirmed);
  await expect(overlay.popover).toBeVisible();

  await overlay.popover.getByRole('button', { name: 'Fermer', exact: true }).first().click();
  await expect(overlay.popover).toBeHidden();
  await overlay.open(C.fox);
});

test('reader sees no « Signaler » on their own annotation', async ({ confirmed }) => {
  const { overlay, thread, annotations } = await visit(confirmed);
  await overlay.toggle.click();
  await overlay.open(C.underQuote);
  await expect(overlay.popover).toContainText('Le ruisseau, sous ma citation.');
  await expect(overlay.popover.getByTestId('annotation-popover-report')).toHaveCount(0);
  await confirmed.keyboard.press('Escape');

  await thread.scrollToBottom();
  await annotations.openPublished(thread.item(ROOT));
  const mine = rowWith(annotations, ANNOTATED.bodies.morning);
  await expect(mine).toHaveCount(1);
  await expect(mine.getByTestId('annotation-report')).toHaveCount(0);
});
