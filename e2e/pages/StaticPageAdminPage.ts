import { expect, type Locator, type Page, type Response } from '@playwright/test';
import { STATIC_PAGE } from '../support/fixtures';
import { MultiEditor } from './MultiEditor';

/**
 * StaticPage admin create / edit form — MultiEdit body + the five-field layout.
 *
 * Selectors for the shared editor live on {@link MultiEditor}; this object only
 * owns the StaticPage form chrome and the routes under `/admin/static-pages`.
 */
export class StaticPageAdminPage {
  readonly blocks: MultiEditor;

  constructor(
    private readonly page: Page,
    private readonly id: number = STATIC_PAGE.id,
  ) {
    this.blocks = new MultiEditor(page);
  }

  get createPath(): string {
    return '/admin/static-pages/create';
  }

  get editPath(): string {
    return `/admin/static-pages/${this.id}/edit`;
  }

  get title(): Locator {
    return this.page.locator('#title');
  }

  get slugField(): Locator {
    return this.page.locator('#slug');
  }

  get summary(): Locator {
    return this.page.locator('#summary');
  }

  async tryGotoCreate(): Promise<Response | null> {
    return this.page.goto(this.createPath);
  }

  async tryGotoEdit(): Promise<Response | null> {
    return this.page.goto(this.editPath);
  }

  async gotoCreate(): Promise<void> {
    const response = await this.tryGotoCreate();
    expect(response?.status(), `GET ${this.createPath}`).toBe(200);
    await this.blocks.waitUntilReady();
  }

  async gotoEdit(): Promise<void> {
    const response = await this.tryGotoEdit();
    expect(response?.status(), `GET ${this.editPath}`).toBe(200);
    await this.blocks.waitUntilReady();
  }
}
