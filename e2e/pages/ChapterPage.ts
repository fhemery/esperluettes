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

  async goto(): Promise<void> {
    const response = await this.page.goto(this.path);
    expect(response?.status(), `GET ${this.path}`).toBe(200);
  }

  async text(): Promise<string> {
    return (await this.content.innerText()).trim();
  }
}
