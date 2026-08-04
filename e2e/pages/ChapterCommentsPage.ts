import { expect, type Locator, type Page } from '@playwright/test';
import { COMMENTS, STORY } from '../support/fixtures';
import { RichTextEditor } from './RichTextEditor';

/** Chapter show `#comments`: lazy-loaded list with inline reply/edit composers. */
export class ChapterCommentsPage {
  constructor(
    private readonly page: Page,
    private readonly storySlug: string = STORY.slug,
    private readonly chapterSlug: string = COMMENTS.chapterSlug,
  ) {}

  get path(): string {
    return `/stories/${this.storySlug}/chapters/${this.chapterSlug}#comments`;
  }

  get commentsSection(): Locator {
    return this.page.locator('#comments');
  }

  get commentList(): Locator {
    return this.page.locator('#comment-list');
  }

  get rootComment(): Locator {
    return this.page.locator(`#comment-${COMMENTS.rootCommentId}`);
  }

  async goto(): Promise<void> {
    const response = await this.page.goto(this.path);
    expect(response?.status(), `GET ${this.path}`).toBe(200);
  }

  /** Scroll the lazy-load sentinel into view, then wait for the seeded marker. */
  async waitForCommentsLoaded(): Promise<void> {
    await this.commentsSection.scrollIntoViewIfNeeded();

    const marker = this.commentList.getByText(COMMENTS.bodyMarker);
    if (await marker.isVisible()) {
      return;
    }

    const sentinel = this.commentList.locator('.h-1').last();
    await sentinel.scrollIntoViewIfNeeded();
    await expect(marker).toBeVisible();
  }

  async openReplyOnRoot(): Promise<void> {
    await this.rootComment.getByRole('button', { name: 'Répondre', exact: true }).click();
  }

  async openEditOnRoot(): Promise<void> {
    await this.rootComment.getByRole('button', { name: 'Éditer' }).click();
  }

  replyEditor(): RichTextEditor {
    return new RichTextEditor(this.page, `reply-editor-${COMMENTS.rootCommentId}`);
  }

  editEditor(): RichTextEditor {
    return new RichTextEditor(this.page, `edit-editor-${COMMENTS.rootCommentId}`);
  }
}
