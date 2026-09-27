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

  /** The links of the chapter-choice blocks. */
  get choiceLinks(): Locator {
    return this.content.locator('.ce-block--chapter-choice a');
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

  async goto(): Promise<void> {
    const response = await this.page.goto(this.path);
    expect(response?.status(), `GET ${this.path}`).toBe(200);
  }

  async text(): Promise<string> {
    return (await this.content.innerText()).trim();
  }
}
