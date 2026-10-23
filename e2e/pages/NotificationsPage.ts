import { type Locator, type Page } from '@playwright/test';

/** The full notifications list. */
export class NotificationsPage {
  constructor(private readonly page: Page) {}

  async goto(): Promise<void> {
    await this.page.goto('/notifications');
  }

  get items(): Locator {
    return this.page.locator('[data-test-id="notif-item"]');
  }

  /** The item whose text contains the given fragment. */
  item(text: string): Locator {
    return this.items.filter({ hasText: text });
  }

  async shot(name: string): Promise<void> {
    const dir = process.env.E2E_SHOTS_DIR;
    if (dir) await this.page.screenshot({ path: `${dir}/${name}.png`, fullPage: true });
  }
}
