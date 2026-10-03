import { expect, type Locator, type Page } from '@playwright/test';
import { RichTextEditor } from './RichTextEditor';

/**
 * Component object for chapter annotations: the « Annoter » toolbar action,
 * the capture form, the drafts banner and pop-up above the root comment form,
 * and the « N annotations » server pop-up on a published root comment.
 *
 * The capture form and both pop-ups are teleported or fixed overlays, so they
 * are anchored on the page, not inside `#comment-list`.
 */
export class ChapterAnnotations {
  constructor(private readonly page: Page) {}

  /** « Annoter » in the selection toolbar (cloned into <body> on each selection). */
  get annotateButton(): Locator {
    return this.page.locator('#comment-toolbar-active .annotation-toolbar-btn');
  }

  get captureForm(): Locator {
    return this.page.locator('[data-annotation-form]');
  }

  get captureEditor(): RichTextEditor {
    return new RichTextEditor(this.page, 'annotation-body-editor');
  }

  /** Assumes a selection is live: « Annoter » → type the body → « Enregistrer ». */
  async capture(body: string): Promise<void> {
    await this.annotateButton.click();
    await expect(this.captureForm).toBeVisible();
    await this.captureEditor.waitUntilReady();
    await this.captureEditor.fill(body);
    await this.captureForm.getByRole('button', { name: 'Enregistrer' }).click();
    await expect(this.captureForm).toBeHidden();
  }

  get banner(): Locator {
    return this.page.getByTestId('annotation-banner');
  }

  async openDrafts(): Promise<void> {
    await this.banner.getByRole('button').click();
    await expect(this.draftsDialog).toBeVisible();
  }

  get draftsDialog(): Locator {
    return this.page.getByRole('dialog', { name: 'Vos annotations en attente' });
  }

  get draftRows(): Locator {
    return this.draftsDialog.locator('li');
  }

  async deleteDraft(row: Locator): Promise<void> {
    await row.getByRole('button', { name: 'Supprimer', exact: true }).click();
  }

  /** « N annotations » on a published root comment. */
  countButton(commentItem: Locator): Locator {
    return commentItem.locator('[data-annotations-button]').first();
  }

  /** The button's label, without its icon ligature. */
  countLabel(commentItem: Locator): Locator {
    return this.countButton(commentItem).locator('[data-annotations-count]');
  }

  async openPublished(commentItem: Locator): Promise<void> {
    await this.countButton(commentItem).click();
    await expect(this.serverDialog).toBeVisible();
  }

  get serverDialog(): Locator {
    return this.page.getByRole('dialog', { name: 'Annotations', exact: true });
  }

  get serverRows(): Locator {
    return this.serverDialog.locator('li');
  }

  markProcessedButton(row: Locator): Locator {
    return row.getByRole('button', { name: 'Marquer comme traitée', exact: true });
  }

  markUnprocessedButton(row: Locator): Locator {
    return row.getByRole('button', { name: 'Marquer comme non traitée', exact: true });
  }

  processedMarker(row: Locator): Locator {
    return row.getByTestId('annotation-processed');
  }

  deleteButton(row: Locator): Locator {
    return row.getByRole('button', { name: "Supprimer l'annotation", exact: true });
  }
}
