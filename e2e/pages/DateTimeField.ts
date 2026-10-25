import { type Locator, type Page } from '@playwright/test';

/**
 * Component object for `<x-shared::datetime-local-input>`: a date input (it
 * carries the field id), a time input beside it, and the hidden input that
 * carries the form field name and is what the server receives.
 *
 * Built from the field's id; the hidden input is `#{id}_value`. A read-only
 * mirror has no hidden input.
 */
export class DateTimeField {
  readonly date: Locator;
  readonly time: Locator;
  readonly hidden: Locator;
  readonly group: Locator;

  constructor(private readonly page: Page, readonly id: string) {
    this.date = page.locator(`input#${id}`);
    this.group = this.date.locator('xpath=..');
    this.time = this.group.locator('input[type="time"]');
    this.hidden = page.locator(`input#${id}_value`);
  }

  /** The `<label for>` of the field. */
  get label(): Locator {
    return this.page.locator(`label[for="${this.id}"]`);
  }

  async fill(date: string, time?: string): Promise<void> {
    await this.date.fill(date);
    if (time !== undefined) await this.time.fill(time);
  }

  async clearDate(): Promise<void> {
    await this.date.fill('');
  }

  /** What the form would submit right now, before the submit handler runs. */
  async submitted(): Promise<string> {
    return this.hidden.inputValue();
  }

  async values(): Promise<{ date: string; time: string }> {
    return { date: await this.date.inputValue(), time: await this.time.inputValue() };
  }
}
