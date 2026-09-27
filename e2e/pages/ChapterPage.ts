import { expect, type Locator, type Page } from '@playwright/test';
import { STORY } from '../support/fixtures';

/** A chapter as a reader sees it. */
export class ChapterPage {
  constructor(
    private readonly page: Page,
    private readonly storySlug: string = STORY.slug,
    private readonly chapterSlug: string = STORY.publishedChapter.slug,
  ) {}

  get path(): string {
    return `/stories/${this.storySlug}/chapters/${this.chapterSlug}`;
  }

  /** The rendered chapter body. */
  get content(): Locator {
    return this.page.locator('.rich-content, .prose').first();
  }

  get spoilers(): Locator {
    return this.page.locator('.ql-spoiler');
  }

  /** The rendered chapter-choice blocks. */
  get choiceBlocks(): Locator {
    return this.content.locator('.ce-block--chapter-choice');
  }

  /** The links of the chapter-choice blocks. */
  get choiceLinks(): Locator {
    return this.content.locator('.ce-block--chapter-choice a');
  }

  /** The author's heat toggle next to the quote badge (authors only). */
  get heatToggle(): Locator {
    return this.page.locator('[data-quote-author-badge] button[aria-pressed]');
  }

  /** Author heat tints, present once the heat is on. */
  get heatMarks(): Locator {
    return this.content.locator('mark.quote-heat');
  }

  /** A reader's own quote highlights. */
  get quoteHighlights(): Locator {
    return this.content.locator('mark.quote-tint');
  }

  get textBlocks(): Locator {
    return this.content.locator('.ce-block--text');
  }

  /** The selection toolbar, cloned into <body> on first use. */
  get selectionToolbar(): Locator {
    return this.page.locator('#comment-toolbar-active');
  }

  get citeButton(): Locator {
    return this.selectionToolbar.locator('[data-requires-selection-within]');
  }

  /**
   * Selects the first `length` characters of the first text node of `target`
   * (all of it by default) and releases the mouse, which is what the toolbar
   * listens to. Programmatic, so a link can be selected without being followed.
   */
  async selectText(target: Locator, length = Infinity): Promise<void> {
    await target.evaluate((el, max) => {
      const node = document.createTreeWalker(el, NodeFilter.SHOW_TEXT).nextNode() as Text;
      const range = document.createRange();
      range.setStart(node, 0);
      range.setEnd(node, Math.min(max, node.length));
      const selection = window.getSelection()!;
      selection.removeAllRanges();
      selection.addRange(range);
      el.dispatchEvent(new MouseEvent('mouseup', { bubbles: true }));
    }, length);
  }

  /**
   * Selects from the start of `from`'s first text node to the end of `to`'s,
   * then releases the mouse — a drag that starts in one element and ends in
   * another.
   */
  async selectAcross(from: Locator, to: Locator): Promise<void> {
    const end = await to.elementHandle();
    await from.evaluate((start, endEl) => {
      const first = document.createTreeWalker(start, NodeFilter.SHOW_TEXT).nextNode() as Text;
      const last = document.createTreeWalker(endEl!, NodeFilter.SHOW_TEXT).nextNode() as Text;
      const range = document.createRange();
      range.setStart(first, 0);
      range.setEnd(last, last.length);
      const selection = window.getSelection()!;
      selection.removeAllRanges();
      selection.addRange(range);
      endEl!.dispatchEvent(new MouseEvent('mouseup', { bubbles: true }));
    }, end);
  }

  /** Switches the page's season/appearance the way the layout sets them. */
  async setTheme(season: string, appearance: string): Promise<void> {
    await this.page.evaluate(([s, a]) => {
      document.documentElement.dataset.season = s;
      document.documentElement.dataset.appearance = a;
    }, [season, appearance]);
    // Buttons transition their colours: measure the settled state.
    await this.page.evaluate(() => Promise.all(document.getAnimations().map(a => a.finished)));
  }

  /**
   * WCAG contrast ratio between an element's text colour and its background,
   * both resolved to sRGB through a canvas (computed colours may be oklch).
   */
  async contrast(target: Locator): Promise<number> {
    return target.evaluate(el => {
      const s = getComputedStyle(el);
      const ctx = document.createElement('canvas').getContext('2d', { willReadFrequently: true })!;
      const rgb = (color: string) => {
        ctx.clearRect(0, 0, 1, 1);
        ctx.fillStyle = color;
        ctx.fillRect(0, 0, 1, 1);
        return Array.from(ctx.getImageData(0, 0, 1, 1).data.slice(0, 3));
      };
      const lum = (c: number[]) => {
        const [r = 0, g = 0, b = 0] = c.map(v => {
          const x = v / 255;
          return x <= 0.03928 ? x / 12.92 : ((x + 0.055) / 1.055) ** 2.4;
        });
        return 0.2126 * r + 0.7152 * g + 0.0722 * b;
      };
      const fg = lum(rgb(s.color));
      const bg = lum(rgb(s.backgroundColor));
      const ratio = (Math.max(fg, bg) + 0.05) / (Math.min(fg, bg) + 0.05);
      return Math.round(ratio * 100) / 100;
    });
  }

  async goto(): Promise<void> {
    const response = await this.page.goto(this.path);
    expect(response?.status(), `GET ${this.path}`).toBe(200);
  }

  async text(): Promise<string> {
    return (await this.content.innerText()).trim();
  }
}
