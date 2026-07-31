import { test as setup } from '@playwright/test';
import fs from 'node:fs';
import path from 'node:path';
import { LoginPage } from '../pages/LoginPage';
import { ACCOUNTS, ROLES, storageStatePath } from './fixtures';
import { ROOT } from './sail';

/**
 * Log every role in once and park its cookies on disk.
 *
 * Specs then start already authenticated instead of walking the login form,
 * which is both faster and one less thing to break when the form changes.
 */
for (const role of ROLES) {
  setup(`authenticate as ${role}`, async ({ page }) => {
    const file = path.join(ROOT, storageStatePath(role));
    fs.mkdirSync(path.dirname(file), { recursive: true });

    await new LoginPage(page).loginAs(ACCOUNTS[role]);
    await page.context().storageState({ path: file });
  });
}
