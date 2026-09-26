import { devices } from '@playwright/test';
import path from 'node:path';
import { ChapterQuotes } from '../../pages/ChapterQuotes';
import { QUOTES, STORY, storageStatePath } from '../../support/fixtures';
import { ROOT } from '../../support/sail';
import { expect, test } from '../../support/test';

/**
 * FEATURE — quotable-blocks-opt-in (VERIFY). Delete at WRAP.
 *
 * Only text blocks are quotable: « Citer » disappears as soon as a selection
 * touches an image caption, and highlights / heat read text blocks only. All of
 * it is client-side (toolbar applicability, canonical text, tint placement), so
 * none of it is reachable from a PHP request.
 *
 * Order matters: the author checks on the Simple chapter pin the seeded counts,
 * so they run before any reader saves a new quote there.
 *
 * The triple-click row is expected to fail until BUILD fixes the dead « Citer »
 * button. Set E2E_SHOTS_DIR to write the VERIFY evidence screenshots.
 */

const ILLUSTRATED = STORY.illustratedChapter;
const CAPTION = ILLUSTRATED.caption;
const MULTI_BLOCK_ERROR = 'La sélection doit rester dans un même bloc de texte.';

test.describe('author', () => {
  test('ch.7 heat and summary list both quotes, nothing tinted on the image', async ({ author }) => {
    const chapter = new ChapterQuotes(author);
    await chapter.goto();

    await expect(chapter.badge).toHaveAttribute('data-quote-author-badge', '2');
    await chapter.showHeat();

    const heatText = (await chapter.heat.allTextContents()).join('');
    expect(heatText).toContain(QUOTES.formattedPassage);
    expect(heatText).toContain(QUOTES.belowImagePassage);
    expect(await chapter.heatOutsideTextBlocks(), 'a heat mark sits outside a text block').toBe(0);
    await expect(chapter.image.locator('mark')).toHaveCount(0);
    await expect(chapter.markers).toHaveCount(2);

    await chapter.openSummary();
    await expect(chapter.summaryRows).toHaveCount(2);
    await expect(chapter.summaryRows.filter({ hasText: QUOTES.formattedPassage })).toHaveCount(1);
    await expect(chapter.summaryRows.filter({ hasText: QUOTES.belowImagePassage })).toHaveCount(1);
    await chapter.evidence('07-author-ch7-heat-summary');
  });

  test('ch.3 (Simple) heat, markers, badge and summary as seeded', async ({ author }) => {
    const chapter = new ChapterQuotes(author, STORY.slug, STORY.simpleChapter.slug);
    await chapter.goto();

    await expect(chapter.badge).toHaveAttribute('data-quote-author-badge', '5');
    await chapter.showHeat();

    const heatText = (await chapter.heat.allTextContents()).join('');
    expect(heatText).toContain('La première phrase du premier bloc, assez longue');
    expect(heatText).toContain(QUOTES.lonePassage);
    await expect(chapter.heat.locator('xpath=self::*[@data-quote-depth="3"]')).not.toHaveCount(0);
    // shared (x2), overlapping, lone — the stale one has no marker.
    await expect(chapter.markers).toHaveCount(3);

    await chapter.openSummary();
    await expect(chapter.summaryRows).toHaveCount(4);
    const shared = chapter.summaryRows.filter({ hasText: QUOTES.sharedPassage });
    await expect(shared).toContainText('2');
    const stale = chapter.summaryRows.filter({ hasText: QUOTES.stalePassage });
    await expect(stale).toHaveCount(1);
    await expect(stale.locator('button')).toHaveCount(0);
    await chapter.evidence('09-author-ch3-heat-summary');
  });
});

test.describe('confirmed reader, desktop', () => {
  test('ch.7 seeded quote highlights in place', async ({ confirmed, admin }) => {
    const mine = new ChapterQuotes(confirmed);
    await mine.goto();
    await expect(mine.tints.first()).toBeVisible();
    expect(await mine.tintTexts()).toEqual([QUOTES.formattedPassage]);
    await expect(mine.image.locator('mark')).toHaveCount(0);
    await mine.evidence('06a-confirmed-ch7-tint');

    // The below-image quote belongs to admin: a reader only sees their own.
    const theirs = new ChapterQuotes(admin);
    await theirs.goto();
    await expect(theirs.tints.first()).toBeVisible();
    expect(await theirs.tintTexts()).toEqual([QUOTES.belowImagePassage]);
    await expect(theirs.tints.first().locator('xpath=ancestor::div[contains(@class,"ce-block--text")]')).toHaveCount(1);
    await theirs.evidence('06b-admin-ch7-tint-below-image');
  });

  test('ch.7 selection inside the caption shows no toolbar at all', async ({ confirmed }) => {
    const chapter = new ChapterQuotes(confirmed);
    await chapter.goto();
    await chapter.dragSelect('Légende', 'illustration');
    expect(await chapter.selectionText()).toContain("Légende de l'illustration");
    await expect(chapter.toolbar).toBeHidden();
    await chapter.evidence('02-caption-no-toolbar');
  });

  test('ch.7 drag from the first block into the caption: no « Citer »', async ({ confirmed }) => {
    const chapter = new ChapterQuotes(confirmed);
    await chapter.goto();
    await chapter.dragSelect('phrase.', 'Légende');
    const text = await chapter.selectionText();
    expect(text).toContain('phrase.');
    expect(text).toContain('Légende');
    await expect(chapter.citeButton).toBeHidden();
    await expect(chapter.toolbar).toBeHidden();
    await chapter.evidence('03-block-into-caption-no-cite');
  });

  test('ch.7 drag across the image into the second block: no « Citer » (caption touched)', async ({ confirmed }) => {
    const chapter = new ChapterQuotes(confirmed);
    await chapter.goto();
    await chapter.dragSelect('phrase.', 'Epsilon');
    const text = await chapter.selectionText();
    expect(text).toContain(CAPTION);
    expect(text).toContain('Epsilon');
    await expect(chapter.toolbar).toBeHidden();
    await chapter.evidence('04a-across-image-no-cite');
  });

  test('ch.4 drag across two text blocks: « Citer » shows, mini-form refuses', async ({ confirmed }) => {
    const chapter = new ChapterQuotes(confirmed, STORY.slug, STORY.advancedChapter.slug);
    await chapter.goto();
    await chapter.dragSelect('aussi suffisamment', 'Beta un.');
    await expect(chapter.citeButton).toBeVisible();
    await chapter.citeButton.click();
    await expect(chapter.miniForm).toBeVisible();
    await expect(chapter.miniFormError).toHaveText(MULTI_BLOCK_ERROR);
    await expect(chapter.miniFormSave).toBeDisabled();
    await chapter.evidence('04b-two-text-blocks-multi-block-error');
  });

  test('ch.7 triple-click the paragraph before the image', async ({ confirmed }) => {
    const chapter = new ChapterQuotes(confirmed);
    await chapter.goto();
    await chapter.tripleClick('Delta un.');
    expect(await chapter.selectionText()).toContain('au milieu de la phrase.');
    await chapter.evidence('05-triple-click-before-image');
    console.log('[triple-click range]', await chapter.rangeEnds());
    // Observation row: whichever way it goes, never a dead button.
    if (await chapter.citeButton.isVisible()) {
      await chapter.citeButton.click();
      await expect(chapter.miniForm).toBeVisible();
      test.info().annotations.push({ type: 'triple-click', description: 'Citer shown, form opens' });
    } else {
      await expect(chapter.toolbar).toBeHidden();
      test.info().annotations.push({ type: 'triple-click', description: 'Citer hidden, no toolbar' });
    }
  });

  test('ch.7 a few words in the first block: « Citer », mini-form opens and saves', async ({ confirmed }) => {
    const chapter = new ChapterQuotes(confirmed);
    await chapter.goto();
    await chapter.dragSelect('Un paragraphe', 'avec');
    await expect(chapter.citeButton).toBeVisible();
    await chapter.evidence('01a-first-block-cite');

    await chapter.citeButton.click();
    await expect(chapter.miniForm).toBeVisible();
    await expect(chapter.miniFormQuote).toHaveText('Un paragraphe avec');
    await expect(chapter.miniFormError).toBeHidden();
    await chapter.miniForm.locator('textarea').fill('Note E2E quotable-blocks');
    await chapter.evidence('01b-first-block-mini-form');
    await chapter.miniFormSave.click();
    await expect(chapter.miniForm).toBeHidden();
    await expect.poll(() => chapter.tintTexts()).toContain('Un paragraphe avec');
  });

  test('ch.3 (Simple) seeded highlights render and a new passage can be quoted', async ({ confirmed }) => {
    const chapter = new ChapterQuotes(confirmed, STORY.slug, STORY.simpleChapter.slug);
    await chapter.goto();
    await expect(chapter.tints.first()).toBeVisible();
    expect((await chapter.tintTexts()).sort()).toEqual([QUOTES.sharedPassage, QUOTES.lonePassage].sort());
    await chapter.evidence('08a-confirmed-ch3-tints');

    await chapter.dragSelect('Gamma un.', 'troisième bloc,');
    await expect(chapter.citeButton).toBeVisible();
    await chapter.citeButton.click();
    await expect(chapter.miniForm).toBeVisible();
    await expect(chapter.miniFormError).toBeHidden();
    await chapter.miniFormSave.click();
    await expect(chapter.miniForm).toBeHidden();
    await expect.poll(() => chapter.tintTexts()).toContain(
      'Gamma un. La première phrase du troisième bloc,',
    );
    await chapter.evidence('08b-confirmed-ch3-new-quote');
  });
});

test('ch.3 (Simple) layout: indent, paragraph spacing, last paragraph padding', async ({ guest }) => {
  const simple = new ChapterQuotes(guest, STORY.slug, STORY.simpleChapter.slug);
  await simple.goto();
  const metrics = await simple.paragraphMetrics();
  expect(metrics).toHaveLength(6);
  for (const p of metrics) expect(p.indent).toBe('32px');
  for (const p of metrics.slice(0, -1)) expect(p.paddingBottom).toBe('12px');
  expect(metrics.at(-1)!.paddingBottom).toBe('0px');
  await simple.evidence('10-simple-layout');

  // The Advanced twin carries the same six paragraphs: before the feature the
  // two rendered identically, and the Simple wrapper must not change that.
  const advanced = new ChapterQuotes(guest, STORY.slug, STORY.advancedChapter.slug);
  await advanced.goto();
  expect(metrics).toEqual(await advanced.paragraphMetrics());
});

test.describe('no quoting for guest and non-confirmed user', () => {
  for (const role of ['guest', 'user'] as const) {
    test(`${role}: selecting text on ch.3 and ch.7 shows no toolbar`, async ({ guest, user }) => {
      const page = role === 'guest' ? guest : user;
      for (const slug of [STORY.simpleChapter.slug, ILLUSTRATED.slug]) {
        const chapter = new ChapterQuotes(page, STORY.slug, slug);
        await chapter.goto();
        const [from, to] = slug === ILLUSTRATED.slug ? ['Un paragraphe', 'avec'] : ['Alpha un.', 'phrase du'];
        await chapter.dragSelect(from, to);
        expect(await chapter.selectionText()).toContain(from);
        await expect(chapter.toolbar).toBeHidden();
      }
      await new ChapterQuotes(page).evidence(`11-${role}-no-toolbar`);
    });
  }
});

test.describe('confirmed reader, mobile (touch)', () => {
  test('ch.7 touch selection: toolbar below it in a text block, none on the caption', async ({ browser }) => {
    const context = await browser.newContext({
      ...devices['Pixel 7'],
      locale: 'fr-FR',
      storageState: path.join(ROOT, storageStatePath('confirmed')),
    });
    const page = await context.newPage();
    const chapter = new ChapterQuotes(page);
    await chapter.goto();

    await chapter.touchSelect('Le paragraphe', 'suit');
    await expect(chapter.citeButton).toBeVisible();
    const { selectionBottom, toolbarTop } = await chapter.selectionAndToolbarBox();
    expect(toolbarTop, 'toolbar must sit below the selection on touch').toBeGreaterThan(selectionBottom);
    await chapter.evidence('12a-mobile-text-toolbar-below');

    await chapter.touchSelect('Légende', 'illustration');
    await expect(chapter.toolbar).toBeHidden();
    await chapter.evidence('12b-mobile-caption-no-toolbar');

    await context.close();
  });
});
