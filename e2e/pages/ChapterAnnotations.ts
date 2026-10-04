import { expect, type Locator, type Page } from '@playwright/test';
import { RichTextEditor } from './RichTextEditor';

/** The emoji buttons of the selection toolbar, by their accessible name. */
const REACTIONS = {
  heart: 'Réagir avec un cœur',
  fire: 'Réagir avec une flamme',
  thumbs_up: 'Réagir avec un pouce levé',
} as const;

export type Reaction = keyof typeof REACTIONS;

/** The pending-change markers of the commenter's rows in the « N annotations » pop-up. */
const PENDING_LABELS = {
  edited: 'Modifiée — non enregistrée',
  deleted: 'Sera supprimée',
  added: 'Ajoutée — non enregistrée',
} as const;

export type PendingState = keyof typeof PENDING_LABELS;

/**
 * Component object for chapter annotations: the « Annoter » and emoji toolbar
 * actions, the capture form, the drafts banner and pop-up above the root
 * comment form, the save banner of pending changes, and the « N annotations »
 * server pop-up (rows, pending markers, reply threads) on a published root.
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

  /** The selection toolbar itself, cloned into <body> on first use. */
  get toolbar(): Locator {
    return this.page.locator('#comment-toolbar-active');
  }

  /** « Sélection trop longue », shown in place of the actions. */
  get toolbarTooLong(): Locator {
    return this.toolbar.locator('[data-toolbar-too-long]');
  }

  /** The group of ❤️ 🔥 👍 buttons in the selection toolbar. */
  get reactions(): Locator {
    return this.toolbar.locator('[data-annotation-reactions]');
  }

  reactionButton(reaction: Reaction): Locator {
    return this.toolbar.getByRole('button', { name: REACTIONS[reaction], exact: true });
  }

  /** Assumes a selection is live: one click stores the emoji, no form opens. */
  async react(reaction: Reaction, options: { tap?: boolean } = {}): Promise<void> {
    const button = this.reactionButton(reaction);
    await expect(button).toBeVisible();
    if (options.tap) await button.tap();
    else await button.click();
    await expect(this.toolbar).toBeHidden();
    await expect(this.captureForm).toBeHidden();
  }

  get captureForm(): Locator {
    return this.page.locator('[data-annotation-form]');
  }

  get captureSave(): Locator {
    return this.captureForm.getByRole('button', { name: 'Enregistrer', exact: true });
  }

  /** The passage the capture form is about (read-only: an edit changes the body only). */
  get captureHighlight(): Locator {
    return this.captureForm.locator('blockquote');
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
    await this.captureSave.click();
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

  /** The sticky banner of pending changes to an already-posted root comment's annotations. */
  get saveBanner(): Locator {
    return this.page.getByTestId('annotation-changes-banner');
  }

  /** « Enregistrer » on the save banner; the banner goes once the save landed. */
  async saveChanges(): Promise<void> {
    await this.saveBanner.getByRole('button', { name: 'Enregistrer', exact: true }).click();
    await expect(this.saveBanner).toBeHidden();
  }

  /** « Tout annuler » on the save banner, accepting its confirmation; returns the confirmation text. */
  async discardChanges(): Promise<string> {
    const asked = new Promise<string>((resolve) => {
      this.page.once('dialog', (dialog) => {
        resolve(dialog.message());
        void dialog.accept();
      });
    });
    await this.saveBanner.getByRole('button', { name: 'Tout annuler', exact: true }).click();
    await expect(this.saveBanner).toBeHidden();
    return asked;
  }

  /** « Enregistrer » on the save banner when the save is expected to be refused. */
  async attemptSave(): Promise<void> {
    await this.saveBanner.getByRole('button', { name: 'Enregistrer', exact: true }).click();
    await expect(this.saveBannerError).toBeVisible();
  }

  /** The banner's error block: the general message, then one line per refused item. */
  get saveBannerError(): Locator {
    return this.saveBanner.getByRole('alert');
  }

  /** « Voir » on the save banner opens the pop-up of the viewer's root comment. */
  async openFromSaveBanner(): Promise<void> {
    await this.saveBanner.getByRole('button', { name: 'Voir', exact: true }).click();
    await expect(this.serverDialog).toBeVisible();
  }

  /** The viewer's own root comment id, as the chapter exposes it to the annotation scripts. */
  async rootCommentId(): Promise<number> {
    const id = await this.page.locator('[data-annotable]').first().getAttribute('data-root-comment-id');
    expect(id, 'no root comment for this viewer').not.toBeNull();
    return Number(id);
  }

  /**
   * Evidence screenshot for VERIFY, taken only when `E2E_SHOTS_DIR` is set, so
   * later suite runs take none.
   */
  async evidence(name: string, target?: Locator): Promise<void> {
    const dir = process.env.E2E_SHOTS_DIR;
    if (!dir) return;
    const path = `${dir}/${name}.png`;
    // Modals fade in: shoot the settled state.
    await this.page.evaluate(() => Promise.allSettled(document.getAnimations().map((a) => a.finished)));
    if (target) await target.screenshot({ path });
    else await this.page.screenshot({ path });
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

  /** Root annotations of the pop-up — their replies are nested `<li>`s, left out. */
  get serverRows(): Locator {
    return this.serverDialog.locator('li:not([data-testid="annotation-reply"])');
  }

  /** The commenter's row carrying the pending marker `state`. */
  pendingRow(state: PendingState): Locator {
    return this.serverRows.filter({
      has: this.page.getByText(PENDING_LABELS[state], { exact: true }).filter({ visible: true }),
    });
  }

  /** Commenter: « Modifier » reopens the capture form on the row's body. */
  async editRow(row: Locator): Promise<void> {
    await row.getByRole('button', { name: 'Modifier', exact: true }).click();
    await expect(this.captureForm).toBeVisible();
    await this.captureEditor.waitUntilReady();
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

  editButton(row: Locator): Locator {
    return row.getByRole('button', { name: 'Modifier', exact: true });
  }

  /** Commenter: « Supprimer l'annotation » stores a pending delete; returns any confirmation text. */
  async deleteRowPending(row: Locator, options: { accept?: boolean } = {}): Promise<string | null> {
    let asked: string | null = null;
    const onDialog = (dialog: import('@playwright/test').Dialog) => {
      asked = dialog.message();
      void (options.accept === false ? dialog.dismiss() : dialog.accept());
    };
    this.page.once('dialog', onDialog);
    await this.deleteButton(row).click();
    // No dialog when the row has no replies: drop the listener.
    this.page.off('dialog', onDialog);
    return asked;
  }

  /** Commenter: per-row undo — « Annuler la modification » on an edit, « Annuler » otherwise. */
  undoButton(row: Locator): Locator {
    return row.getByRole('button', { name: /^Annuler( la modification)?$/ });
  }

  /** Commenter: the line flagging a row the server refused, and its « Retirer ». */
  rowError(row: Locator): Locator {
    return row.getByRole('alert').filter({ visible: true });
  }

  removeStaleButton(row: Locator): Locator {
    return row.getByRole('button', { name: 'Retirer', exact: true });
  }

  /** The reply thread under a root annotation row. */
  replies(row: Locator): Locator {
    return row.getByTestId('annotation-reply');
  }

  replyButton(row: Locator): Locator {
    return row.getByRole('button', { name: 'Répondre', exact: true });
  }

  /** The author's nudge, shown after their reply, to answer on the root comment too. */
  replyHint(row: Locator): Locator {
    return row.getByText('Pour que le lecteur soit notifié, répondez aussi à son commentaire.');
  }

  /** The pop-up's single reply editor, moved under the row being answered. */
  get replyEditor(): RichTextEditor {
    return new RichTextEditor(this.page, 'annotation-reply-editor');
  }

  sendReplyButton(row: Locator): Locator {
    return row.getByRole('button', { name: 'Envoyer', exact: true });
  }

  /** « Répondre » → type the body in the shared reply editor → « Envoyer ». */
  async reply(row: Locator, body: string): Promise<void> {
    const before = await this.replies(row).count();
    await this.replyButton(row).click();
    const editor = this.replyEditor;
    await editor.waitUntilReady();
    await editor.fill(body);
    await this.sendReplyButton(row).click();
    await expect(this.replies(row)).toHaveCount(before + 1);
    await expect(editor.body).toBeHidden();
  }

  deleteReplyButton(reply: Locator): Locator {
    return reply.getByRole('button', { name: 'Supprimer la réponse', exact: true });
  }

  /** « Supprimer la réponse », accepting its confirmation. */
  async deleteReply(reply: Locator): Promise<void> {
    this.page.once('dialog', (dialog) => dialog.accept());
    await this.deleteReplyButton(reply).click();
  }
}
