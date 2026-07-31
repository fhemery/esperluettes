import { expect, type Locator, type Page } from '@playwright/test';

/**
 * Component object for `<x-editor::rich-text>`.
 *
 * Anchored on the wrapper's `data-testid`, which the component derives from
 * the `id` it is given. Everything else is scoped inside it, so a page with
 * several editors never mixes them up.
 */
export class RichTextEditor {
  constructor(private readonly page: Page, private readonly id: string) {}

  /** The component wrapper: toolbar, editor and counter all live in here. */
  get root(): Locator {
    return this.page.getByTestId(`rich-text-${this.id}`);
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
    return this.page.locator(`#quill-counter-${this.id}`);
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
