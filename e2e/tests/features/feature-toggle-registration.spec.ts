/**
 * VERIFY of feature-toggle-registration: the admin toggle page built on
 * declarations, its orphan section, and a row-less declared toggle taking
 * effect once switched on. Temporary — deleted at WRAP unless promoted.
 *
 * Tests run in declaration order against one database: the fresh-state checks
 * come first, then the ones that write.
 */
import { AdminFeatureTogglesPage } from '../../pages/AdminFeatureTogglesPage';
import { FEATURE_TOGGLES } from '../../support/fixtures';
import { expect, test } from '../../support/test';

const { declared, orphan } = FEATURE_TOGGLES;

test.describe('feature toggle admin page', () => {
  test('a regular admin sees neither the tech-only toggle nor orphans, and a readable empty state', async ({
    admin,
  }) => {
    const page = new AdminFeatureTogglesPage(admin);
    await page.goto();

    await expect(page.declaredTable).toBeVisible();
    await expect(page.declaredRow(declared)).toHaveCount(0);
    await expect(page.orphanTable).toHaveCount(0);
    await expect(page.declaredRows).toHaveCount(1);
    await expect(page.declaredRows.first()).toHaveText('Aucun feature toggle.');
    await expect(page.declaredRows.first()).toBeInViewport();
  });

  test('a tech admin sees the row-less declared toggle as OFF, no create button, no extra column', async ({
    tech_admin,
  }) => {
    const page = new AdminFeatureTogglesPage(tech_admin);
    await page.goto();

    const row = page.declaredRow(declared);
    await expect(row).toHaveCount(1);
    await expect(page.accessBadge(row)).toHaveText('OFF');
    await expect(page.createButton).toHaveCount(0);
    await expect(page.declaredHeaders).toHaveText(['Domaine', 'Nom', 'Accès', 'Rôles', 'Actions']);
  });

  test('a tech admin sees the orphan row with a delete button only', async ({ tech_admin }) => {
    const page = new AdminFeatureTogglesPage(tech_admin);
    await page.goto();

    const row = page.orphanRow(orphan);
    await expect(row).toHaveCount(1);
    await expect(row).toContainText('Non déclaré dans le code');
    await expect(page.rowButtons(row)).toHaveText(['Supprimer']);
    await expect(page.declaredRow(orphan)).toHaveCount(0);
  });

  // The pre-existing admin-table pattern (A31): the page never scrolls
  // sideways; the table container does, and scrolling it reveals the actions.
  test('at 375px the page does not scroll and the actions are reached by scrolling each table', async ({
    tech_admin,
  }) => {
    await tech_admin.setViewportSize({ width: 375, height: 800 });
    const page = new AdminFeatureTogglesPage(tech_admin);
    await page.goto();

    const pageOverflow = await tech_admin.evaluate(
      () => document.documentElement.scrollWidth - window.innerWidth,
    );
    expect(pageOverflow, 'page scrolls horizontally').toBeLessThanOrEqual(0);

    for (const [container, row] of [
      [page.declaredTable, page.declaredRow(declared)],
      [page.orphanTable, page.orphanRow(orphan)],
    ] as const) {
      expect(await page.horizontalScrollRange(container), 'table container scrolls sideways').toBeGreaterThan(0);

      await page.scrollToEnd(container);

      const buttons = page.rowButtons(row);
      const count = await buttons.count();
      expect(count).toBeGreaterThan(0);
      for (let i = 0; i < count; i++) {
        await expect(buttons.nth(i), `button ${i} of ${await row.innerText()}`).toBeInViewport();
      }
    }
  });

  test('deleting the orphan removes it and flashes success', async ({ tech_admin }) => {
    const page = new AdminFeatureTogglesPage(tech_admin);
    await page.goto();

    await page.deleteOrphan(page.orphanRow(orphan));

    await expect(page.flashSuccess).toContainText('Feature toggle supprimé avec succès.');
    await expect(page.orphanTable).toHaveCount(0);
  });

  test('switching the row-less toggle ON sticks and shows the dark-mode setting', async ({ tech_admin }) => {
    const darkModeSetting = tech_admin.getByText('Choisissez le mode clair ou sombre.');
    await tech_admin.goto('/settings?tab=general');
    await expect(tech_admin.getByText('Personnalisez l\'apparence du site.')).toBeVisible();
    await expect(darkModeSetting).toHaveCount(0);

    const page = new AdminFeatureTogglesPage(tech_admin);
    await page.goto();

    await page.setAccess(page.declaredRow(declared), 'ON');
    await expect(page.flashSuccess).toContainText('Accès mis à jour avec succès.');

    await tech_admin.reload();
    await expect(page.accessBadge(page.declaredRow(declared))).toHaveText('ON');

    await tech_admin.goto('/settings?tab=general');
    await expect(darkModeSetting).toBeVisible();
  });

  test('the edit page has access and roles only, and a role-based save shows the role', async ({ tech_admin }) => {
    const page = new AdminFeatureTogglesPage(tech_admin);
    await page.gotoEdit(declared);

    expect(await page.editFieldNames()).toEqual(['access', 'roles[]']);

    await page.selectAccess('role_based');
    await page.checkRole('Moderator');
    await page.saveEdit();

    await expect(page.flashSuccess).toContainText('Feature toggle mis à jour avec succès.');
    const row = page.declaredRow(declared);
    await expect(page.accessBadge(row)).toHaveText('PAR RÔLE');
    await expect(page.rolesCell(row)).toHaveText('moderator');
  });
});
