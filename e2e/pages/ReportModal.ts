import { expect, type Locator, type Page } from '@playwright/test';

/**
 * The AJAX-loaded `<x-moderation::report-button>` modal.
 *
 * `<x-shared::modal>` renders no `role="dialog"`, so the handle is its
 * positioning class filtered to the visible one.
 */
export class ReportModal {
  constructor(private readonly page: Page) {}

  get root(): Locator {
    return this.page.locator('.fixed.inset-0.overflow-y-auto').locator('visible=true').first();
  }

  get title(): Locator {
    return this.root.getByRole('heading', { name: 'Signaler ce contenu' });
  }

  get reason(): Locator {
    return this.root.locator('select#reason_id');
  }

  get description(): Locator {
    return this.root.locator('textarea#description');
  }

  get submit(): Locator {
    return this.root.getByText('Envoyer le signalement');
  }

  get successMessage(): Locator {
    return this.root.getByText('Votre signalement a été envoyé avec succès. Merci de votre aide.');
  }

  async reasonLabels(): Promise<string[]> {
    return this.reason.locator('option').allInnerTexts();
  }

  async fileReport(reasonLabel: string, description: string): Promise<void> {
    await expect(this.title).toBeVisible();
    await this.reason.selectOption({ label: reasonLabel });
    await this.description.fill(description);
    await this.submit.click();
    await expect(this.successMessage).toBeVisible();
  }
}
