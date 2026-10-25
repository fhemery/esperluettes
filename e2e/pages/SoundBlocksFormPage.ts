import { expect, type Locator, type Page } from '@playwright/test';
import { MultiEditor } from './MultiEditor';
import { SoundField } from './SoundField';

/**
 * The admin News / StaticPage create + edit form, as far as sound blocks need it.
 * Both forms share the same ids (`title`, `slug`, `summary`, `status`).
 */
export class SoundBlocksFormPage {
  readonly editor: MultiEditor;

  constructor(
    private readonly page: Page,
    /** 'news' or 'static-pages' */
    readonly kind: 'news' | 'static-pages',
  ) {
    this.editor = new MultiEditor(page);
  }

  async gotoCreate(): Promise<void> {
    await this.page.goto(`/admin/${this.kind}/create`);
    await this.editor.root.waitFor();
    await expect.poll(() => this.editor.mode()).not.toBe('');
  }

  /** From the admin index: open the edit form of the row titled `title`. */
  async gotoEdit(title: string): Promise<void> {
    await this.page.goto(`/admin/${this.kind}`);
    await this.page.locator('tr', { hasText: title }).locator('a[href*="/edit"]').first().click();
    await this.editor.root.waitFor();
    await expect.poll(() => this.editor.mode()).not.toBe('');
  }

  async fill(title: string, slug: string, status: 'draft' | 'published'): Promise<void> {
    // The slug is derived from the title on blur; filling it as well would append.
    await this.page.locator('#title').fill(title);
    await this.page.locator('#title').blur();
    await expect(this.page.locator('#slug')).toHaveValue(slug);
    await this.page.locator('#summary').fill('Résumé de test');
    await this.page.locator('#status').selectOption(status);
  }

  soundField(blockIndex: number): SoundField {
    return new SoundField(this.editor.block(blockIndex).locator('[x-data*="mediaSoundField"]').first());
  }

  caption(blockIndex: number): Locator {
    return this.editor.block(blockIndex).locator('input[name$="[caption]"]');
  }

  get submit(): Locator {
    return this.page.locator('form button[type="submit"]').last();
  }

  /** Server-side error text inside the form (French validation messages). */
  get errors(): Locator {
    return this.page.locator('form .text-error, form ul.text-sm li, form [class*="text-red"], form [class*="text-error"]');
  }
}

/** Evidence shot, only when `E2E_SHOTS_DIR` is set — later suite runs take none. */
export async function shot(page: Page, name: string): Promise<void> {
  const dir = process.env.E2E_SHOTS_DIR;
  if (dir) await page.screenshot({ path: `${dir}/${name}.png`, fullPage: true });
}
