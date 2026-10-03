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

  /**
   * Evidence screenshot for a VERIFY run, taken only when `E2E_SHOTS_DIR` is
   * set — later suite runs take none.
   */
  async evidence(name: string): Promise<void> {
    const dir = process.env.E2E_SHOTS_DIR;
    if (!dir) return;
    // Modals fade in: capture the settled state, not a half-transparent frame.
    await this.page.evaluate(() =>
      Promise.race([
        Promise.all(
          document
            .getAnimations()
            .filter((a) => a.effect?.getTiming().iterations !== Infinity)
            .map((a) => a.finished.catch(() => undefined)),
        ),
        new Promise((resolve) => setTimeout(resolve, 2000)),
      ]),
    );
    await this.page.screenshot({ path: `${dir}/${name}.png` });
  }

  /** « Annoter » in the selection toolbar (cloned into <body> on each selection). */
  get annotateButton(): Locator {
    return this.page.locator('#comment-toolbar-active .annotation-toolbar-btn');
  }

  /** The selection toolbar itself, cloned into <body> on first use. */
  get toolbar(): Locator {
    return this.page.locator('#comment-toolbar-active');
  }

  /** Quote's « Citer », the other action of the same toolbar. */
  get quoteButton(): Locator {
    return this.toolbar.locator('.quote-toolbar-btn');
  }

  /** « Sélection trop longue », shown in place of the actions. */
  get toolbarTooLong(): Locator {
    return this.toolbar.locator('[data-toolbar-too-long]');
  }

  get captureForm(): Locator {
    return this.page.locator('[data-annotation-form]');
  }

  /** The highlighted passage echoed at the top of the capture form. */
  get captureHighlight(): Locator {
    return this.captureForm.locator('blockquote');
  }

  get captureError(): Locator {
    return this.captureForm.locator('p[x-text="error"]');
  }

  get captureSave(): Locator {
    return this.captureForm.getByRole('button', { name: 'Enregistrer', exact: true });
  }

  get captureCancel(): Locator {
    return this.captureForm.getByRole('button', { name: 'Annuler', exact: true });
  }

  /**
   * Every annotation draft in this browser's localStorage, whoever wrote it,
   * keyed by the comment-draft store key (`comment-drafts:{user}:{type}:{id}`).
   */
  async storedDrafts(): Promise<Record<string, { body: string; highlighted: string }[]>> {
    return this.page.evaluate(() => {
      const out: Record<string, { body: string; highlighted: string }[]> = {};
      for (let i = 0; i < localStorage.length; i++) {
        const key = localStorage.key(i)!;
        if (!key.startsWith('comment-drafts:')) continue;
        const state = JSON.parse(localStorage.getItem(key) ?? '{}');
        if (Array.isArray(state.annotations) && state.annotations.length > 0) out[key] = state.annotations;
      }
      return out;
    });
  }

  /** The in-progress root comment body kept by the comment-draft store, if any. */
  async storedRootBody(): Promise<string | null> {
    return this.page.evaluate(() => {
      for (let i = 0; i < localStorage.length; i++) {
        const key = localStorage.key(i)!;
        if (!key.startsWith('comment-drafts:')) continue;
        const state = JSON.parse(localStorage.getItem(key) ?? '{}');
        if (state.root?.body) return state.root.body as string;
      }
      return null;
    });
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

  /** « Modifier »: closes the pop-up and reopens the capture form on that draft. */
  async editDraft(row: Locator): Promise<void> {
    await row.getByRole('button', { name: 'Modifier', exact: true }).click();
    await expect(this.captureForm).toBeVisible();
    await this.captureEditor.waitUntilReady();
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

  /** Every « N annotations » button on the page. */
  get allCountButtons(): Locator {
    return this.page.locator('[data-annotations-button]');
  }

  deleteButton(row: Locator): Locator {
    return row.getByRole('button', { name: "Supprimer l'annotation", exact: true });
  }
}
