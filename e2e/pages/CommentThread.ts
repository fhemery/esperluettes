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
 *   inside their root's `<li>`. So a locator matching "the li holding this
 *   body" matches the ancestor too — `.last()` is what picks the innermost one.
 */
export class CommentThread {
  readonly root: Locator;

  constructor(private readonly page: Page) {
    this.root = page.locator('#comment-list');
  }

  /** The "members only" box Comment renders when `checkAccess()` refuses. */
  get membersOnlyBox(): Locator {
    return this.root.getByText('Seuls les membres peuvent accéder aux commentaires.');
  }

  get loginButton(): Locator {
    return this.root.getByRole('link', { name: 'Se connecter' });
  }

  get emptyMessage(): Locator {
    return this.root.getByText('Aucun commentaire pour le moment.');
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

  /**
   * The innermost `<li>` whose body is `text` — i.e. the comment itself rather
   * than the root it may be nested in.
   */
  itemWithBody(text: string): Locator {
    return this.root
      .locator('li[id^="comment-"]')
      .filter({ has: this.page.locator('.comment-body', { hasText: text }) })
      .last();
  }

  async idOfBody(text: string): Promise<number> {
    const raw = await this.itemWithBody(text).getAttribute('id');
    const id = Number((raw ?? '').replace('comment-', ''));
    expect(id, `no comment id parsed from "${raw}"`).toBeGreaterThan(0);
    return id;
  }

  /** Scroll to the bottom so the `x-intersect` sentinel fires the fragment fetch. */
  async scrollToBottom(): Promise<void> {
    await this.page.mouse.wheel(0, 20000);
    await this.page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
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

  replyButtonOn(commentId: number): Locator {
    return this.item(commentId).locator('[data-action="reply"]').first();
  }

  replyForm(parentId: number): Locator {
    return this.root.locator(`form[data-comment-draft="reply"][data-parent-comment-id="${parentId}"]`);
  }

  replyEditor(parentId: number): RichTextEditor {
    return new RichTextEditor(this.page, `reply-editor-${parentId}`);
  }

  async postReply(rootId: number, body: string): Promise<number> {
    await this.replyButtonOn(rootId).click();
    const form = this.replyForm(rootId);
    await expect(form).toBeVisible();
    await this.replyEditor(rootId).fill(body);
    const submit = form.locator('button[type="submit"]');
    await expect(submit).toBeEnabled();
    const before = this.page.url();
    await submit.click();
    return this.awaitPostedId(before);
  }

  editButtonOn(commentId: number): Locator {
    return this.item(commentId).locator(`[data-action="edit"][data-comment-id="${commentId}"]`).first();
  }

  editForm(commentId: number): Locator {
    return this.item(commentId).locator(`form[action$="/comments/${commentId}"]`);
  }

  editEditor(commentId: number): RichTextEditor {
    return new RichTextEditor(this.page, `edit-editor-${commentId}`);
  }

  /** The compact flag button `<x-moderation::report-button>` renders. */
  reportButtonOn(commentId: number): Locator {
    return this.item(commentId).locator('[x-data^="reportButton"] button').first();
  }

  /**
   * The moderator popover trigger, present only for a moderator on someone
   * else's comment.
   */
  moderationTriggerOn(commentId: number): Locator {
    return this.item(commentId).locator('[aria-haspopup="dialog"]').first();
  }

  /**
   * The moderator action menu. `<x-shared::popover>` teleports its panel to
   * `<body>`, so this cannot be scoped to the comment — every comment's panel
   * sits at body level and only the open one is visible.
   */
  get openModerationMenu(): Locator {
    return this.page.locator('#comment-moderator-btn').locator('visible=true');
  }
}
