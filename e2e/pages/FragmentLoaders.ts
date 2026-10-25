import { type Locator, type Page } from '@playwright/test';

/**
 * The four lazily loaded HTML widgets that load through `window.fetchFragment`:
 * Settings tabs, Story admin moderation chapters, Moderation user-management
 * search and the Profile user-search-input (activation-code create page).
 */
export class FragmentLoaders {
  constructor(private readonly page: Page) {}

  // Settings
  async gotoSettings(): Promise<void> {
    await this.page.goto('/settings');
  }
  get settingsTabs(): Locator {
    return this.page.getByRole('tab');
  }
  get settingsContent(): Locator {
    return this.page.locator('#settings-content');
  }

  // Story admin moderation
  async gotoStoryModeration(): Promise<void> {
    await this.page.goto('/stories/admin/moderation');
  }
  get chaptersToggle(): Locator {
    return this.page.locator('tbody button:has(.material-symbols-outlined:text-is("expand_more"))').first();
  }
  get expandedChapters(): Locator {
    return this.page.locator('tbody tr[class*="bg-surface"]:visible');
  }

  // Moderation user management
  async gotoUserManagement(): Promise<void> {
    await this.page.goto('/admin/moderation/user-management');
  }
  get userSearch(): Locator {
    return this.page.locator('[x-data="userManagement()"] input');
  }
  get userSearchResults(): Locator {
    return this.page.locator('[x-data="userManagement()"] div[x-html="resultsHtml"]');
  }

  // Profile user-search-input
  async gotoActivationCodeCreate(): Promise<void> {
    await this.page.goto('/admin/auth/activation-codes/create');
  }
  get sponsorInput(): Locator {
    return this.page.locator('form input[type="text"]').first();
  }
  get sponsorResults(): Locator {
    return this.page.locator('form [data-user-id]');
  }

  /** Tag the current document so a later reload can be detected (the tag vanishes). */
  async tagDocument(): Promise<void> {
    await this.page.evaluate(() => { (window as unknown as { __tag: boolean }).__tag = true; });
  }
  async isDocumentStillTagged(): Promise<boolean> {
    return this.page.evaluate(() => (window as unknown as { __tag?: boolean }).__tag === true);
  }

  /** Answer requests matching `pattern` with a redirect (what `compliant` does when the CGU are not accepted). */
  async redirectFragmentsToTerms(pattern: RegExp): Promise<void> {
    await this.page.route(pattern, (route) =>
      route.fulfill({ status: 302, headers: { Location: '/terms' } }));
  }

  /** Answer requests matching `pattern` with 419 (expired session / CSRF). */
  async expireFragments(pattern: RegExp): Promise<void> {
    await this.page.route(pattern, (route) => route.fulfill({ status: 419, body: 'Page Expired' }));
  }
}
