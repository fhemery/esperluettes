import { AdminSidebar } from '../../pages/AdminSidebar';
import { ADMIN_NAV_KEYS } from '../../support/admin-nav-map';
import { expect, test } from '../../support/test';
import type { Page } from '@playwright/test';

/**
 * CORE — admin sidebar inventory for staff roles.
 *
 * Domains register admin pages with role lists in their service providers.
 * This suite freezes the effective set so a missing or extra link fails
 * before production. Update `e2e/support/admin-nav-map.ts` when registrations
 * change.
 */

async function assertSidebarKeys(page: Page, role: keyof typeof ADMIN_NAV_KEYS): Promise<void> {
  const sidebar = new AdminSidebar(page);
  await sidebar.goto();
  const actual = await sidebar.collectNavKeys();
  expect(actual).toEqual([...ADMIN_NAV_KEYS[role]]);
}

test('moderator sees exactly the mapped admin sidebar keys', async ({ moderator }) => {
  await assertSidebarKeys(moderator, 'moderator');
});

test('admin sees exactly the mapped admin sidebar keys', async ({ admin }) => {
  await assertSidebarKeys(admin, 'admin');
});

test('tech_admin sees exactly the mapped admin sidebar keys', async ({ tech_admin }) => {
  await assertSidebarKeys(tech_admin, 'tech_admin');
});
