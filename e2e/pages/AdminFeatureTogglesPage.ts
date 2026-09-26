import type { Locator, Page } from '@playwright/test';

export interface ToggleRef {
  readonly domain: string;
  readonly name: string;
}

/**
 * Admin → Commutateurs de fonctionnalité (`/admin/config/feature-toggles`)
 * and its edit page.
 *
 * Two tables: declared toggles, and — tech admins only, when there is any — the
 * « Non déclarés dans le code » orphan rows. Rows have no id; a row is the one
 * whose cells hold both the domain and the name.
 */
export class AdminFeatureTogglesPage {
  static readonly PATH = '/admin/config/feature-toggles';

  constructor(private readonly page: Page) {}

  async goto(): Promise<void> {
    await this.page.goto(AdminFeatureTogglesPage.PATH);
  }

  async gotoEdit(toggle: ToggleRef): Promise<void> {
    await this.page.goto(`${AdminFeatureTogglesPage.PATH}/${toggle.domain}/${toggle.name}/edit`);
  }

  get declaredTable(): Locator {
    return this.page.getByTestId('feature-toggles-declared');
  }

  get orphanTable(): Locator {
    return this.page.getByTestId('feature-toggles-orphans');
  }

  get createButton(): Locator {
    return this.page.getByRole('link', { name: /créer|nouveau|ajouter/i });
  }

  /** Header cells of the declared table. */
  get declaredHeaders(): Locator {
    return this.declaredTable.locator('thead th');
  }

  get declaredRows(): Locator {
    return this.declaredTable.locator('tbody tr');
  }

  declaredRow(toggle: ToggleRef): Locator {
    return this.rowIn(this.declaredTable, toggle);
  }

  orphanRow(toggle: ToggleRef): Locator {
    return this.rowIn(this.orphanTable, toggle);
  }

  /** The access badge (ON / OFF / PAR RÔLE) of a row. */
  accessBadge(row: Locator): Locator {
    return row.locator('td').nth(2).locator('span');
  }

  /** The roles cell of a declared row. */
  rolesCell(row: Locator): Locator {
    return row.locator('td').nth(3);
  }

  rowButtons(row: Locator): Locator {
    return row.getByRole('button');
  }

  /** Click the quick-access button (`ON`, `OFF`, `Par rôle`) of a row. */
  async setAccess(row: Locator, label: 'ON' | 'OFF' | 'Par rôle'): Promise<void> {
    await row.getByRole('button', { name: label, exact: true }).click();
    await this.page.waitForLoadState('load');
  }

  /** Delete an orphan, accepting the native `confirm()` dialog. */
  async deleteOrphan(row: Locator): Promise<void> {
    this.page.once('dialog', (dialog) => void dialog.accept());
    await row.getByRole('button', { name: 'Supprimer' }).click();
    await this.page.waitForLoadState('load');
  }

  /**
   * The success flash message. The admin layout and this page each render the
   * flash block, so two identical, stacked copies exist; take the first.
   */
  get flashSuccess(): Locator {
    return this.page.locator('div.surface-success > p').first();
  }

  // --- Edit page ------------------------------------------------------------

  get editForm(): Locator {
    return this.page.locator('form').filter({ has: this.page.locator('select#access') });
  }

  /** Every named field of the edit form, CSRF and method spoofing excluded. */
  async editFieldNames(): Promise<string[]> {
    const names = await this.editForm
      .locator('input[name], select[name], textarea[name]')
      .evaluateAll((els) => els.map((e) => e.getAttribute('name') ?? ''));
    return [...new Set(names.filter((n) => n !== '_token' && n !== '_method'))].sort();
  }

  async selectAccess(value: 'on' | 'off' | 'role_based'): Promise<void> {
    await this.page.locator('select#access').selectOption(value);
  }

  async checkRole(label: string): Promise<void> {
    await this.editForm.locator('label').filter({ hasText: label }).click();
  }

  async saveEdit(): Promise<void> {
    await this.editForm.getByRole('button', { name: 'Enregistrer' }).click();
    await this.page.waitForLoadState('load');
  }

  private rowIn(table: Locator, toggle: ToggleRef): Locator {
    return table
      .locator('tbody tr')
      .filter({ has: this.page.locator('td', { hasText: new RegExp(`^\\s*${toggle.domain}\\s*$`) }) })
      .filter({ hasText: toggle.name });
  }
}
