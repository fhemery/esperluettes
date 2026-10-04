import { expect, type Locator, type Page } from '@playwright/test';
import type { Account } from '../support/fixtures';

/**
 * The login form, and whatever the app puts between it and the dashboard.
 *
 * Landing somewhere that is not `/login` does not mean the user is in: a fresh
 * account is parked on the CGU page until it accepts the terms, and most of
 * the app renders fine from that half-authenticated state. So `loginAs` drives
 * all the way through to the dashboard and asserts it got there.
 */
export class LoginPage {
  constructor(private readonly page: Page) {}

  private get email(): Locator {
    return this.page.locator('input[name="email"]');
  }

  private get password(): Locator {
    return this.page.locator('input[name="password"]');
  }

  private get submit(): Locator {
    return this.page.getByTestId('login-form').locator('button[type="submit"]');
  }

  private get acceptTermsCheckbox(): Locator {
    return this.page.getByTestId('accept-terms');
  }

  async goto(): Promise<void> {
    await this.page.goto('/login');
  }

  async loginAs(account: Account): Promise<void> {
    await this.goto();
    await this.email.fill(account.email);
    await this.password.fill(account.password);
    await this.submit.click();
    await this.page.waitForLoadState('networkidle');

    await expect(this.page, `login failed for ${account.email}`).not.toHaveURL(/\/login/);

    await this.acceptTermsIfAsked();

    // The dashboard sits behind the `compliant` middleware, so reaching it is
    // the proof that nothing is still pending. Usually we are already there,
    // having been redirected to the intended URL.
    if (!this.page.url().includes('/dashboard')) {
      await this.page.goto('/dashboard');
    }
    await expect(
      this.page.getByTestId('dashboard'),
      `${account.email} never reached the dashboard (stuck at ${this.page.url()})`,
    ).toBeVisible();
  }

  /**
   * Accept the terms when the app asks. It only asks once per account, and the
   * e2e database is rebuilt per run, so this fires on the first login of a run
   * and is a no-op afterwards.
   */
  async acceptTermsIfAsked(): Promise<void> {
    if (!this.page.url().includes('/compliance/terms')) return;

    await this.acceptTermsCheckbox.check();
    await this.page.getByTestId('accept-terms-submit').click();
    await this.page.waitForLoadState('networkidle');

    await expect(
      this.page,
      'still on the terms page after accepting them',
    ).not.toHaveURL(/\/compliance\/terms/);

    // Parental authorization is the other compliance gate; no seeded account
    // is under 15, so hitting it means the fixtures drifted.
    await expect(
      this.page,
      'unexpected parental-authorization gate — check E2ESeeder',
    ).not.toHaveURL(/\/compliance\/parental/);
  }
}
