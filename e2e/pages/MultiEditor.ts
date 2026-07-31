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
   * unconditionally would hang on exactly the chapters this feature adds.
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

  get simplePane(): Locator {
    return this.root.locator('div[x-show="mode === \'simple\'"]');
  }

  get advancedPane(): Locator {
    return this.root.locator('div[x-show="mode === \'advanced\'"]');
  }

  get blocks(): Locator {
    return this.root.locator('.multi-editor__blocks > [data-block]');
  }

  get textBlocks(): Locator {
    return this.root.locator('.multi-editor__blocks > [data-block][data-type="text"]');
  }

  get imageBlocks(): Locator {
    return this.root.locator('.multi-editor__blocks > [data-block][data-type="image"]');
  }

  block(index: number): Locator {
    return this.blocks.nth(index);
  }

  /** The Quill surface of the nth text block. */
  textBlockEditor(index: number): RichTextEditor {
    return new RichTextEditor(this.page, this.textBlocks.nth(index));
  }

  /** The hidden textarea the nth text block submits. */
  textBlockInput(index: number): Locator {
    return this.textBlocks.nth(index).locator('textarea');
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

  async moveUp(index: number): Promise<void> {
    await this.block(index).locator('button[x-on\\:click="moveUp($el)"]').click();
  }

  async moveDown(index: number): Promise<void> {
    await this.block(index).locator('button[x-on\\:click="moveDown($el)"]').click();
  }

  async removeBlock(index: number): Promise<void> {
    const before = await this.blocks.count();
    await this.block(index).locator('button[x-on\\:click="removeBlock($el)"]').click();
    await expect(this.blocks).toHaveCount(before - 1);
  }

  /** The image controls of a block, addressed by its position among all blocks. */
  image(index: number): ImageBlock {
    return new ImageBlock(this.page, this.block(index));
  }
}

/** The `<x-media::image-field>` inside one image block. */
export class ImageBlock {
  constructor(private readonly page: Page, readonly root: Locator) {}

  get fileInput(): Locator {
    return this.root.locator('input[type="file"]');
  }

  get pathInput(): Locator {
    return this.root.locator('input[name$="[path]"]');
  }

  get alt(): Locator {
    return this.root.locator('input[name$="[alt]"]');
  }

  get caption(): Locator {
    return this.root.locator('input[name$="[caption]"]');
  }

  get preview(): Locator {
    return this.root.locator('img[alt]:not([alt=""])').first();
  }

  /** Upload a PNG generated on the fly — no binary fixture in the repo. */
  async upload(name = 'e2e.png'): Promise<void> {
    await this.fileInput.setInputFiles({ name, mimeType: 'image/png', buffer: pngBuffer() });
    await expect(this.preview).toBeVisible();
  }

  /**
   * Open the reuse picker and wait for its library to have arrived: the modal
   * is shown synchronously and only then does Alpine `fetch` the library, so
   * reading the items straight after the click reports an empty picker.
   */
  async openPicker(): Promise<Locator> {
    const library = this.page.waitForResponse(
      (response) => response.url().includes('/media/library') && response.status() === 200,
    );
    await this.root.locator('button[x-on\\:click="openPicker()"]').click();
    const modal = this.root.locator('div[x-show="pickerOpen"]');
    await expect(modal).toBeVisible();
    await library;
    // …and then for Alpine to have rendered it: either the grid has items or
    // the "library is empty" paragraph is up. Until one of the two is true the
    // picker is still loading and reading the items reports nothing.
    await expect
      .poll(async () => (await this.pickerItems.count()) > 0 || (await this.pickerEmptyMessage.count()) > 0, {
        message: 'the picker never finished loading',
      })
      .toBe(true);
    return modal;
  }

  get pickerItems(): Locator {
    return this.root.locator('div[x-show="pickerOpen"] button[x-on\\:click="chooseExisting(item)"] img');
  }

  /** The `x-if` paragraph shown when the scope holds no reusable image. */
  get pickerEmptyMessage(): Locator {
    return this.root.locator('div[x-show="pickerOpen"] p.text-center');
  }

  async chooseFromPicker(index = 0): Promise<void> {
    await this.pickerItems.nth(index).click();
    await expect(this.preview).toBeVisible();
  }
}

/**
 * A 2×2 red PNG. Small enough to stay under any size limit, and — unlike the
 * usual copy-pasted one-liners — with a valid IDAT checksum: GD refuses to
 * decode a PNG whose zlib check fails, and the upload then 500s inside
 * `ImageService::generateVariants()` rather than failing as a bad request.
 */
export function pngBuffer(): Buffer {
  return Buffer.from(
    'iVBORw0KGgoAAAANSUhEUgAAAAIAAAACCAIAAAD91JpzAAAAEElEQVR42mP4z8AARAwQCgAf7gP9Y167WwAAAABJRU5ErkJggg==',
    'base64',
  );
}
