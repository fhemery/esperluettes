import { expect, type Locator, type Page } from '@playwright/test';
import { RichTextEditor } from './RichTextEditor';

/**
 * Component object for `<x-editor::multi>` — the Simple/Avancé block editor.
 *
 * The component carries no `data-testid`: its own Alpine code addresses blocks
 * through `data-block` / `data-type` / `data-uid`, and the controls through
 * their `x-on:click` handlers. Those attributes are therefore part of the
 * component's contract already, so anchoring on them costs production markup
 * nothing and breaks exactly when the behaviour breaks. Everything is scoped
 * to the component root, so a form with several editors never mixes them up.
 */
export class MultiEditor {
  readonly root: Locator;
  /** The single rich-text field shown in Simple mode. */
  readonly simple: RichTextEditor;

  constructor(private readonly page: Page, root?: Locator) {
    this.root = root ?? page.locator('.multi-editor');
    this.simple = new RichTextEditor(page, this.root.locator('[data-testid^="rich-text-me-simple-"]'));
  }

  /**
   * Resolves once the component has booted, whichever mode it opened in: the
   * Simple pane is `x-show`-hidden on an advanced document, so waiting for it
   * unconditionally would hang on a chapter stored as blocks.
   */
  async waitUntilReady(): Promise<void> {
    await expect(this.root).toBeVisible();
    // The mode input is Alpine-bound, so it is empty until Alpine has run.
    await expect.poll(() => this.mode(), { message: 'multi-editor never booted' }).not.toBe('');
    if ((await this.mode()) === 'advanced') {
      await expect(this.textBlocks.first().locator('.ql-editor')).toBeVisible();
    } else {
      await this.simple.waitUntilReady();
    }
  }

  /** What the form will submit: 'simple' or 'advanced'. */
  async mode(): Promise<string> {
    return this.root.locator('input[name="mode"]').inputValue();
  }

  /** The visual order of block uids, as the server will read it. */
  async order(): Promise<string[]> {
    const csv = await this.root.locator('input[name$="_order"]').inputValue();
    return csv === '' ? [] : csv.split(',');
  }

  get simpleButton(): Locator {
    return this.root.locator('button[x-on\\:click="goSimple()"]');
  }

  get advancedButton(): Locator {
    return this.root.locator('button[x-on\\:click="goAdvanced()"]');
  }

  get blocks(): Locator {
    return this.root.locator('.multi-editor__blocks > [data-block]');
  }

  get textBlocks(): Locator {
    return this.root.locator('.multi-editor__blocks > [data-block][data-type="text"]');
  }

  block(index: number): Locator {
    return this.blocks.nth(index);
  }

  /** The Quill surface of the nth text block. */
  textBlockEditor(index: number): RichTextEditor {
    return new RichTextEditor(this.page, this.textBlocks.nth(index));
  }

  async goAdvanced(): Promise<void> {
    await this.advancedButton.click();
    await expect(this.blocks.first()).toBeVisible();
  }

  async goSimple(): Promise<void> {
    await this.simpleButton.click();
    await expect(this.simple.body).toBeVisible();
  }

  /** The palette at the bottom of the advanced pane. */
  async addBlock(type: 'text' | 'image'): Promise<void> {
    const before = await this.blocks.count();
    await this.root.locator(`button[x-on\\:click="appendBlock('${type}')"]`).click();
    await expect(this.blocks).toHaveCount(before + 1);
  }

  /** The "+" affordance at the bottom of a block, which inserts right after it. */
  async insertAfter(index: number, type: 'text' | 'image'): Promise<void> {
    const before = await this.blocks.count();
    const block = this.block(index);
    await block.locator('button[x-on\\:click="open = !open"]').click();
    await block.locator(`button[x-on\\:click="insertAfter($el, '${type}'); open = false"]`).click();
    await expect(this.blocks).toHaveCount(before + 1);
  }

  async moveDown(index: number): Promise<void> {
    await this.block(index).locator('button[x-on\\:click="moveDown($el)"]').click();
  }

  async removeBlock(index: number): Promise<void> {
    const before = await this.blocks.count();
    await this.block(index).locator('button[x-on\\:click="removeBlock($el)"]').click();
    await expect(this.blocks).toHaveCount(before - 1);
  }
}
