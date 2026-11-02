import { expect, type Locator, type Page } from '@playwright/test';
import { STORY } from '../support/fixtures';
import { DateTimeField } from './DateTimeField';
import { MultiEditor } from './MultiEditor';

/** The chapter create form — same `<x-editor::multi>` as the edit form. */
export class ChapterCreatePage {
  readonly blocks: MultiEditor;

  constructor(
    private readonly page: Page,
    private readonly storySlug: string = STORY.slug,
  ) {
    this.blocks = new MultiEditor(page);
  }

  get path(): string {
    return `/stories/${this.storySlug}/chapters/create`;
  }

  get title(): Locator {
    return this.page.locator('#title');
  }

  get publishedToggle(): Locator {
    return this.page.locator('#published');
  }

  /** Scheduled publication; only visible while the toggle is off. */
  get publishAt(): DateTimeField {
    return new DateTimeField(this.page, 'publish_at');
  }

  /** The live "pending uploads vs balance" counter. */
  get creditsCounter(): Locator {
    return this.page.getByTestId('chapter-image-credits');
  }

  /** The server-side rejection shown when the balance cannot cover the save. */
  get creditsError(): Locator {
    return this.page.getByText(/Cet enregistrement coûte/).first();
  }

  /** The hidden file input of the nth block (an image block). */
  imageFileInput(blockIndex: number): Locator {
    return this.blocks.block(blockIndex).locator('input[type="file"][name$="[file]"]');
  }

  /** Ticks the « ma propriété ou libre de droit » box that a freshly picked image requires. */
  async confirmPropertyOf(blockIndex: number): Promise<void> {
    await this.blocks.block(blockIndex).locator('input[type="checkbox"][required]').check();
  }

  get saveButton(): Locator {
    return this.page.getByTestId('chapter-save');
  }

  async goto(): Promise<void> {
    const response = await this.page.goto(this.path);
    expect(response?.status(), `GET ${this.path}`).toBe(200);
    await this.blocks.waitUntilReady();
  }
}
