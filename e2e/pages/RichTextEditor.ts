import { expect, type Locator, type Page } from '@playwright/test';

/**
 * Component object for `<x-editor::rich-text>`.
 *
 * Anchored on the wrapper, given either as the `id` the component derives its
 * `data-testid` from, or as a ready-made locator — `<x-editor::multi>` builds
 * its simple pane with a generated id, so it can only be reached positionally.
 * Everything else is scoped inside that wrapper, so a page with several
 * editors never mixes them up.
 */
export class RichTextEditor {
  readonly root: Locator;

  constructor(private readonly page: Page, target: string | Locator) {
    this.root = typeof target === 'string' ? page.getByTestId(`rich-text-${target}`) : target;
  }

  /** Quill's contenteditable surface. */
  get body(): Locator {
    return this.root.locator('.ql-editor');
  }

  /** The toolbar Quill injects above the editor. */
  get toolbar(): Locator {
    return this.root.locator('.ql-toolbar');
  }

  /** The hidden textarea the form actually submits. */
  get input(): Locator {
    return this.root.locator('textarea');
  }

  get counter(): Locator {
    return this.root.locator('[id^="quill-counter-"]:not([id^="quill-counter-wrap-"])');
  }

  /** Resolves once Quill has booted and replaced the div — i.e. the bundle ran. */
  async waitUntilReady(): Promise<void> {
    await expect(this.body).toBeVisible();
  }

  async text(): Promise<string> {
    return (await this.body.innerText()).trim();
  }

  /** Replace the whole content, the way a user would. */
  async fill(text: string): Promise<void> {
    await this.body.click();
    await this.page.keyboard.press('ControlOrMeta+A');
    await this.page.keyboard.type(text);
  }

  async type(text: string): Promise<void> {
    await this.body.click();
    await this.page.keyboard.type(text);
  }

  async clickToolbarButton(className: string): Promise<void> {
    await this.toolbar.locator(`button.${className}`).click();
  }
}
