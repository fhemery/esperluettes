import { expect, type Locator, type Page } from '@playwright/test';

/**
 * `<x-moderation::report-button>` and the modal it fetches.
 *
 * Clicking the flag fetches the form over AJAX, injects it and lets Alpine
 * open it; submitting posts JSON and swaps the form for a success message. So
 * a report is only filed once that message is visible — not when the click
 * returns.
 */
export class ReportButton {
  constructor(private readonly page: Page) {}

  private get root(): Locator {
    return this.page.locator('[x-data^="reportButton"]').first();
  }

  private get modal(): Locator {
    return this.page.locator('[x-data^="reportForm"]');
  }

  /** Files a report with the first available reason. */
  async report(description: string): Promise<void> {
    await this.root.locator('button').first().click();

    const reason = this.modal.locator('#reason_id');
    await expect(reason, 'report form never loaded').toBeVisible();
    const firstReason = await reason.locator('option:not([value=""])').first().getAttribute('value');
    await reason.selectOption(firstReason!);
    await this.modal.locator('#description').fill(description);

    await this.modal.locator('form button').last().click();
    await expect(
      this.modal.locator('[x-show="submitted"]'),
      'report was not accepted',
    ).toBeVisible();
  }
}
