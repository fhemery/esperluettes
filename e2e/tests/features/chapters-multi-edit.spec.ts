import { ChapterEditPage } from '../../pages/ChapterEditPage';
import { ChapterPage } from '../../pages/ChapterPage';
import { expect, test } from '../../support/test';
import { COAUTHORED_STORY, STORY } from '../../support/fixtures';

/**
 * FEATURE — chapters-multi-edit, authoring side.
 *
 * Everything here needs Alpine and Quill to have run: the Simple/Avancé
 * toggle, the conversion (which copies one textarea into another and re-inits
 * Quill on it), the block controls, and the client-side alt-text guard. None
 * of it is reachable from a PHP request.
 *
 * Deliberately *not* here, because the PHP suite already settles it far more
 * cheaply — see app/Domains/Story/Tests/Feature/Chapters/:
 *   - what is stored, and the mode a form reopens in (ChapterAdvancedModeTest)
 *   - the server-side alt-text and empty-blocks rules (idem)
 *   - the upload scope being the acting user (idem)
 *   - counts across a conversion (ChapterConversionCountsTest)
 *   - the media GC and soft-deleted chapters (ChapterMediaUsageProviderTest)
 *
 * The chapters written to here are the draft (`draftChapter`), the
 * word-count chapter (`countedChapter`) and the co-authored story's chapter —
 * never the two the reading spec compares.
 */

const MOBILE = { width: 375, height: 812 };
const TO_SIMPLE_DISABLED = 'Retirez les images et ne gardez qu’un seul bloc de texte pour revenir en mode Simple.';

test.describe('the form as it opens', () => {
  test('an unconverted chapter opens in Simple mode, with the links toolbar and 15 lines', async ({ author }) => {
    const edit = new ChapterEditPage(author, STORY.slug, STORY.draftChapter.slug);
    await edit.goto();

    expect(await edit.blocks.mode()).toBe('simple');
    await expect(edit.blocks.simplePane).toBeVisible();
    await expect(edit.blocks.blocks).toHaveCount(0);

    // The `links` preset: formatting plus a link button, and no image button.
    await expect(edit.content.toolbar.locator('button.ql-link')).toHaveCount(1);
    await expect(edit.content.toolbar.locator('button.ql-image')).toHaveCount(0);

    // 15 visible lines and indented paragraphs, as before the feature.
    expect(await edit.content.visibleLines()).toBe(15);
    const indented = await edit.content.body.evaluate((el) => !!el.closest('.ql-indent'));
    expect(indented, 'the writing surface is inside a .ql-indent wrapper').toBe(true);
  });

  test('a new chapter opens in Simple mode, empty, with the toggle available', async ({ author }) => {
    const create = ChapterEditPage.create(author);
    await create.goto();

    expect(await create.blocks.mode()).toBe('simple');
    expect(await create.content.text()).toBe('');
    await expect(create.blocks.simpleButton).toBeVisible();
    await expect(create.blocks.advancedButton).toBeVisible();
  });
});

test.describe('converting and coming back', () => {
  test('clicking Avancé turns the existing HTML into one text block', async ({ author }) => {
    const edit = new ChapterEditPage(author, STORY.slug, STORY.draftChapter.slug);
    await edit.goto();

    const before = await edit.content.text();
    expect(before, 'the fixture chapter must not be empty').not.toBe('');

    await edit.blocks.goAdvanced();

    expect(await edit.blocks.mode()).toBe('advanced');
    await expect(edit.blocks.blocks).toHaveCount(1);
    await expect(edit.blocks.textBlocks).toHaveCount(1);
    expect(await edit.blocks.textBlockEditor(0).text(), 'text lost in conversion').toBe(before);
  });

  test('Simple is disabled with its French tooltip once there is more than one block', async ({ author }) => {
    const edit = new ChapterEditPage(author, STORY.slug, STORY.draftChapter.slug);
    await edit.goto();
    await edit.blocks.goAdvanced();

    await expect(edit.blocks.simpleButton, 'one text block ⇒ Simple is reachable').toBeEnabled();

    await edit.blocks.addBlock('text');
    await expect(edit.blocks.simpleButton).toBeDisabled();
    await expect(edit.blocks.simpleButton).toHaveAttribute('title', TO_SIMPLE_DISABLED);

    // An image is enough on its own to keep Simple out of reach.
    await edit.blocks.addBlock('image');
    await expect(edit.blocks.simpleButton).toBeDisabled();
    await expect(edit.blocks.simpleButton).toHaveAttribute('title', TO_SIMPLE_DISABLED);
  });

  test('Simple becomes reachable again once the extra block is deleted', async ({ author }) => {
    const edit = new ChapterEditPage(author, STORY.slug, STORY.draftChapter.slug);
    await edit.goto();
    await edit.blocks.goAdvanced();
    await edit.blocks.addBlock('text');
    await expect(edit.blocks.simpleButton).toBeDisabled();

    await edit.blocks.removeBlock(1);

    await expect(edit.blocks.blocks, 'the block is gone from the DOM').toHaveCount(1);
    await expect(
      edit.blocks.simpleButton,
      'one text block left, yet Simple is still disabled — the component never re-synced after the delete',
    ).toBeEnabled();
  });

  test('returning to Simple carries the text back into the simple pane', async ({ author }) => {
    const edit = new ChapterEditPage(author, STORY.slug, STORY.draftChapter.slug);
    await edit.goto();
    const before = await edit.content.text();

    await edit.blocks.goAdvanced();
    await edit.blocks.goSimple();

    expect(await edit.blocks.mode()).toBe('simple');
    await expect(edit.blocks.blocks).toHaveCount(0);
    expect(await edit.content.text()).toBe(before);
  });
});

test.describe('block controls', () => {
  test('a block added under Alpine is the same writing surface as the first', async ({ author }) => {
    const edit = new ChapterEditPage(author, STORY.slug, STORY.draftChapter.slug);
    await edit.goto();
    await edit.blocks.goAdvanced();
    await edit.blocks.addBlock('text');

    const first = edit.blocks.textBlockEditor(0).body;
    const second = edit.blocks.textBlockEditor(1).body;
    await expect(second, 'the added block never booted Quill').toBeVisible();

    const [a, b] = [await first.boundingBox(), await second.boundingBox()];
    expect(Math.abs(a!.height - b!.height), 'the two writing surfaces differ in height').toBeLessThanOrEqual(1);

    for (const surface of [first, second]) {
      expect(await surface.evaluate((el) => !!el.closest('.ql-indent')), 'missing indentation').toBe(true);
    }

    // Same toolbar preset in a dynamically added block.
    await expect(edit.blocks.textBlockEditor(1).toolbar.locator('button.ql-link')).toHaveCount(1);
  });

  test('blocks can be inserted, reordered and deleted, and the order is submitted', async ({ author }) => {
    const edit = new ChapterEditPage(author, STORY.slug, STORY.draftChapter.slug);
    await edit.goto();
    await edit.blocks.goAdvanced();

    await edit.blocks.textBlockEditor(0).fill('Premier bloc');
    await edit.blocks.insertAfter(0, 'text');
    await edit.blocks.textBlockEditor(1).fill('Second bloc');

    const order = await edit.blocks.order();
    expect(order).toHaveLength(2);

    await edit.blocks.moveDown(0);
    expect(await edit.blocks.order(), 'order not resubmitted after a move').toEqual([order[1], order[0]]);
    expect(await edit.blocks.textBlockEditor(0).text()).toBe('Second bloc');
  });

  test('deleting a block updates the order that gets submitted', async ({ author }) => {
    const edit = new ChapterEditPage(author, STORY.slug, STORY.draftChapter.slug);
    await edit.goto();
    await edit.blocks.goAdvanced();
    await edit.blocks.textBlockEditor(0).fill('Premier bloc');
    await edit.blocks.addBlock('text');
    await edit.blocks.textBlockEditor(1).fill('Second bloc');
    const order = await edit.blocks.order();

    await edit.blocks.removeBlock(1);

    expect(await edit.blocks.order(), 'blocks_order still names the deleted block').toEqual([order[0]]);
    expect(await edit.blocks.textBlockEditor(0).text()).toBe('Premier bloc');
  });
});

test.describe('images', () => {
  test('the form can carry a file at all', async ({ author }) => {
    const edit = new ChapterEditPage(author, STORY.slug, STORY.draftChapter.slug);
    await edit.goto();

    // The image block submits `blocks[uid][file]`, so the form must be
    // multipart — News's own MultiEdit forms are. Without it the browser sends
    // the file name as a plain string and the `image` rule rejects the save.
    await expect(
      author.locator('form:has([data-testid="chapter-save"])'),
      'the chapter edit form is not multipart, so no image block can ever be uploaded',
    ).toHaveAttribute('enctype', 'multipart/form-data');

    // The create form carries the same blocks, so it needs the same enctype —
    // an author's very first chapter can hold an image too.
    const create = ChapterEditPage.create(author, STORY.slug);
    await create.goto();

    await expect(
      author.locator('form:has([data-testid="chapter-save"])'),
      'the chapter create form is not multipart, so no image block can ever be uploaded',
    ).toHaveAttribute('enctype', 'multipart/form-data');
  });

  test('an upload previews, saves under the author scope and renders on the reading page', async ({ author }) => {
    const edit = new ChapterEditPage(author, STORY.slug, STORY.draftChapter.slug);
    await edit.goto();
    await edit.blocks.goAdvanced();
    await edit.blocks.textBlockEditor(0).fill('Avant l’image.');

    await edit.blocks.addBlock('image');
    const image = edit.blocks.image(1);
    await image.upload();
    await image.alt.fill('Une image de test');
    await image.caption.fill('Légende E2E');

    await edit.blocks.addBlock('text');
    await edit.blocks.textBlockEditor(1).fill('Après l’image.');

    // Publish it so it can be read back.
    await edit.setPublished(true);
    await edit.save();

    const read = new ChapterPage(author, STORY.slug, STORY.draftChapter.slug);
    await read.goto();

    await expect(read.images).toHaveCount(1);
    await expect(read.images.first()).toHaveAttribute('src', /\/storage\/chapters\/\d+\//);
    await expect(read.captions.first()).toHaveText('Légende E2E');

    // Text, image, text — in that order.
    const positions = await read.quoteRoot.evaluate((root) => {
      const nodes = Array.from(root.querySelectorAll('.ce-block'));
      return nodes.map((n) => (n.classList.contains('ce-block--image') ? 'image' : 'text'));
    });
    expect(positions).toEqual(['text', 'image', 'text']);
  });

  test('a blank alt blocks the save, a blank caption does not', async ({ author }) => {
    const edit = new ChapterEditPage(author, STORY.slug, STORY.draftChapter.slug);
    await edit.goto();
    await edit.blocks.goAdvanced();
    await edit.blocks.addBlock('image');
    // The last block, not block 1: by the time this runs the earlier test has
    // saved image blocks into this chapter, so it reopens with several.
    const image = edit.blocks.image((await edit.blocks.blocks.count()) - 1);
    await image.upload('sans-alt.png');

    // The caption is never required…
    await expect(image.caption).not.toHaveAttribute('required', /.*/);
    // …the alt becomes required as soon as the block carries an image.
    expect(
      await image.alt.evaluate((el: HTMLInputElement) => el.checkValidity()),
      'a blank alt is not reported invalid, so the form would submit',
    ).toBe(false);

    await edit.trySave();
    expect(author.url(), 'the form submitted despite a blank alt').toContain('/edit');

    await image.alt.fill('Alt fourni');
    expect(await image.alt.evaluate((el: HTMLInputElement) => el.checkValidity())).toBe(true);
  });

  test('the reuse picker offers the author their own chapter images, twice if they want', async ({ author }) => {
    const edit = new ChapterEditPage(author, STORY.slug, STORY.draftChapter.slug);
    await edit.goto();
    await edit.blocks.goAdvanced();
    await edit.blocks.addBlock('image');
    await edit.blocks.addBlock('image');

    // The two blocks just added — the chapter may already carry others.
    const count = await edit.blocks.blocks.count();
    const first = edit.blocks.image(count - 2);
    const second = edit.blocks.image(count - 1);
    await first.openPicker();

    const sources = await first.pickerItems.evaluateAll((els) => els.map((el) => (el as HTMLImageElement).src));
    expect(sources.length, 'the picker lists nothing — the author has no reusable chapter image').toBeGreaterThan(0);
    for (const src of sources) {
      expect(src, 'the picker offers an image outside the author’s own chapter folder').toMatch(
        /\/storage\/chapters\/\d+\//,
      );
    }

    await first.chooseFromPicker(0);
    const path = await first.pathInput.inputValue();
    expect(path).toMatch(/^chapters\/\d+\//);

    // The same image, a second time in the same chapter.
    await second.openPicker();
    await second.chooseFromPicker(0);
    expect(await second.pathInput.inputValue()).toBe(path);
  });
});

test.describe('word count', () => {
  test('a no-op conversion moves neither the word nor the character count', async ({ author }) => {
    const edit = new ChapterEditPage(author, STORY.slug, STORY.countedChapter.slug);
    const read = new ChapterPage(author, STORY.slug, STORY.countedChapter.slug);

    // Save once in Simple mode first, so the baseline is what the app itself
    // stores rather than what the seeder wrote: the two must be compared after
    // the same sanitising pass, or the seed's own formatting is the variable.
    await edit.goto();
    await edit.save();
    await read.goto();
    const before = await read.metrics();
    expect(before).toMatch(/\d/);

    await edit.goto();
    await edit.blocks.goAdvanced();
    await edit.save();

    await read.goto();
    expect(await read.metrics(), 'a no-op conversion moved a user-visible count').toBe(before);
  });
});

test.describe('a co-author', () => {
  test('gets the same block editor and their conversion sticks for the other author', async ({ confirmed, author }) => {
    const edit = new ChapterEditPage(confirmed, COAUTHORED_STORY.slug, COAUTHORED_STORY.chapter.slug);
    await edit.goto();

    await edit.blocks.goAdvanced();
    await edit.blocks.addBlock('text');
    await edit.blocks.textBlockEditor(1).fill('Ajouté par le co-auteur.');
    await edit.save();

    const authorEdit = new ChapterEditPage(author, COAUTHORED_STORY.slug, COAUTHORED_STORY.chapter.slug);
    await authorEdit.goto();
    expect(await authorEdit.blocks.mode(), 'the co-author’s conversion did not stick').toBe('advanced');
    await expect(authorEdit.blocks.textBlocks).toHaveCount(2);
  });

  test('uploads into their own media folder, next to the other author’s images', async ({ confirmed, author }) => {
    const edit = new ChapterEditPage(confirmed, COAUTHORED_STORY.slug, COAUTHORED_STORY.chapter.slug);
    await edit.goto();

    await edit.blocks.addBlock('image');
    const image = edit.blocks.image((await edit.blocks.blocks.count()) - 1);
    await image.upload('co-auteur.png');
    await image.alt.fill('Image du co-auteur');
    await edit.save();

    const read = new ChapterPage(confirmed, COAUTHORED_STORY.slug, COAUTHORED_STORY.chapter.slug);
    await read.goto();
    await expect(read.images).toHaveCount(1);
    const coAuthorSrc = await read.images.first().getAttribute('src');

    // The other author adds their own image; both must survive side by side.
    const authorEdit = new ChapterEditPage(author, COAUTHORED_STORY.slug, COAUTHORED_STORY.chapter.slug);
    await authorEdit.goto();
    await authorEdit.blocks.addBlock('image');
    const second = authorEdit.blocks.image((await authorEdit.blocks.blocks.count()) - 1);
    await second.upload('auteur.png');
    await second.alt.fill('Image de l’auteur');
    await authorEdit.save();

    const readBoth = new ChapterPage(author, COAUTHORED_STORY.slug, COAUTHORED_STORY.chapter.slug);
    await readBoth.goto();
    await expect(readBoth.images).toHaveCount(2);
    const sources = await readBoth.images.evaluateAll((els) => els.map((el) => (el as HTMLImageElement).getAttribute('src') ?? ''));
    expect(sources[0]).toBe(coAuthorSrc);
    const folders = sources.map((src) => src.replace(/^.*\/storage\/(chapters\/\d+)\/.*$/, '$1'));
    expect(new Set(folders).size, 'both images landed in the same user folder').toBe(2);
  });
});

test.describe('the form on a phone', () => {
  test.use({ viewport: MOBILE });

  test('the toggle and the block controls stay reachable at 375px', async ({ author }) => {
    const edit = new ChapterEditPage(author, STORY.slug, STORY.countedChapter.slug);
    await edit.goto();

    await expect(edit.blocks.simpleButton).toBeInViewport({ ratio: 0.9 });
    await expect(edit.blocks.advancedButton).toBeInViewport({ ratio: 0.9 });

    await edit.blocks.goAdvanced();
    await edit.blocks.addBlock('text');
    await expect(edit.blocks.blocks).toHaveCount(2);

    // Every control of the first block is clickable, not clipped off-screen.
    for (const handler of ['moveUp($el)', 'moveDown($el)', 'removeBlock($el)']) {
      const button = edit.blocks.block(0).locator(`button[x-on\\:click="${handler}"]`);
      await expect(button).toBeVisible();
      const box = await button.boundingBox();
      expect(box!.x + box!.width, `${handler} is off-screen at 375px`).toBeLessThanOrEqual(MOBILE.width);
    }

    await edit.blocks.moveDown(0);
    expect(await edit.blocks.order()).toHaveLength(2);
  });
});

