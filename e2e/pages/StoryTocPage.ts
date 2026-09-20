import { expect, type Locator, type Page } from '@playwright/test';
import { STORY } from '../support/fixtures';

export interface Box {
  readonly top: number;
  readonly bottom: number;
  readonly left: number;
  readonly width: number;
  readonly height: number;
  readonly centreY: number;
}

/**
 * One chapter row of the table of contents, measured after layout.
 *
 * The whole point of this feature is a handful of vertical pixels, so the row
 * is described by geometry rather than by markup: `cell` is the chapter-info
 * grid item (it stretches to the full row height), `title` is the anchor
 * inside it, and `neighbours` are the other cells of the same row — the ones
 * the title has to line up with.
 */
export interface TocRow {
  readonly href: string;
  readonly text: string;
  readonly cell: Box;
  /** The anchor's own border box. */
  readonly title: Box;
  /**
   * The line boxes the title *glyphs* really occupy.
   *
   * This, not `title`, is what the fix is about: before it, the reader list's
   * anchor carried `flex-1` and grew to the full row height, so its box was
   * perfectly centred while the text inside it sat at the top. Asserting on
   * `title` there passes against the bug.
   */
  readonly glyphs: Box;
  /** Line boxes the title text actually occupies: 1 means it did not wrap. */
  readonly titleLines: number;
  /** The anchor overflows its box, so `truncate` has something to do. */
  readonly overflows: boolean;
  readonly textOverflow: string;
  /** The date + badges strip that only the `< sm` layout shows, or `null`. */
  readonly badges: Box | null;
  /** Centres of every other visible cell in the same grid row. */
  readonly neighbourCentres: number[];
}

/**
 * A story's table of contents (`/stories/{slug}`).
 *
 * Selectors live here. The chapter list is the only `div.grid` in the page
 * that holds chapter links — the author's reorder view is a `ul` — so the two
 * lists (author and reader) are reachable through the same handle and the
 * measurements below apply unchanged to both.
 */
export class StoryTocPage {
  constructor(
    private readonly page: Page,
    private readonly storySlug: string = STORY.slug,
  ) {}

  get url(): string {
    return `/stories/${this.storySlug}`;
  }

  async goto(): Promise<void> {
    await this.page.goto(this.url);
    await expect(this.page.locator('section').last()).toBeVisible();
  }

  get list(): Locator {
    return this.page.locator('div.grid').filter({ has: this.page.locator('a[href*="/chapters/"]') });
  }

  /** The "Aucun chapitre disponible pour le moment." paragraph. */
  get emptyMessage(): Locator {
    return this.page.getByText('Aucun chapitre disponible pour le moment.');
  }

  /** The chapter-info cell of one row, addressed by the chapter it links to. */
  infoCell(chapterSlug: string): Locator {
    // The author list nests the anchor one level deeper than the reader list,
    // so this cannot be a direct-child selector.
    return this.page.locator(`div.grid > div:has(a[href$="/chapters/${chapterSlug}"])`);
  }

  /**
   * The read toggle of one row. It is the grid cell rendered just before the
   * chapter-info cell, which is the only way to tie a toggle to its chapter:
   * the toggle markup itself carries no chapter identity.
   */
  readToggle(chapterSlug: string): Locator {
    return this.infoCell(chapterSlug)
      .locator('xpath=preceding-sibling::div[1]')
      .locator('button.read-toggle');
  }

  readIcon(chapterSlug: string, state: 'read' | 'unread'): Locator {
    return this.readToggle(chapterSlug).locator(`[data-test-id="read-toggle-icon-${state}"]`);
  }

  editLink(chapterSlug: string): Locator {
    return this.page.locator(`a[href$="/chapters/${chapterSlug}/edit"]`);
  }

  /**
   * Measure every visible chapter row in one layout pass.
   *
   * `neighbourCentres` is computed by grouping the grid's direct children by
   * their top edge, which is what a CSS grid row is once it is laid out — it
   * does not assume how many columns the view renders, so guest (no read
   * toggle) and author (two extra action cells) are measured the same way.
   */
  async measure(): Promise<TocRow[]> {
    const rows = await this.page.evaluate(() => {
      const box = (r: DOMRect) => ({
        top: r.top,
        bottom: r.bottom,
        left: r.left,
        width: r.width,
        height: r.height,
        centreY: r.top + r.height / 2,
      });

      const grids = Array.from(document.querySelectorAll('div.grid')).filter(
        (g) => g.querySelector('a[href*="/chapters/"]') && (g as HTMLElement).offsetParent !== null,
      );
      if (grids.length !== 1) {
        throw new Error(`expected exactly one visible chapter grid, found ${grids.length}`);
      }
      const grid = grids[0] as HTMLElement;

      const cells = Array.from(grid.children).filter(
        (c) => (c as HTMLElement).offsetParent !== null && c.getBoundingClientRect().height > 0,
      ) as HTMLElement[];

      const titles = Array.from(
        grid.querySelectorAll('a[href*="/chapters/"].font-semibold'),
      ) as HTMLAnchorElement[];

      return titles
        .filter((a) => a.offsetParent !== null)
        .map((a) => {
          const cell = a.closest('.surface-read') as HTMLElement;
          const cellRect = cell.getBoundingClientRect();

          // Line boxes the text really occupies — 1 unless the title wrapped.
          const range = document.createRange();
          range.selectNodeContents(a);
          const lineRects = Array.from(range.getClientRects());
          const lines = new Set(lineRects.map((r) => Math.round(r.top))).size;

          const top = Math.min(...lineRects.map((r) => r.top));
          const bottom = Math.max(...lineRects.map((r) => r.bottom));
          const glyphs = {
            top,
            bottom,
            left: Math.min(...lineRects.map((r) => r.left)),
            width: Math.max(...lineRects.map((r) => r.right)) - Math.min(...lineRects.map((r) => r.left)),
            height: bottom - top,
            centreY: (top + bottom) / 2,
          };

          const badges = cell.querySelector('div[class*="sm:hidden"]') as HTMLElement | null;

          const sameRow = cells.filter(
            (c) => c !== cell && Math.abs(c.getBoundingClientRect().top - cellRect.top) < 2,
          );

          return {
            href: a.getAttribute('href') ?? '',
            text: (a.textContent ?? '').trim(),
            cell: box(cellRect),
            title: box(a.getBoundingClientRect()),
            glyphs,
            titleLines: lines,
            overflows: a.scrollWidth > a.clientWidth + 1,
            textOverflow: getComputedStyle(a).textOverflow,
            badges: badges && badges.offsetParent !== null ? box(badges.getBoundingClientRect()) : null,
            neighbourCentres: sameRow.map((c) => {
              const r = c.getBoundingClientRect();
              return r.top + r.height / 2;
            }),
          };
        });
    });

    return rows as TocRow[];
  }
}
