import { expect, type Locator } from '@playwright/test';

/**
 * One `chapter-choice` block inside `<x-editor::multi>` (Story's plugin block).
 *
 * The block's own Alpine scope moves rows in the DOM and re-indexes their field
 * names; its controls are addressed by their `x-on:click` handlers, which are
 * deliberately distinct from the multi-editor's block controls.
 */
export class ChapterChoiceBlock {
  constructor(readonly root: Locator) {}

  async uid(): Promise<string | null> {
    return this.root.getAttribute('data-uid');
  }

  get rows(): Locator {
    return this.root.locator('[data-choice-list] > [data-choice]');
  }

  row(index: number): Locator {
    return this.rows.nth(index);
  }

  target(index: number): Locator {
    return this.row(index).locator('select');
  }

  label(index: number): Locator {
    return this.row(index).locator('input[type="text"]');
  }

  enabled(index: number): Locator {
    return this.row(index).locator('input[type="checkbox"]');
  }

  /** Every submitted field name of the nth row. */
  async names(index: number): Promise<string[]> {
    return this.row(index).locator('[name]').evaluateAll(els => els.map(e => e.getAttribute('name') ?? ''));
  }

  async addChoice(): Promise<void> {
    const before = await this.rows.count();
    await this.root.locator('button[x-on\\:click="add($el)"]').click();
    await expect(this.rows).toHaveCount(before + 1);
  }

  async fill(index: number, target: string, label: string, enabled = true): Promise<void> {
    await this.target(index).selectOption({ label: target });
    await this.label(index).fill(label);
    await this.enabled(index).setChecked(enabled);
  }

  async moveDown(index: number): Promise<void> {
    await this.row(index).locator('button[x-on\\:click="down($el)"]').click();
  }

  async moveUp(index: number): Promise<void> {
    await this.row(index).locator('button[x-on\\:click="up($el)"]').click();
  }

  async remove(index: number): Promise<void> {
    const before = await this.rows.count();
    await this.row(index).locator('button[x-on\\:click="remove($el)"]').click();
    await expect(this.rows).toHaveCount(before - 1);
  }
}
