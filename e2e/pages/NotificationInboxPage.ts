import { type Locator, type Page } from '@playwright/test';

/**
 * `/notifications`: the in-app inbox. Each entry is a `[data-test-id="notif-item"]`
 * whose content is the type's `display()` HTML, rendered unescaped.
 */
export class NotificationInboxPage {
  static readonly PATH = '/notifications';

  constructor(private readonly page: Page) {}

  async goto(): Promise<void> {
    await this.page.goto(NotificationInboxPage.PATH);
  }

  get items(): Locator {
    return this.page.locator('[data-test-id="notif-item"]');
  }

  /** Entries whose text contains `text`. */
  item(text: string | RegExp): Locator {
    return this.items.filter({ hasText: text });
  }

  /** The link inside an entry, by its visible text. */
  link(item: Locator, linkText: string): Locator {
    return item.getByRole('link', { name: linkText });
  }
}
