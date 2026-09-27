import { ChapterCreatePage } from '../../pages/ChapterCreatePage';
import { ChapterEditPage } from '../../pages/ChapterEditPage';
import { ChapterPage } from '../../pages/ChapterPage';
import { QUOTES, STORY } from '../../support/fixtures';
import { expect, test } from '../../support/test';

/**
 * FEATURE — multiedit-chapter-switch-block (BUILD phase 4, extended at VERIFY).
 * Delete at WRAP.
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

// ---------------------------------------------------------------- VERIFY

/** The six chapters of the story, as the dropdown must label them. */
const STORY_TARGETS = [
  STORY.publishedChapter.title,
  STORY.draftChapter.title + NON_PUBLISHED,
  STORY.simpleChapter.title,
  ADVANCED.title,
  STORY.countedChapter.title,
  STORY.illustratedChapter.title,
];

const LONG_CHOICES = [
  { target: STORY.publishedChapter.title, label: 'Ouvrir la lourde porte au fond du couloir sombre' },
  { target: STORY.simpleChapter.title, label: "Redescendre l'escalier en courant sans se retourner" },
  { target: STORY.countedChapter.title, label: '' }, // falls back to the target title
  { target: STORY.draftChapter.title + NON_PUBLISHED, label: 'Vers le brouillon' },
] as const;

test('palette and "+" menu entries carry their icon next to their label', async ({ author }) => {
  const edit = new ChapterEditPage(author, STORY.slug, ADVANCED.slug);
  await edit.goto();

  const icons = { text: /./, image: /./, 'chapter-choice': /^alt_route$/ } as const;
  for (const [type, icon] of Object.entries(icons) as [keyof typeof icons, RegExp][]) {
    const button = edit.blocks.paletteButton(type);
    await expect(button).toBeVisible();
    await expect(button.locator('.material-symbols-outlined')).toHaveText(icon);
  }
  await expect(edit.blocks.paletteButton('chapter-choice')).toContainText('Choix de chapitres');

  await edit.blocks.openInsertMenu(0);
  const entry = edit.blocks.insertMenuButton(0, 'chapter-choice');
  await expect(entry).toBeVisible();
  await expect(entry).toContainText('Choix de chapitres');
  await expect(entry.locator('.material-symbols-outlined')).toHaveText('alt_route');
});

test('choice row controls are labelled and work from the keyboard', async ({ author }) => {
  const edit = new ChapterEditPage(author, STORY.slug, ADVANCED.slug);
  await edit.goto();
  await edit.blocks.addBlock('chapter-choice');
  const block = edit.blocks.choiceBlock(1);
  await block.fill(0, STORY.publishedChapter.title, 'A');
  await block.addChoice();
  await block.fill(1, STORY.simpleChapter.title, 'B');

  for (const control of [block.upButton(0), block.downButton(0), block.removeButton(0)]) {
    await expect(control).toHaveAttribute('aria-label', /\S/);
  }

  // Tab order inside a row: label → « Actif » → up → down.
  await block.label(0).focus();
  await author.keyboard.press('Tab');
  await expect(block.enabled(0)).toBeFocused();
  await author.keyboard.press('Tab');
  await expect(block.upButton(0)).toBeFocused();
  await author.keyboard.press('Tab');
  await expect(block.downButton(0)).toBeFocused();

  await author.keyboard.press('Enter');
  await expect(block.label(0)).toHaveValue('B');
  await expect(block.label(1)).toHaveValue('A');
  // The sibling moved, so the pressed button — now in row 1 — keeps focus.
  await expect(block.downButton(1)).toBeFocused();

  await block.removeButton(1).focus();
  await author.keyboard.press(' ');
  await expect(block.rows).toHaveCount(1);
  await expect(block.label(0)).toHaveValue('B');
});

test('a failed validation re-renders the form with its choice blocks alive', async ({ author }) => {
  const edit = new ChapterEditPage(author, STORY.slug, ADVANCED.slug);
  await edit.goto();
  await edit.blocks.addBlock('chapter-choice');
  const block = edit.blocks.choiceBlock(1);
  const tooLong = 'x'.repeat(121);
  await block.target(0).selectOption({ label: STORY.simpleChapter.title });
  await block.label(0).evaluate(el => el.removeAttribute('maxlength')); // what devtools would do
  await block.label(0).fill(tooLong);

  await edit.saveButton.click();
  await author.waitForLoadState('networkidle');
  await expect(author).toHaveURL(/\/edit$/);
  await edit.blocks.waitUntilReady();

  await expect(edit.blocks.choiceBlocks).toHaveCount(2);
  const rerendered = edit.blocks.choiceBlock(1);
  await expect(rerendered.label(0)).toHaveValue(tooLong);
  await expect(rerendered.target(0).locator('option:checked')).toHaveText(STORY.simpleChapter.title);
  await rerendered.addChoice(); // its Alpine scope booted again
  await expect(edit.blocks.simpleButton).toBeDisabled();
});

test('the create form offers the block, listing every existing chapter', async ({ author }) => {
  const create = new ChapterCreatePage(author);
  await create.goto();
  await create.blocks.goAdvanced();
  await create.blocks.addBlock('chapter-choice');

  const options = await create.blocks.choiceBlock(0).target(0).locator('option').allTextContents();
  expect(options).toEqual(['', ...STORY_TARGETS]);
});

test('at 375px the choice rows stack and the edit form does not scroll sideways', async ({ author }) => {
  await author.setViewportSize({ width: 375, height: 800 });
  const edit = new ChapterEditPage(author, STORY.slug, ADVANCED.slug);
  await edit.goto();
  const block = edit.blocks.choiceBlock(0);
  await block.row(0).scrollIntoViewIfNeeded();

  const select = (await block.target(0).boundingBox())!;
  const label = (await block.label(0).boundingBox())!;
  expect(label.y, 'label input sits below the select').toBeGreaterThanOrEqual(select.y + select.height);
  expect(select.x + select.width).toBeLessThanOrEqual(375);
  expect(label.x + label.width).toBeLessThanOrEqual(375);

  const palette = edit.blocks.paletteButton('chapter-choice');
  await palette.scrollIntoViewIfNeeded();
  const paletteBox = (await palette.boundingBox())!;
  expect(paletteBox.x, 'palette entry left edge').toBeGreaterThanOrEqual(0);
  expect(paletteBox.x + paletteBox.width, 'palette entry right edge').toBeLessThanOrEqual(375);
  const overflow = await author.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
  expect(overflow, 'horizontal overflow in px').toBeLessThanOrEqual(0);
});

test('setup: an author saves long choices and an all-disabled block', async ({ author }) => {
  const edit = new ChapterEditPage(author, STORY.slug, ADVANCED.slug);
  await edit.goto();

  await edit.blocks.addBlock('chapter-choice');
  const long = edit.blocks.choiceBlock(1);
  for (const [i, choice] of LONG_CHOICES.entries()) {
    if (i > 0) await long.addChoice();
    await long.fill(i, choice.target, choice.label);
  }

  await edit.blocks.addBlock('chapter-choice');
  await edit.blocks.choiceBlock(2).fill(0, STORY.publishedChapter.title, 'Jamais visible', false);

  await edit.save();
});

test('a guest sees real buttons, not body text, and follows them', async ({ guest }) => {
  const chapter = new ChapterPage(guest, STORY.slug, ADVANCED.slug);
  await chapter.goto();

  // Premier | the four long ones; the all-disabled block leaves nothing behind.
  await expect(chapter.choiceBlocks).toHaveCount(2);
  await expect(chapter.choiceBlocks.nth(1).locator('a')).toHaveText([
    LONG_CHOICES[0].label,
    LONG_CHOICES[1].label,
    STORY.countedChapter.title,
    LONG_CHOICES[3].label,
  ]);
  await expect(chapter.content.locator('.ce-block:empty')).toHaveCount(0);
  await expect(chapter.content).not.toContainText('Jamais visible');

  const body = await chapter.textBlocks.first().locator('p').first().evaluate(p => {
    const s = getComputedStyle(p);
    return { indent: s.textIndent, size: s.fontSize };
  });
  expect(body.indent, 'control: body paragraphs are indented').not.toBe('0px');

  for (const link of await chapter.choiceLinks.all()) {
    const style = await link.evaluate(a => {
      const s = getComputedStyle(a);
      const w = getComputedStyle(a.parentElement!);
      return {
        underline: s.textDecorationLine,
        indent: w.textIndent,
        display: s.display,
        bg: s.backgroundColor,
        size: s.fontSize,
        radius: s.borderTopLeftRadius,
      };
    });
    expect(style.underline).toBe('none');
    expect(style.indent).toBe('0px');
    expect(style.display).toMatch(/flex$/); // blockified inside the flex wrapper
    expect(style.bg).not.toBe('rgba(0, 0, 0, 0)');
    expect(style.size).not.toBe(body.size);
    expect(style.radius).not.toBe('0px');
  }

  // A choice to an unpublished chapter is a dead link for readers (#9).
  const dead = await chapter.choiceLinks.filter({ hasText: LONG_CHOICES[3].label }).getAttribute('href');
  expect((await guest.request.get(dead!)).status()).toBe(404);

  await chapter.choiceLinks.filter({ hasText: LONG_CHOICES[1].label }).click();
  await expect(guest).toHaveURL(new RegExp(`/chapters/${STORY.simpleChapter.slug}$`));
});

test('the author sees exactly what a guest sees', async ({ author, guest }) => {
  const asAuthor = new ChapterPage(author, STORY.slug, ADVANCED.slug);
  const asGuest = new ChapterPage(guest, STORY.slug, ADVANCED.slug);
  await asAuthor.goto();
  await asGuest.goto();

  const html = (p: ChapterPage) => p.choiceBlocks.evaluateAll(els => els.map(e => e.outerHTML));
  expect(await html(asAuthor)).toEqual(await html(asGuest));
  await expect(asAuthor.content).not.toContainText('non publié');
});

test('a selection dragged from the text onto the buttons offers no « Citer »', async ({ confirmed }) => {
  const chapter = new ChapterPage(confirmed, STORY.slug, ADVANCED.slug);
  await chapter.goto();

  await chapter.selectAcross(chapter.textBlocks.last().locator('p').last(), chapter.choiceLinks.first());
  await expect(chapter.citeButton).toBeHidden();
});

test('button text stays readable in every season, light and dark', async ({ guest }) => {
  const chapter = new ChapterPage(guest, STORY.slug, ADVANCED.slug);
  await chapter.goto();

  const ratios: Record<string, number> = {};
  for (const season of ['autumn', 'winter', 'spring', 'summer']) {
    for (const appearance of ['light', 'dark']) {
      await chapter.setTheme(season, appearance);
      ratios[`${season}/${appearance}`] = await chapter.contrast(chapter.choiceLinks.first());
    }
  }
  console.log('  choice button contrast', JSON.stringify(ratios));
  // The buttons carry <x-shared::button> primary colours on purpose (A13), so
  // they inherit its contrast: 3.1–3.6 in light, 4.4–5.5 in dark — light is below
  // AA's 4.5 for 14px text but above the 3:1 floor. The design-system question
  // goes to the user; this guards the floor so a theme cannot make them unreadable.
  for (const [theme, ratio] of Object.entries(ratios)) {
    expect(ratio, `contrast in ${theme}`).toBeGreaterThanOrEqual(3);
  }
});

test('setup: an author puts quoted words into choice labels above the quotes', async ({ author }) => {
  const edit = new ChapterEditPage(author, STORY.slug, STORY.illustratedChapter.slug);
  await edit.goto();
  await edit.blocks.insertAfter(0, 'chapter-choice');
  const block = edit.blocks.choiceBlock(0);
  await block.fill(0, STORY.publishedChapter.title, QUOTES.belowImagePassage);
  await block.addChoice();
  await block.fill(1, STORY.simpleChapter.title, QUOTES.formattedPassage);
  await edit.save();
});

test('the author heat still tints the quotes, and never a label', async ({ author }) => {
  const chapter = new ChapterPage(author, STORY.slug, STORY.illustratedChapter.slug);
  await chapter.goto();
  await expect(chapter.choiceLinks).toHaveCount(2);

  await chapter.heatToggle.click();
  await expect(chapter.heatMarks.first()).toBeVisible();
  const tinted = (await chapter.heatMarks.allTextContents()).join('|');
  expect(tinted).toContain(QUOTES.belowImagePassage);
  expect(tinted).toContain('italique');
  await expect(chapter.choiceBlocks.locator('mark')).toHaveCount(0);
});

test("a reader's own quote highlight survives, and never lands on a label", async ({ confirmed }) => {
  const chapter = new ChapterPage(confirmed, STORY.slug, STORY.illustratedChapter.slug);
  await chapter.goto();

  await expect(chapter.quoteHighlights.first()).toBeVisible();
  await expect(chapter.textBlocks.first().locator('mark.quote-tint')).not.toHaveCount(0);
  await expect(chapter.choiceBlocks.locator('mark')).toHaveCount(0);
});

// Last on purpose: serial mode skips everything after a failure.
test('at 375px the buttons wrap inside the page and stay tappable', async ({ guest }) => {
  await guest.setViewportSize({ width: 375, height: 800 });
  const chapter = new ChapterPage(guest, STORY.slug, ADVANCED.slug);
  await chapter.goto();
  await chapter.choiceBlocks.nth(1).scrollIntoViewIfNeeded();

  const boxes = await Promise.all((await chapter.choiceBlocks.nth(1).locator('a').all()).map(a => a.boundingBox()));
  for (const box of boxes) {
    expect(box!.x).toBeGreaterThanOrEqual(0);
    expect(box!.x + box!.width).toBeLessThanOrEqual(375);
    expect(box!.height, 'tap target height').toBeGreaterThanOrEqual(32);
  }
  expect(new Set(boxes.map(b => Math.round(b!.y))).size, 'buttons wrap onto several lines').toBeGreaterThan(1);

  const overflow = await guest.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
  expect(overflow, 'horizontal overflow in px').toBeLessThanOrEqual(0);

  // A label that wraps must not inherit the article's justified alignment.
  const aligns = await chapter.choiceLinks.evaluateAll(els => els.map(a => getComputedStyle(a).textAlign));
  expect(aligns.filter(a => a === 'justify'), 'justified button labels').toEqual([]);
});
