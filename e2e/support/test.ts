import { test as base, type Browser, type Page } from '@playwright/test';
import path from 'node:path';
import { type RoleName, storageStatePath } from './fixtures';
import { ROOT } from './sail';

/**
 * The suite's `test`. Import this, never `@playwright/test`'s.
 *
 * Adds one fixture per role, each a page already logged in as that role, plus
 * `guest` for the logged-out case. Sessions live in the browser context, so
 * every role gets its own context — sharing one would silently carry the
 * previous role's cookie into the next check, which is precisely the class of
 * bug this suite exists to catch.
 *
 * Fixtures are lazy: a test that only asks for `author` never opens the other
 * five contexts.
 */

type RolePages = { [K in RoleName]: Page } & { guest: Page };

function roleFixture(role: RoleName | null) {
  return async ({ browser }: { browser: Browser }, use: (page: Page) => Promise<void>): Promise<void> => {
    const context = await browser.newContext(
      role ? { storageState: path.join(ROOT, storageStatePath(role)) } : {},
    );
    const page = await context.newPage();

    await use(page);

    await context.close();
  };
}

export const test = base.extend<RolePages>({
  guest: roleFixture(null),
  admin: roleFixture('admin'),
  moderator: roleFixture('moderator'),
  author: roleFixture('author'),
  confirmed: roleFixture('confirmed'),
  user: roleFixture('user'),
});

export { expect } from '@playwright/test';
