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

  /**
   * The single root every quote anchors against. Advanced content must print
   * inside exactly one of these, not one per block (functional §4.5.1).
   */
  get quoteRoot(): Locator {
    return this.page.locator('[data-quote-article]');
  }

  /** Every paragraph of the body, in document order, blocks or not. */
  get paragraphs(): Locator {
    return this.quoteRoot.locator('p');
  }

  /** The `div.ce-block--text` wrappers advanced content renders. */
  get textBlocks(): Locator {
    return this.quoteRoot.locator('.ce-block--text');
  }

  get images(): Locator {
    return this.quoteRoot.locator('img');
  }

  get captions(): Locator {
    return this.quoteRoot.locator('figcaption');
  }

  get spoilers(): Locator {
    return this.page.locator('.ql-spoiler');
  }

  /** The author's edit affordance, which a reader must never see. */
  get editLink(): Locator {
    return this.page.locator(`a[href$="${this.chapterSlug}/edit"]`);
  }

  async goto(): Promise<void> {
    const response = await this.page.goto(this.path);
    expect(response?.status(), `GET ${this.path}`).toBe(200);
  }

  async text(): Promise<string> {
    return (await this.content.innerText()).trim();
  }

  /**
   * The words/characters pair, read from the metric badge's popover label
   * ("123 mots, 456 SEC*"). The badge itself prints a compacted number, so the
   * label is the only place both values appear.
   */
  async metrics(): Promise<string> {
    return (await this.page.getByText(/ mots, .*SEC/).first().innerText()).trim();
  }

  /** Computed CSS of a paragraph, for the typography comparison. */
  async paragraphStyle(index: number): Promise<Record<string, string>> {
    return this.paragraphs.nth(index).evaluate((el) => {
      const s = getComputedStyle(el);
      return {
        textIndent: s.textIndent,
        paddingBottom: s.paddingBottom,
        marginBottom: s.marginBottom,
        lineHeight: s.lineHeight,
        fontSize: s.fontSize,
        textAlign: s.textAlign,
      };
    });
  }

  /** Vertical gap between the bottom of one paragraph's box and the top of the next. */
  async paragraphGap(index: number): Promise<number> {
    const first = await this.paragraphs.nth(index).boundingBox();
    const second = await this.paragraphs.nth(index + 1).boundingBox();
    expect(first && second, `paragraphs ${index}/${index + 1} are not laid out`).toBeTruthy();
    return Math.round((second!.y - (first!.y + first!.height)) * 100) / 100;
  }

  /** True when the page scrolls sideways — the mobile failure mode. */
  async hasHorizontalScroll(): Promise<boolean> {
    return this.page.evaluate(() => document.documentElement.scrollWidth > document.documentElement.clientWidth + 1);
  }
}
