import { expect, type Page } from '@playwright/test';
import { NEWS } from '../support/fixtures';
import { CommentThread } from './CommentThread';

/** A news article as a reader sees it. */
export class NewsArticlePage {
  readonly comments: CommentThread;

  constructor(
    private readonly page: Page,
    private readonly slug: string = NEWS.slug,
  ) {
    this.comments = new CommentThread(page);
  }

  get path(): string {
    return `/news/${this.slug}`;
  }

  /** Returns the server-rendered HTML, which is what lazy-load claims are made of. */
  async goto(query = ''): Promise<string> {
    const url = this.path + query;
    const response = await this.page.goto(url);
    expect(response?.status(), `GET ${url}`).toBe(200);
    return (await response?.text()) ?? '';
  }
}
