import { expect, type Locator, type Page } from '@playwright/test';
import { RichTextEditor } from './RichTextEditor';

/**
 * Component object for `<x-comment::comment-list-component>`.
 *
 * The thread is the same markup wherever it is mounted (chapter, news, …), so
 * every selector for it lives here. Two things about it drive the shape of this
 * class:
 *
 * - In lazy mode (`page="0"`) the items are **not** in the initial HTML; they
 *   arrive from `GET /comments/fragments` once the sentinel scrolls into view.
 *   Anything that reads an item must wait for that.
 * - A comment is an `<li id="comment-{id}">`, and replies are `<li>`s nested
 *   inside their root's `<li>`.
 */
export class CommentThread {
  readonly root: Locator;

  constructor(private readonly page: Page) {
    this.root = page.locator('#comment-list');
  }

  get rootForm(): Locator {
    return this.root.locator('form[data-comment-draft="root"]');
  }

  get rootEditor(): RichTextEditor {
    return new RichTextEditor(this.page, 'comment-body-editor');
  }

  /** `<x-shared::button>` prints its icon as text, so match the element, not a name. */
  get rootSubmit(): Locator {
    return this.rootForm.locator('button[type="submit"]');
  }

  /** Every comment on screen, roots and replies alike. */
  get items(): Locator {
    return this.root.locator('li[id^="comment-"]');
  }

  item(commentId: number): Locator {
    return this.root.locator(`#comment-${commentId}`);
  }

  /** Scroll to the bottom so the `x-intersect` sentinel fires the fragment fetch. */
  async scrollToBottom(): Promise<void> {
    await this.page.mouse.wheel(0, 20000);
    await this.page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
  }

  /**
   * Half a screen down, like a finger would. A single jump to the bottom can
   * skip the 4 px sentinel on a short mobile page whose bottom is padded by the
   * sticky save banner: it is never inside the viewport on any frame.
   */
  async scrollDown(): Promise<void> {
    await this.page.evaluate(() => window.scrollBy(0, window.innerHeight / 2));
  }

  /**
   * Fill the root editor and submit. Returns the new comment's id, which the
   * controller puts in the redirect URL (`?comment=<id>#comments`) — the only
   * place it is exposed without guessing from the markup.
   */
  async postRoot(body: string): Promise<number> {
    await this.rootEditor.waitUntilReady();
    await this.rootEditor.fill(body);
    await expect(this.rootSubmit).toBeEnabled();
    const before = this.page.url();
    await this.rootSubmit.click();
    return this.awaitPostedId(before);
  }

  /**
   * Moderator: « Vider le contenu » from the comment's moderation popover. The
   * popover panel is teleported to <body>, hence the page-wide, visible-only
   * lookup of the action.
   */
  async emptyContent(commentId: number): Promise<void> {
    await this.item(commentId).locator('[aria-haspopup="dialog"]').first().click();
    const action = this.page
      .getByRole('button', { name: 'Vider le contenu', exact: true })
      .filter({ visible: true });
    await action.click();
    await this.page.waitForLoadState('load');
  }

  replyForm(parentCommentId: number): Locator {
    return this.root.locator(
      `form[data-comment-draft="reply"][data-parent-comment-id="${parentCommentId}"]`,
    );
  }

  replyEditor(parentCommentId: number): RichTextEditor {
    return new RichTextEditor(this.page, `reply-editor-${parentCommentId}`);
  }

  replySubmit(parentCommentId: number): Locator {
    return this.replyForm(parentCommentId).locator('button[type="submit"]');
  }

  /** The id of a comment item found by other means (e.g. by its text). */
  async idOf(item: Locator): Promise<number> {
    const id = await item.getAttribute('id');
    return Number(id?.replace('comment-', ''));
  }

  async openReply(parentCommentId: number): Promise<void> {
    await this.item(parentCommentId)
      .getByRole('button', { name: 'Répondre', exact: true })
      .click();
  }

  async postReply(parentCommentId: number, body: string): Promise<number> {
    await this.openReply(parentCommentId);
    const editor = this.replyEditor(parentCommentId);
    await editor.waitUntilReady();
    await editor.fill(body);
    const submit = this.replyForm(parentCommentId).locator('button[type="submit"]');
    await expect(submit).toBeEnabled();
    const before = this.page.url();
    await submit.click();
    return this.awaitPostedId(before);
  }

  /**
   * The URL we came from matters: the page may already carry a `?comment=`
   * (a deep link), and waiting for the pattern alone would read the *old* id
   * before the POST has even landed.
   */
  private async awaitPostedId(previousUrl: string): Promise<number> {
    await this.page.waitForURL(
      (url) => url.toString() !== previousUrl && /[?&]comment=\d+/.test(url.toString()),
    );
    // The controller appends `?comment=<id>` to the referer, which may already
    // carry one (posting from a deep link), so the *last* value is the new id.
    const params = new URL(this.page.url()).searchParams.getAll('comment');
    const id = Number(params[params.length - 1]);
    expect(id, `no comment id in ${this.page.url()}`).toBeGreaterThan(0);
    return id;
  }
}
