import { ChapterEditPage } from '../../pages/ChapterEditPage';
import { ChapterPage } from '../../pages/ChapterPage';
import { STORY } from '../../support/fixtures';
import { expect, test } from '../../support/test';

/**
 * FEATURE — multiedit-chapter-switch-block (BUILD phase 4). Delete at WRAP.
 *
 * The chapter-choice block's editor is client-side: a template clone with a
 * fresh uid, rows added / moved / removed by a local Alpine scope that
 * re-indexes field names, and the Simple switch refusing a non-text block. The
 * reader side checks the stored buttons navigate and are not quotable, which
 * only the toolbar script decides.
 *
 * Serial: the save test writes the choice block the reader tests then read.
 */

test.describe.configure({ mode: 'serial' });

const ADVANCED = STORY.advancedChapter;
const NON_PUBLISHED = ' (non publié)';

test('the palette and the "+" menu insert working choice blocks with fresh uids', async ({ author }) => {
  const edit = new ChapterEditPage(author, STORY.slug, ADVANCED.slug);
  await edit.goto();
  const blocks = await edit.blocks.blocks.count();

  await edit.blocks.addBlock('chapter-choice');
  await edit.blocks.insertAfter(0, 'chapter-choice');

  await expect(edit.blocks.block(1)).toHaveAttribute('data-type', 'chapter-choice');
  await expect(edit.blocks.block(blocks + 1)).toHaveAttribute('data-type', 'chapter-choice');
  const inserted = edit.blocks.choiceBlock(0);
  const appended = edit.blocks.choiceBlock(1);
  const [insertedUid, appendedUid] = [await inserted.uid(), await appended.uid()];
  expect(insertedUid).not.toBe(appendedUid);
  expect(insertedUid).not.toContain('__UID__');

  // One empty, enabled choice to start with, named after the block's own uid.
  await expect(appended.rows).toHaveCount(1);
  await expect(appended.target(0)).toHaveValue('');
  await expect(appended.enabled(0)).toBeChecked();
  expect(await appended.names(0)).toEqual([
    `blocks[${appendedUid}][choices][0][chapter_id]`,
    `blocks[${appendedUid}][choices][0][label]`,
    `blocks[${appendedUid}][choices][0][enabled]`,
    `blocks[${appendedUid}][choices][0][enabled]`,
  ]);

  // Each block's Alpine scope is its own.
  await appended.addChoice();
  await expect(inserted.rows).toHaveCount(1);
  expect(await edit.blocks.order()).toContain(appendedUid);

  // Every story chapter is offered, drafts marked, the current one included.
  const options = await appended.target(0).locator('option').allTextContents();
  expect(options).toContain(STORY.publishedChapter.title);
  expect(options).toContain(STORY.draftChapter.title + NON_PUBLISHED);
  expect(options).toContain(ADVANCED.title);
});

test('« Simple » is disabled while a choice block exists', async ({ author }) => {
  const edit = new ChapterEditPage(author, STORY.slug, STORY.draftChapter.slug);
  await edit.goto();
  await edit.blocks.goAdvanced();
  await expect(edit.blocks.simpleButton).toBeEnabled();

  await edit.blocks.addBlock('chapter-choice');
  await expect(edit.blocks.simpleButton).toBeDisabled();

  await edit.blocks.removeBlock(1);
  await expect(edit.blocks.simpleButton).toBeEnabled();
});

test('choices added, reordered, disabled and removed survive a save', async ({ author }) => {
  const edit = new ChapterEditPage(author, STORY.slug, ADVANCED.slug);
  await edit.goto();
  await edit.blocks.addBlock('chapter-choice');
  const block = edit.blocks.choiceBlock(0);

  await block.fill(0, STORY.publishedChapter.title, 'Premier');
  await block.addChoice();
  await block.fill(1, STORY.draftChapter.title + NON_PUBLISHED, 'Deuxième', false);
  await block.addChoice();
  await block.fill(2, STORY.simpleChapter.title, 'Troisième');

  await block.moveDown(0); // Deuxième, Premier, Troisième
  await block.remove(2); // Deuxième, Premier
  await block.moveUp(1); // Premier, Deuxième
  await block.moveDown(0); // Deuxième, Premier

  const uid = await block.uid();
  expect(await block.names(1)).toContain(`blocks[${uid}][choices][1][label]`);

  await edit.save();

  await edit.goto();
  const reopened = edit.blocks.choiceBlock(0);
  await expect(reopened.rows).toHaveCount(2);
  await expect(reopened.label(0)).toHaveValue('Deuxième');
  await expect(reopened.target(0).locator('option:checked')).toHaveText(STORY.draftChapter.title + NON_PUBLISHED);
  await expect(reopened.enabled(0)).not.toBeChecked();
  await expect(reopened.label(1)).toHaveValue('Premier');
  await expect(reopened.target(1).locator('option:checked')).toHaveText(STORY.publishedChapter.title);
  await expect(reopened.enabled(1)).toBeChecked();
});

test('a reader sees the enabled choice only, cannot quote it, and follows it', async ({ confirmed }) => {
  const chapter = new ChapterPage(confirmed, STORY.slug, ADVANCED.slug);
  await chapter.goto();

  await expect(chapter.choiceLinks).toHaveCount(1);
  await expect(chapter.choiceLinks.first()).toHaveText('Premier');

  // Control: a few words of a text block do offer « Citer ».
  await chapter.selectText(chapter.textBlocks.first().locator('p').first(), 12);
  await expect(chapter.citeButton).toBeVisible();

  await chapter.selectText(chapter.choiceLinks.first());
  await expect(chapter.citeButton).toBeHidden();
  await expect(chapter.selectionToolbar).toBeHidden();

  await confirmed.evaluate(() => window.getSelection()?.removeAllRanges());
  await chapter.choiceLinks.first().click();
  await expect(confirmed).toHaveURL(new RegExp(`/chapters/${STORY.publishedChapter.slug}$`));
});
