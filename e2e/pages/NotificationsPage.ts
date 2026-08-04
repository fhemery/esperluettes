import { expect, type Locator, type Page } from '@playwright/test';

/** The bell in the top bar and the `/notifications` list behind it. */
export class NotificationsPage {
  constructor(private readonly page: Page) {}

  /** Only rendered when there is at least one unread notification. */
  get unreadBadge(): Locator {
    return this.page.locator('[data-test-id="unread-badge"]');
  }

  get items(): Locator {
    return this.page.locator('[data-test-id="notif-item"]');
  }

  itemsContaining(text: string): Locator {
    return this.items.filter({ hasText: text });
  }

  async goto(): Promise<void> {
    const response = await this.page.goto('/notifications');
    expect(response?.status(), 'GET /notifications').toBe(200);
  }
}

/** Settings → Notifications: one row per notification type, grouped. */
export class NotificationSettingsPage {
  constructor(private readonly page: Page) {}

  /**
   * `/settings/{tab}` returns only the tab *fragment* — it is what the tab
   * strip fetches. The page a user actually sees is `/settings?tab=…`.
   */
  async goto(): Promise<void> {
    const response = await this.page.goto('/settings?tab=notification');
    expect(response?.status(), 'GET /settings?tab=notification').toBe(200);
  }

  /** A group header row, e.g. "Commentaires d'actualités". */
  groupHeader(label: string): Locator {
    return this.page.locator('tbody tr').filter({ has: this.page.getByRole('heading', { name: label, exact: true }) });
  }

  /**
   * The website checkbox for a notification type. It is `sr-only`, so it can be
   * asserted on but never clicked — click `toggleLabel()` instead.
   */
  websiteToggle(type: string): Locator {
    return this.page.locator(`input[name="prefs[${type}][website]"][type="checkbox"]`);
  }

  toggleLabel(type: string): Locator {
    return this.page.locator(`label:has(input[name="prefs[${type}][website]"][type="checkbox"])`);
  }

  /** The row holding a type's label and its toggles. */
  row(type: string): Locator {
    return this.page.locator('tbody tr').filter({ has: this.websiteToggle(type) });
  }

  async save(): Promise<void> {
    await Promise.all([
      this.page.waitForLoadState('load'),
      this.page.locator('form[action$="/notifications/preferences"] button[type="submit"]').click(),
    ]);
  }
}
