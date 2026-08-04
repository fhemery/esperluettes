import { expect, type Locator, type Page } from '@playwright/test';

/** The moderation report list in the admin panel. */
export class ModerationReportsPage {
  constructor(private readonly page: Page) {}

  async goto(topicKey = ''): Promise<void> {
    const url = topicKey
      ? `/admin/moderation/moderation-reports?topic_key=${topicKey}`
      : '/admin/moderation/moderation-reports';
    const response = await this.page.goto(url);
    expect(response?.status(), `GET ${url}`).toBe(200);
  }

  /** The row whose description column carries `description`. */
  rowWithDescription(description: string): Locator {
    return this.page.locator('tbody tr').filter({ hasText: description });
  }

  /** The "open the reported content" link, built from `CommentPolicy::getUrl()`. */
  contentLinkIn(row: Locator): Locator {
    return row.locator('a[target="_blank"]');
  }
}
