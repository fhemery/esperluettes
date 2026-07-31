import { ChapterEditPage } from '../../pages/ChapterEditPage';
import { ChapterPage } from '../../pages/ChapterPage';
import { STORY } from '../../support/fixtures';
import { expect, test } from '../../support/test';

/**
 * FEATURE — chapters-multi-edit, reading side.
 *
 * Runs before `chapters-multi-edit.spec.ts` (file order) and only reads: the
 * two chapters it uses, `simpleChapter` and `advancedChapter`, carry the *same
 * six paragraphs* — one as a single rich-text body, the other split 2/2/2
 * across three text blocks — and are never written to by any spec. The one
 * exception is the mobile image case, which has to author the chapter it
 * reads: no fixture carries an image (the seeder has no file in storage) and
 * the authoring spec's image chapters only exist after this file has run.
 *
 * What is asserted here is only what a browser can settle: computed typography
 * and laid-out geometry (risk 5 / open question 3 of the plan), selection
 * behaviour, and layout at a real viewport. That advanced content prints in a
 * single `[data-quote-article]`, who may see what, and what is stored are all
 * asserted in app/Domains/Story/Tests/Feature/Chapters/.
 */

const MOBILE = { width: 375, height: 812 };

/** Everything about a chapter's paragraphs that a regression would move. */
async function typography(chapter: ChapterPage) {
  const count = await chapter.paragraphs.count();
  const styles: Record<string, string>[] = [];
  const gaps: number[] = [];
  for (let i = 0; i < count; i += 1) {
    styles.push(await chapter.paragraphStyle(i));
    if (i < count - 1) gaps.push(await chapter.paragraphGap(i));
  }
  return { count, styles, gaps };
}

test.describe('typography — a converted chapter must read like an unconverted one', () => {
  test('the advanced chapter is split into blocks and the simple one is not', async ({ guest }) => {
    const simple = new ChapterPage(guest, STORY.slug, STORY.simpleChapter.slug);
    await simple.goto();
    await expect(simple.textBlocks, 'simple chapter must have no block wrappers').toHaveCount(0);
    await expect(simple.paragraphs).toHaveCount(6);

    const advanced = new ChapterPage(guest, STORY.slug, STORY.advancedChapter.slug);
    await advanced.goto();
    await expect(advanced.textBlocks, 'advanced chapter must render three text blocks').toHaveCount(3);
    await expect(advanced.paragraphs).toHaveCount(6);
    await expect(advanced.quoteRoot, 'exactly one quote root').toHaveCount(1);
  });

  test('paragraph indent and spacing are identical, block boundaries included', async ({ guest }) => {
    const simple = new ChapterPage(guest, STORY.slug, STORY.simpleChapter.slug);
    await simple.goto();
    const reference = await typography(simple);

    const advanced = new ChapterPage(guest, STORY.slug, STORY.advancedChapter.slug);
    await advanced.goto();
    const converted = await typography(advanced);

    // The indent that open question 3 asks about: inherited into `.ce-block--text`?
    expect(reference.styles[0]!.textIndent, 'the reference itself must be indented').toBe('32px');
    expect(converted.styles.map((s) => s.textIndent)).toEqual(reference.styles.map((s) => s.textIndent));

    // Inter-paragraph spacing, including paragraphs 1→2 and 3→4, which sit
    // across a block boundary in the converted chapter (assumption A11).
    expect(converted.styles.map((s) => s.paddingBottom)).toEqual(reference.styles.map((s) => s.paddingBottom));
    expect(converted.gaps, 'laid-out gaps between consecutive paragraphs').toEqual(reference.gaps);

    // …and everything else the paragraphs inherit.
    expect(converted.styles).toEqual(reference.styles);
  });

  test('only the very last paragraph drops its bottom padding', async ({ guest }) => {
    const advanced = new ChapterPage(guest, STORY.slug, STORY.advancedChapter.slug);
    await advanced.goto();

    // Paragraph 1 is the last of block 1: it must keep its padding, or spacing
    // collapses at every boundary.
    expect((await advanced.paragraphStyle(1)).paddingBottom).toBe('12px');
    expect((await advanced.paragraphStyle(3)).paddingBottom).toBe('12px');
    expect((await advanced.paragraphStyle(5)).paddingBottom).toBe('0px');
  });
});

test.describe('reading a converted chapter', () => {
  test('the blocks read as one continuous text', async ({ guest }) => {
    const advanced = new ChapterPage(guest, STORY.slug, STORY.advancedChapter.slug);
    await advanced.goto();

    const text = await advanced.text();
    expect(text.indexOf('Alpha un.')).toBeLessThan(text.indexOf('Beta un.'));
    expect(text.indexOf('Beta un.')).toBeLessThan(text.indexOf('Gamma un.'));
  });

  test('a confirmed reader can still select text and reach the quote button', async ({ confirmed }) => {
    const advanced = new ChapterPage(confirmed, STORY.slug, STORY.advancedChapter.slug);
    await advanced.goto();

    await selectParagraph(confirmed, advanced, 2); // inside the second block

    await expect(confirmed.locator('.comment-toolbar')).toBeVisible();
    await expect(confirmed.locator('.quote-toolbar-btn')).toBeVisible();
  });

  test('a quote taken on an unconverted chapter is highlighted after reload', async ({ confirmed }) => {
    const simple = new ChapterPage(confirmed, STORY.slug, STORY.simpleChapter.slug);
    await simple.goto();

    await selectParagraph(confirmed, simple, 0);
    await confirmed.locator('.quote-toolbar-btn').click();
    const dialog = confirmed.locator('[role="dialog"][aria-labelledby="quote-mini-form-title"]');
    await expect(dialog).toBeVisible();
    await dialog.locator('#quote-note-input').fill('Note E2E');
    await dialog.getByRole('button', { name: 'Enregistrer' }).click();

    await expect(simple.quoteRoot.locator('mark.quote-tint').first(), 'highlight after saving').toBeVisible();

    await simple.goto();
    await expect(simple.quoteRoot.locator('mark.quote-tint').first(), 'highlight after reload').toBeVisible();
  });

  test('no edit affordance for a guest or a non-confirmed reader', async ({ guest, user }) => {
    for (const page of [guest, user]) {
      const advanced = new ChapterPage(page, STORY.slug, STORY.advancedChapter.slug);
      await advanced.goto();
      await expect(advanced.paragraphs).toHaveCount(6);
      await expect(advanced.editLink, 'edit link visible to a reader').toHaveCount(0);
    }
  });
});

test.describe('mobile', () => {
  test.use({ viewport: MOBILE });

  test('a converted chapter stacks without scrolling sideways', async ({ guest }) => {
    const advanced = new ChapterPage(guest, STORY.slug, STORY.advancedChapter.slug);
    await advanced.goto();

    await expect(advanced.paragraphs).toHaveCount(6);
    expect(await advanced.hasHorizontalScroll(), 'horizontal scrollbar at 375px').toBe(false);

    // Blocks stacked, not side by side.
    const boxes: { top: number; bottom: number; width: number }[] = await advanced.textBlocks.evaluateAll((els) =>
      els.map((el) => el.getBoundingClientRect()).map((r) => ({ top: r.top, bottom: r.bottom, width: r.width })),
    );
    for (let i = 1; i < boxes.length; i += 1) {
      const [previous, current] = [boxes[i - 1]!, boxes[i]!];
      expect(current.top, `block ${i} sits below block ${i - 1}`).toBeGreaterThanOrEqual(previous.bottom - 1);
    }
    for (const box of boxes) {
      expect(box.width, 'block wider than the viewport').toBeLessThanOrEqual(MOBILE.width);
    }
  });

  test('a chapter carrying an image does not scroll sideways either', async ({ author }) => {
    const create = ChapterEditPage.create(author, STORY.slug);
    await create.goto();
    await create.title.fill('Chapitre illustré E2E');
    await create.blocks.goAdvanced();
    // Converting an empty body may or may not seed a text block; the chapter
    // needs one either way, since an image contributes no text to `content`.
    if ((await create.blocks.textBlocks.count()) === 0) await create.blocks.addBlock('text');
    await create.blocks.textBlockEditor(0).fill('Avant l’image.');

    await create.blocks.addBlock('image');
    const image = create.blocks.image((await create.blocks.blocks.count()) - 1);
    await image.upload('illustration-mobile.png');
    await image.alt.fill('Illustration E2E');

    await create.setPublished(true);
    await create.save();

    const slug = new URL(author.url()).pathname.split('/').pop()!;
    const read = new ChapterPage(author, STORY.slug, slug);
    await read.goto();
    await expect(read.images, 'the authored image did not render').toHaveCount(1);

    // Defect D3: the article's `[text-indent:2rem]` was inherited by the
    // figure's inline `<picture>`, pushing it 32px past its own container —
    // enough to give the page a horizontal scrollbar at 375px.
    expect(await read.hasHorizontalScroll(), 'horizontal scrollbar at 375px on a chapter with an image').toBe(false);

    const geometry = await read.images.first().evaluate((img) => {
      const figure = img.closest('figure')!;
      return {
        indent: getComputedStyle(figure).textIndent,
        figureRight: figure.getBoundingClientRect().right,
        imageRight: img.getBoundingClientRect().right,
      };
    });
    expect(geometry.indent, 'the figure must opt out of the article indent').toBe('0px');
    expect(geometry.imageRight, 'the image overflows its own figure').toBeLessThanOrEqual(geometry.figureRight + 1);
  });
});

/** Drag-select a whole paragraph the way a reader would, so `mouseup` fires. */
async function selectParagraph(page: import('@playwright/test').Page, chapter: ChapterPage, index: number) {
  const box = await chapter.paragraphs.nth(index).boundingBox();
  expect(box, `paragraph ${index} is not laid out`).toBeTruthy();
  await page.mouse.move(box!.x + 40, box!.y + box!.height / 2);
  await page.mouse.down();
  await page.mouse.move(box!.x + box!.width - 20, box!.y + box!.height / 2, { steps: 12 });
  await page.mouse.up();
}
