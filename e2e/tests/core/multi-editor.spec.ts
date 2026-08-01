import { ChapterEditPage } from '../../pages/ChapterEditPage';
import { STORY } from '../../support/fixtures';
import { expect, test } from '../../support/test';

/**
 * CORE — the MultiEdit block editor.
 *
 * `<x-editor::multi>` is shared authoring machinery: News, chapters and static
 * pages all mount the same Alpine component, so a break here breaks authoring
 * on surfaces whose own specs never touch it. It earns a place in core because
 * it is entirely client-side — adding, reordering and deleting a block, and the
 * state resync those must trigger, exist only after Alpine has run and cannot
 * be reached from a PHP request.
 *
 * `chapters-multi-edit/` shipped a regression of exactly this shape (defect
 * D2): `removeBlock()` resolved `$refs.container` from the *calling* element,
 * which is undefined once the block has been detached, so the component
 * stopped syncing its state after any delete. Nothing server-side noticed.
 *
 * What is stored, the mode a form reopens in, the validation rules and the
 * upload scope are all asserted far more cheaply in
 * app/Domains/Story/Tests/Feature/Chapters/ChapterAdvancedModeTest.php.
 *
 * Nothing here saves: the draft chapter is read, converted in the browser and
 * left alone.
 */

const TO_SIMPLE_DISABLED = 'Retirez les images et ne gardez qu’un seul bloc de texte pour revenir en mode Simple.';

test('a block added under Alpine is a working writing surface', async ({ author }) => {
  const edit = new ChapterEditPage(author, STORY.slug, STORY.draftChapter.slug);
  await edit.goto();
  await edit.blocks.goAdvanced();

  await edit.blocks.addBlock('text');

  const added = edit.blocks.textBlockEditor(1);
  await expect(added.body, 'the added block never booted Quill').toBeVisible();
  await expect(added.toolbar.locator('button')).not.toHaveCount(0);

  await added.fill('Second bloc');
  expect(await added.text()).toBe('Second bloc');
});

test('inserting and reordering blocks updates the order that gets submitted', async ({ author }) => {
  const edit = new ChapterEditPage(author, STORY.slug, STORY.draftChapter.slug);
  await edit.goto();
  await edit.blocks.goAdvanced();

  await edit.blocks.textBlockEditor(0).fill('Premier bloc');
  await edit.blocks.insertAfter(0, 'text');
  await edit.blocks.textBlockEditor(1).fill('Second bloc');

  const order = await edit.blocks.order();
  expect(order, 'one uid per block in blocks_order').toHaveLength(2);

  await edit.blocks.moveDown(0);

  expect(await edit.blocks.order(), 'order not resubmitted after a move').toEqual([order[1], order[0]]);
  expect(await edit.blocks.textBlockEditor(0).text()).toBe('Second bloc');
});

test('deleting a block re-syncs the component state', async ({ author }) => {
  const edit = new ChapterEditPage(author, STORY.slug, STORY.draftChapter.slug);
  await edit.goto();
  await edit.blocks.goAdvanced();
  await edit.blocks.textBlockEditor(0).fill('Premier bloc');
  await edit.blocks.addBlock('text');
  await edit.blocks.textBlockEditor(1).fill('Second bloc');
  const order = await edit.blocks.order();
  await expect(edit.blocks.simpleButton, 'two blocks ⇒ Simple is out of reach').toBeDisabled();

  await edit.blocks.removeBlock(1);

  expect(await edit.blocks.order(), 'blocks_order still names the deleted block').toEqual([order[0]]);
  expect(await edit.blocks.textBlockEditor(0).text(), 'the surviving block lost its text').toBe('Premier bloc');
  await expect(
    edit.blocks.simpleButton,
    'one text block left, yet Simple is still disabled — the component never re-synced after the delete',
  ).toBeEnabled();
  await expect(edit.blocks.simpleButton).not.toHaveAttribute('title', TO_SIMPLE_DISABLED);
});
