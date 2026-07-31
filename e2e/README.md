# E2E suite

Playwright + TypeScript, driving a **second instance of the app** on `:8080`
backed by a throwaway SQLite database. The local MySQL dev database is never
touched, so specs are free to create, edit and delete whatever they need.

```bash
npm run e2e                    # everything, headless
npm run e2e:core               # only the permanent net
npm run e2e -- --headed        # watch it happen
npm run e2e -- editor          # only specs matching "editor"
npm run e2e:ui                 # Playwright's interactive runner
npm run e2e:report             # open the last HTML report
E2E_SKIP_RESET=1 npm run e2e   # keep the current database between runs
```

One-time: `npm run e2e:setup` (downloads the Chromium binary).

## What belongs in a browser at all

**If a PHP integration test can assert it, it does not belong here.** A feature
test runs in milliseconds against a real request; a browser test costs seconds
and a maintenance burden. Authorisation rules, validation, what gets stored,
what a Blade template renders — all of that is cheaper and more precise in
`app/Domains/*/Tests/`.

What is left, and what this suite is for:

- client-side behaviour — Alpine, Quill, counters, drag-and-drop,
- which assets a page actually loads,
- anything that only exists after JavaScript has run,
- layout that breaks at a real viewport.

## Two kinds of spec

| | `tests/core/` | `tests/features/` |
|---|---|---|
| Lifetime | permanent | deleted at WRAP |
| Runs | every `npm run e2e` | while the feature is in flight |
| Bar to entry | guards something used across the app and breakable from anywhere | verifies one feature once |

`core/` is the net that must stay green after every feature, so it is kept
small on purpose — every spec in it costs everyone seconds on every run.
Everything else starts in `features/` and is expected to die there; see
[`tests/features/README.md`](tests/features/README.md).

Logging in is not a core spec: `support/auth.setup.ts` already logs every role
in and asserts each one reaches the dashboard, before any spec runs. If login
breaks, the whole suite fails first.

## How it hangs together

| Piece | Role |
|---|---|
| [`.env.e2e`](../.env.e2e) | `APP_ENV=e2e` ⇒ Laravel loads this file. SQLite, file sessions, its own cookie name. |
| [`playwright.config.ts`](../playwright.config.ts) | Boots `artisan serve --env=e2e` on `:8080`, one worker, traces on failure. |
| [`support/global-setup.ts`](support/global-setup.ts) | `migrate:fresh --seed --env=e2e` before the run. |
| `app/Domains/*/Database/Seeders/E2e*Seeder.php` | Write the fixture world — one account per role, one story, one chapter, one news. Each domain owns its own; `DatabaseSeeder` calls them when `APP_ENV=e2e`. |
| [`support/fixtures.ts`](support/fixtures.ts) | The same fixtures in TypeScript. **Keep in step with the seeder.** |
| [`support/auth.setup.ts`](support/auth.setup.ts) | Logs each role in once, parks cookies in `.auth/`. |
| [`support/test.ts`](support/test.ts) | The suite's `test`, with a per-role page fixture. |
| [`pages/`](pages/) | Page and component objects. Selectors live here and nowhere else. |

## Writing a spec

```ts
import { expect, test } from '../support/test';

test('an author can edit their chapter', async ({ author }) => {  // already logged in
  const edit = new ChapterEditPage(author);
  await edit.goto();
  await edit.content.fill('…');
  await edit.save();
});
```

Roles available as fixtures: `guest`, `user`, `confirmed`, `author`,
`moderator`, `admin`. Each gets its own browser context, so one role's session
can never leak into another's assertions.

New specs go in `tests/features/`. Rules that keep this cheap to own:

- **Selectors belong in `pages/`.** A spec that contains a CSS selector is a
  spec that breaks when the markup moves. Add a `data-testid` to the markup
  rather than reaching for a clever selector.
- **Prefer test ids over translated strings.** A label in a tab strip is
  present whatever tab is open, so text assertions pass vacuously.
- **Never clean up after yourself.** The database is rebuilt before every run;
  cleanup code is just another thing to get wrong.
- **Screenshots on failure only.** Playwright captures them, plus a trace you
  can open with `npm run e2e:report`. Do not add success screenshots.

## Gotchas

- Stories and chapters are addressed by **slug-with-id** (`histoire-e2e-1`);
  the app 301s anything else to that exact string. `E2eStorySeeder` pins the
  ids so the URLs in `fixtures.ts` are constants.
- Seeded accounts have **not** accepted the CGU, so the first login of a run
  goes through the terms page. `LoginPage` handles it and then proves it
  reached the dashboard — landing anywhere that is not `/login` proves nothing.
- The e2e app shares `storage/app` with dev, so uploads land in your dev media.
- `artisan serve` runs PHP's built-in server, not the nginx stack on `:80`.
  `PHP_CLI_SERVER_WORKERS` is set to 4 so asset requests do not serialise.
- Playwright kills only the local `sail` wrapper; `global-teardown.ts` stops
  the PHP process inside the container so `:8080` is actually released.
