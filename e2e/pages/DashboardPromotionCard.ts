import { expect, type Locator, type Page } from '@playwright/test';

/**
 * The promotion card on `/dashboard`, shown to non-confirmed users. Requesting
 * is a plain form POST that redirects back to the dashboard.
 */
export class DashboardPromotionCard {
  constructor(private readonly page: Page) {}

  private get form(): Locator {
    return this.page.locator('form[action$="/dashboard/promotion/request"]');
  }

  get submit(): Locator {
    return this.form.locator('button[type="submit"]');
  }

  async request(): Promise<void> {
    await this.page.goto('/dashboard');
    await expect(this.submit, 'promotion button disabled — check the promotable fixture').toBeEnabled();
    await Promise.all([this.page.waitForURL(/\/dashboard/), this.submit.click()]);
    await this.page.waitForLoadState('networkidle');
    await expect(this.submit, 'request did not reach pending state').toBeDisabled();
  }
}
