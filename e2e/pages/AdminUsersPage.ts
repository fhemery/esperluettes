import { expect, type Locator, type Page } from '@playwright/test';

/**
 * The admin user list. Only the lifecycle actions are modelled — they are the
 * only way to reach "this comment's author was deactivated / deleted" from a
 * browser. Every action sits behind a native `confirm()`.
 */
export class AdminUsersPage {
  constructor(private readonly page: Page) {}

  async gotoSearching(email: string): Promise<void> {
    const url = `/admin/auth/users?search=${encodeURIComponent(email)}`;
    const response = await this.page.goto(url);
    expect(response?.status(), `GET ${url}`).toBe(200);
  }

  row(email: string): Locator {
    return this.page.locator('tbody tr').filter({ hasText: email });
  }

  private async submit(email: string, form: Locator): Promise<void> {
    this.page.once('dialog', (dialog) => dialog.accept());
    await Promise.all([
      this.page.waitForLoadState('load'),
      form.locator('button[type="submit"]').click(),
    ]);
    expect(this.page.url(), `stayed on the form after acting on ${email}`).toContain('/admin/auth/users');
  }

  async deactivate(email: string): Promise<void> {
    await this.gotoSearching(email);
    await this.submit(email, this.row(email).locator('form[action$="/deactivate"]'));
  }

  async reactivate(email: string): Promise<void> {
    await this.gotoSearching(email);
    await this.submit(email, this.row(email).locator('form[action$="/reactivate"]'));
  }

  async destroy(email: string): Promise<void> {
    await this.gotoSearching(email);
    await this.submit(email, this.row(email).locator('form:has(input[name="_method"][value="DELETE"])'));
  }
}
