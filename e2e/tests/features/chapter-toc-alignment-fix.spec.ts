/**
 * Feature spec — chapter table of contents alignment fix.
 *
 * The fix is a few pixels of vertical offset: the chapter-info cell of both
 * chapter lists got `justify-center`, and the reader list's title anchor lost
 * `flex-1`. Nothing about it is visible to a PHP feature test, which sees
 * class strings and no layout, so the assertions here are geometric: a title
 * is "centred" when its own box shares a centre line with the grid cell that
 * holds it and with the other cells of the same row.
 *
 * Temporary: delete at WRAP unless promoted, see tests/features/README.md.
 */
import { expect, test } from '../../support/test';
import { StoryTocPage, type TocRow } from '../../pages/StoryTocPage';
import { COAUTHORED_STORY, EMPTY_STORY, STORY } from '../../support/fixtures';

/** Sub-pixel layout noise; anything above this is a visible offset. */
const TOLERANCE = 1.5;

const MOBILE = { width: 375, height: 800 };

function expectCentred(row: TocRow): void {
  // Proves the measurement really is the `sm`+ layout: below `sm` the cell
  // also holds the date + badges strip and nothing is centred.
  expect(row.badges, `"${row.text}" is being measured in the mobile layout`).toBeNull();

  // `glyphs`, not `title`: the pre-fix reader anchor was a perfectly centred
  // box with its text pinned to the top of it.
  expect(
    Math.abs(row.glyphs.centreY - row.cell.centreY),
    `"${row.text}" is off the centre of its cell`,
  ).toBeLessThanOrEqual(TOLERANCE);

  for (const centre of row.neighbourCentres) {
    expect(
      Math.abs(row.glyphs.centreY - centre),
      `"${row.text}" is off the centre of a neighbouring cell`,
    ).toBeLessThanOrEqual(TOLERANCE);
  }
}

function expectLeftAligned(row: TocRow): void {
  // The cell's own p-2 is 8px; anything more means the title got centred or
  // shrink-wrapped horizontally, which is the regression `items-center` would
  // have introduced.
  expect(row.glyphs.left - row.cell.left, `"${row.text}" is not left-aligned`).toBeLessThanOrEqual(9);
}

/** The `< sm` layout: title on line 1, date + badges on line 2 below it. */
function expectStacked(row: TocRow): void {
  expectLeftAligned(row);
  expect(row.titleLines, `"${row.text}" wrapped`).toBe(1);
  expect(row.badges, `"${row.text}" has no mobile badge strip`).not.toBeNull();
  expect(
    row.badges!.top,
    `"${row.text}" and its badges are on the same line`,
  ).toBeGreaterThanOrEqual(row.glyphs.bottom - TOLERANCE);
}

test.describe('author view', () => {
  test('every chapter title is centred in its row, level with its neighbours', async ({ author }) => {
    const toc = new StoryTocPage(author);
    await toc.goto();

    const rows = await toc.measure();
    expect(rows.length).toBeGreaterThan(3);

    // Word counts differ from one chapter to the next, so the badge cells are
    // not all the same width: this is also the "mixed row heights" check.
    for (const row of rows) {
      expectCentred(row);
      expectLeftAligned(row);
      expect(row.titleLines, `"${row.text}" wrapped`).toBe(1);
    }
  });

  test('a title wider than its column is truncated without growing the row', async ({ author }) => {
    const toc = new StoryTocPage(author);
    await toc.goto();

    const rows = await toc.measure();
    const long = rows.find((r) => r.href.endsWith(STORY.longTitleChapter.slug));
    expect(long, 'the long-title chapter is missing from the list').toBeDefined();

    expect(long!.overflows, 'the long title did not overflow — the fixture is too short').toBe(true);
    expect(long!.textOverflow).toBe('ellipsis');
    expect(long!.titleLines).toBe(1);

    const others = rows.filter((r) => r !== long).map((r) => r.cell.height);
    expect(Math.max(...others) - Math.min(...others)).toBeLessThanOrEqual(TOLERANCE);
    expect(Math.abs(long!.cell.height - others[0])).toBeLessThanOrEqual(TOLERANCE);
  });

  test('the unpublished markers stay inline with the title and still open', async ({ author }) => {
    const toc = new StoryTocPage(author);
    await toc.goto();

    const cell = toc.infoCell(STORY.draftChapter.slug);
    const title = cell.locator('a').first();
    const marker = cell.getByText('visibility_off');

    const titleBox = (await title.boundingBox())!;
    const markerBox = (await marker.boundingBox())!;
    expect(
      Math.abs(titleBox.y + titleBox.height / 2 - (markerBox.y + markerBox.height / 2)),
    ).toBeLessThanOrEqual(3);

    await marker.hover();
    // <x-shared::popover> teleports its panel to <body>, so it cannot be
    // scoped to the row that opened it.
    const panel = author.locator('body > div[role="dialog"]').filter({ visible: true });
    await expect(panel).toContainText('publié');
  });

  test('below sm the title and the badges stay on two separate lines', async ({ author }) => {
    await author.setViewportSize(MOBILE);
    const toc = new StoryTocPage(author);
    await toc.goto();

    const rows = await toc.measure();
    expect(rows.length).toBeGreaterThan(3);

    for (const row of rows) {
      expectStacked(row);
    }
  });
});

test.describe('reader view', () => {
  test('a logged-in reader sees every title centred, level with the toggle and the date', async ({
    confirmed,
  }) => {
    const toc = new StoryTocPage(confirmed);
    await toc.goto();

    const rows = await toc.measure();
    expect(rows.length).toBeGreaterThan(3);

    for (const row of rows) {
      expectCentred(row);
      expectLeftAligned(row);
      expect(row.titleLines, `"${row.text}" wrapped`).toBe(1);
    }
  });

  test('a guest, with no read-toggle column, sees the same centring', async ({ guest }) => {
    const toc = new StoryTocPage(guest);
    await toc.goto();

    const rows = await toc.measure();
    expect(rows.length).toBeGreaterThan(3);

    for (const row of rows) {
      expectCentred(row);
      expectLeftAligned(row);
    }
  });

  test('a title wider than its column is truncated without growing the row', async ({ confirmed }) => {
    const toc = new StoryTocPage(confirmed);
    await toc.goto();

    const rows = await toc.measure();
    const long = rows.find((r) => r.href.endsWith(STORY.longTitleChapter.slug))!;

    expect(long, 'the long-title chapter is missing from the list').toBeDefined();
    expect(long.overflows, 'the long title did not overflow — the fixture is too short').toBe(true);
    expect(long.textOverflow).toBe('ellipsis');
    expect(long.titleLines).toBe(1);

    const heights = rows.map((r) => r.cell.height);
    expect(Math.max(...heights) - Math.min(...heights)).toBeLessThanOrEqual(TOLERANCE);
  });

  test('the read toggle still works and swaps its icon without moving the row', async ({ confirmed }) => {
    const toc = new StoryTocPage(confirmed);
    await toc.goto();

    const slug = STORY.publishedChapter.slug;
    await expect(toc.readIcon(slug, 'unread')).toBeVisible();

    const before = (await toc.measure()).find((r) => r.href.endsWith(slug))!;

    await toc.readToggle(slug).click();
    await expect(toc.readIcon(slug, 'read')).toBeVisible();

    const after = (await toc.measure()).find((r) => r.href.endsWith(slug))!;
    expect(Math.abs(after.glyphs.top - before.glyphs.top)).toBeLessThanOrEqual(TOLERANCE);
    expect(Math.abs(after.cell.height - before.cell.height)).toBeLessThanOrEqual(TOLERANCE);
    expectCentred(after);

    // And it really reached the server, rather than only flipping Alpine state.
    await confirmed.reload();
    await expect(toc.readIcon(slug, 'read')).toBeVisible();

    await toc.readToggle(slug).click();
    await expect(toc.readIcon(slug, 'unread')).toBeVisible();
  });

  test('below sm the title and the badges stay on two separate lines', async ({ confirmed }) => {
    await confirmed.setViewportSize(MOBILE);
    const toc = new StoryTocPage(confirmed);
    await toc.goto();

    const rows = await toc.measure();
    expect(rows.length).toBeGreaterThan(3);

    for (const row of rows) {
      expectStacked(row);
    }
  });

  test('a story with no chapter still renders the empty message', async ({ guest }) => {
    const toc = new StoryTocPage(guest, EMPTY_STORY.slug);
    await toc.goto();

    await expect(toc.emptyMessage).toBeVisible();
    await expect(toc.list).toHaveCount(0);
  });
});

test.describe('a story with a single chapter', () => {
  test('its only row is centred for the author', async ({ author }) => {
    const toc = new StoryTocPage(author, COAUTHORED_STORY.slug);
    await toc.goto();

    const rows = await toc.measure();
    expect(rows).toHaveLength(1);
    expectCentred(rows[0]);
    expectLeftAligned(rows[0]);
  });

  test('its only row is centred for a reader', async ({ user }) => {
    const toc = new StoryTocPage(user, COAUTHORED_STORY.slug);
    await toc.goto();

    const rows = await toc.measure();
    expect(rows).toHaveLength(1);
    expectCentred(rows[0]);
    expectLeftAligned(rows[0]);
  });
});
