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
   * Selects the first occurrence of `passage` inside one text node of the
   * chapter and releases the mouse, which is what the toolbar listens to.
   */
  async selectPassage(passage: string): Promise<void> {
    await this.content.evaluate((el, text) => {
      const walker = document.createTreeWalker(el, NodeFilter.SHOW_TEXT);
      let node: Text | null;
      while ((node = walker.nextNode() as Text | null)) {
        const at = node.data.indexOf(text);
        if (at === -1) continue;
        const range = document.createRange();
        range.setStart(node, at);
        range.setEnd(node, at + text.length);
        const selection = window.getSelection()!;
        selection.removeAllRanges();
        selection.addRange(range);
        node.parentElement!.dispatchEvent(new MouseEvent('mouseup', { bubbles: true }));
        return;
      }
      throw new Error(`« ${text} » is not inside one text node of the chapter`);
    }, passage);
  }

  /** Images of the chapter body. */
  get images(): Locator {
    return this.content.locator('img');
  }

  /** Resolves once every chapter image has loaded for real (not just failed). */
  async waitForImages(): Promise<void> {
    await expect.poll(() => this.images.evaluateAll((imgs) =>
      imgs.every((img) => (img as HTMLImageElement).complete && (img as HTMLImageElement).naturalWidth > 0)),
    ).toBe(true);
  }

  /** The reader's quote panel, opened by clicking their yellow mark. */
  get quotePanel(): Locator {
    return this.page.getByRole('dialog', { name: 'Ma citation' });
  }

  /** « Citer » in the selection toolbar. */
  get quoteButton(): Locator {
    return this.selectionToolbar.getByRole('button', { name: /Citer/ });
  }

  /** The first `<x-shared::popover>` of the page (a metric badge of the header). */
  private get headerPopover(): Locator {
    return this.page.locator('[x-data="popover"]').first();
  }

  /** Pins it open the way a press on its trigger does. */
  async openHeaderPopover(): Promise<void> {
    await this.headerPopover.locator('[role="button"]').first().dispatchEvent('mousedown');
  }

  /** Whether it is open, read from its Alpine state (its panel is teleported). */
  async headerPopoverOpen(): Promise<boolean> {
    return this.headerPopover.evaluate((el) => {
      const data = (window as unknown as { Alpine: { $data: (el: Element) => { pinned: boolean; hoverOpen: boolean } } })
        .Alpine.$data(el);
      return data.pinned || data.hoverOpen;
    });
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

  async goto(): Promise<void> {
    const response = await this.page.goto(this.path);
    expect(response?.status(), `GET ${this.path}`).toBe(200);
  }

  async text(): Promise<string> {
    return (await this.content.innerText()).trim();
  }
}
