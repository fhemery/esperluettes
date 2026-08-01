import { expect, type Locator, type Page, type Response } from '@playwright/test';
import { STORY } from '../support/fixtures';
import { MultiEditor } from './MultiEditor';
import { RichTextEditor } from './RichTextEditor';

/**
 * The chapter edit form.
 *
 * Carries two editors — the author note (a plain rich-text field, hidden
 * behind a button until asked for) and the content, which is
 * `<x-editor::multi>`. `content` stays that component's Simple pane, so specs
 * written before the MultiEdit work keep reading as they did; `blocks` is the
 * component as a whole.
 */
export class ChapterEditPage {
  readonly blocks: MultiEditor;
  readonly content: RichTextEditor;
  readonly authorNote: RichTextEditor;

  constructor(
    private readonly page: Page,
    private readonly storySlug: string = STORY.slug,
    private readonly chapterSlug: string = STORY.publishedChapter.slug,
  ) {
    this.blocks = new MultiEditor(page);
    this.content = this.blocks.simple;
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
    await this.blocks.waitUntilReady();
  }

  async save(): Promise<void> {
    await this.saveButton.click();
    await this.page.waitForLoadState('networkidle');
    await expect(this.page, 'still on the edit form after saving').not.toHaveURL(/\/edit$/);
  }
}
