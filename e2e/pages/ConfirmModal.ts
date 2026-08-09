import { type Locator, type Page } from '@playwright/test';

/**
 * Component object for `<x-shared::confirm-modal>`.
 *
 * The modal renders no `role="dialog"`, and every instance on the page sits at
 * the same depth with `display: none` until Alpine opens it — so the only
 * handle is "the overlay that is currently visible". Scoping by title would not
 * help: the hidden ones carry their title too.
 *
 * Its confirm side is a real `<form method="POST">`, so confirming is a
 * navigation, not a JS call.
 */
export class ConfirmModal {
  constructor(private readonly page: Page) {}

  /** The one overlay Alpine has opened, if any. */
  get root(): Locator {
    return this.page.locator('div.fixed.inset-0.overflow-y-auto:visible');
  }

  /** The white card, i.e. the overlay minus its backdrop — what has to fit the screen. */
  get panel(): Locator {
    return this.root.locator('> div').last();
  }

  get title(): Locator {
    return this.root.locator('h2');
  }

  get body(): Locator {
    return this.root.locator('p');
  }

  get cancelButton(): Locator {
    return this.root.locator('button[type="button"]');
  }

  /** The submit inside the modal's own form — never the page's. */
  get confirmButton(): Locator {
    return this.root.locator('form button[type="submit"]');
  }

  async cancel(): Promise<void> {
    await this.cancelButton.click();
  }

  async confirm(): Promise<void> {
    await this.confirmButton.click();
  }
}
