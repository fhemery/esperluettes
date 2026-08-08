import type { Locator, Page } from '@playwright/test';

/**
 * Admin panel sidebar. Selectors live here — specs assert against keys from
 * `admin-nav-map.ts`, not CSS.
 */
export class AdminSidebar {
  constructor(private readonly page: Page) {}

  private get links(): Locator {
    return this.page.locator('[data-test-id="admin-sidebar-link"][data-nav-key]');
  }

  async goto(): Promise<void> {
    await this.page.goto('/administration');
    await this.page.waitForLoadState('networkidle');
  }

  /** Unique `data-nav-key` values (mobile + desktop duplicates collapsed). */
  async collectNavKeys(): Promise<string[]> {
    const keys = await this.links.evaluateAll((nodes) =>
      nodes
        .map((n) => n.getAttribute('data-nav-key'))
        .filter((k): k is string => Boolean(k)),
    );
    return [...new Set(keys)].sort();
  }
}
