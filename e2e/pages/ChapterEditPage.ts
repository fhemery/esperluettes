import { expect, type Locator, type Page, type Response } from '@playwright/test';
import { STORY } from '../support/fixtures';
import { RichTextEditor } from './RichTextEditor';

/**
 * The chapter edit form.
 *
 * Carries two rich-text editors — the author note (hidden behind a button
 * until asked for) and the content — so both are exposed by name rather than
 * by position.
 */
export class ChapterEditPage {
  readonly content: RichTextEditor;
  readonly authorNote: RichTextEditor;

  constructor(
    private readonly page: Page,
    private readonly storySlug: string = STORY.slug,
    private readonly chapterSlug: string = STORY.publishedChapter.slug,
  ) {
    this.content = new RichTextEditor(page, 'chapter-content-editor');
    this.authorNote = new RichTextEditor(page, 'chapter-author-note-editor');
  }

  get path(): string {
    return `/stories/${this.storySlug}/chapters/${this.chapterSlug}/edit`;
  }

  get title(): Locator {
    return this.page.locator('#title');
  }

  get publishedToggle(): Locator {
    return this.page.locator('#published');
  }

  get saveButton(): Locator {
    return this.page.getByTestId('chapter-save');
  }

  /** Navigate without asserting — for the role checks, where a 403 is the point. */
  async tryGoto(): Promise<Response | null> {
    return this.page.goto(this.path);
  }

  /** Navigate and wait until the editor has actually booted. */
  async goto(): Promise<void> {
    const response = await this.tryGoto();
    expect(response?.status(), `GET ${this.path}`).toBe(200);
    await this.content.waitUntilReady();
  }

  async save(): Promise<void> {
    await this.saveButton.click();
    await this.page.waitForLoadState('networkidle');
    await expect(this.page, 'still on the edit form after saving').not.toHaveURL(/\/edit$/);
  }
}
