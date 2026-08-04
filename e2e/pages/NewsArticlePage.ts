import { expect, type Locator, type Page } from '@playwright/test';
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

  /** The `<section id="comments">` wrapper the article adds around the thread. */
  get section(): Locator {
    return this.page.locator('#comments');
  }

  get body(): Locator {
    return this.page.locator('.news-content');
  }

  async goto(query = ''): Promise<string> {
    const url = this.path + query;
    const response = await this.page.goto(url);
    expect(response?.status(), `GET ${url}`).toBe(200);
    return (await response?.text()) ?? '';
  }

  /** Navigate expecting the article to be gone or hidden. */
  async gotoExpecting404(): Promise<void> {
    const response = await this.page.goto(this.path);
    expect(response?.status(), `GET ${this.path}`).toBe(404);
  }
}

/** The admin news list, for the publish/delete controls. */
export class NewsAdminPage {
  constructor(private readonly page: Page) {}

  async goto(): Promise<void> {
    const response = await this.page.goto('/admin/news');
    expect(response?.status(), 'GET /admin/news').toBe(200);
  }

  row(title: string): Locator {
    return this.page.locator('tr').filter({ hasText: title });
  }

  /** Delete is a form button behind a native `confirm()`. */
  async delete(title: string): Promise<void> {
    this.page.once('dialog', (dialog) => dialog.accept());
    await Promise.all([
      this.page.waitForLoadState('load'),
      this.row(title)
        .locator('form:has(input[name="_method"][value="DELETE"]) button[type="submit"]')
        .click(),
    ]);
  }
}
