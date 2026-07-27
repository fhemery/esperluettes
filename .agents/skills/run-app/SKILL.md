---
name: run-app
description: Run, drive and screenshot the Esperluettes app in a real browser. Use when asked to run or start the app, take a screenshot, click through a page, verify a change works in the real UI (not just tests), or reproduce a UI bug. Covers Sail startup, the Playwright driver, login, and writing flows.
---

# Run and drive the app

Laravel + Blade + Alpine, served by Sail at `http://localhost`. There is no
separate dev server to start for page rendering — Sail serves the app; Vite is
only needed for asset rebuilds.

The app is driven with **Playwright through `.agents/skills/run-app/driver.mjs`**.
Do not write a new browser driver, and do not install Playwright by hand: it is a
devDependency and the setup script fetches the browser binary.

All paths below are relative to the repo root.

## One-time setup

```bash
npm install          # playwright is a devDependency
npm run browser:setup
```

`browser:setup` is idempotent and fast once satisfied — run it whenever you are
unsure. It checks the package, downloads the Chromium binary if missing
(~115 MB, into `~/.cache/ms-playwright`, outside the repo), proves it launches
headless, and pings the app. Expected output:

```
  ok    playwright 1.62.0
  ok    chromium binary present
  ok    chromium launches headless
  ok    app responding at http://localhost (200)
```

If the app is not responding: `./vendor/bin/sail up -d`.

## Credentials

Never commit them. The driver reads:

```bash
export APP_USER='you@example.com'
export APP_PASSWORD='…'          # a local seeded account
export APP_BASE_URL='http://localhost'   # optional, this is the default
```

Ask the user for a local account if you do not have one. `login()` fails loudly
with the app's own error message rather than silently continuing as a guest.

## Run: a flow (the main path)

A flow is a `.mjs` file that default-exports `async ({ page, ctx, browser, helpers })`.
Run one:

```bash
APP_USER=… APP_PASSWORD=… npm run browser:drive -- .agents/skills/run-app/flows/profile-tabs.mjs
```

`flows/profile-tabs.mjs` is a complete worked example — copy it. It logs in,
walks every profile tab, flips a user setting through the settings UI, asserts
the tab strip reacts, then repeats the public checks in a second browser context
as a guest. Output is `PASS`/`FAIL` lines; the process exits `1` if any check
fails or the driver throws, so flows work in CI.

Helpers passed in as `helpers`:

| Helper | What it does |
|---|---|
| `login(page, email?, password?)` | Real login form; throws with the app's error text on failure |
| `goto(page, '/path')` | Navigate, returns the HTTP status |
| `shot(page, 'name')` | Screenshot into `.agents/skills/run-app/shots/`, returns the path |
| `tabStrip(page)` | Reads any `x-shared::scrollable-tabs` strip: `{key, label, selected, icon}` |
| `toggleSetting(page, tab, 'Title')` | Flips a boolean user setting and saves it |
| `check(name, ok, detail?)` | Prints PASS/FAIL and records failures |

The driver logs every response `>= 400` and every page error at the end, so a
silent 500 inside a tab cannot pass unnoticed.

**Take a screenshot and actually open it.** A flow that passes while rendering a
blank page is a flow that asserted nothing.

## Run: one-off poking

For a quick look, skip the flow file:

```bash
APP_USER=… APP_PASSWORD=… npm run browser:drive -- \
  --goto /profile/logistix/quotes \
  --shot adhoc.png \
  --eval "document.querySelectorAll('[role=\"tab\"]').length"
```

`--as <email>` overrides `APP_USER`. Omit both to browse as a guest.

## Run: human path

`./vendor/bin/sail up -d`, then open `http://localhost`. Useless to an agent with
no display — use the driver.

## Test

```bash
./vendor/bin/sail artisan test:parallel                 # full suite
./vendor/bin/sail artisan test app/Domains/Profile      # one domain
./vendor/bin/sail composer deptrac                      # architecture rules
```

Tests are not a substitute for driving the app: they render Blade, but they do
not run Alpine, so anything client-side is only proven in the browser.

## Gotchas

- **`/profile` does not redirect to `/profile/{slug}`.** It renders the own
  profile at `/profile`, so parsing the slug out of the URL yields the literal
  string `profile` and every subsequent request 500s. Read the slug from a tab
  link instead — see the flow.
- **Settings toggles have no clickable input.** The switch is
  `<input type="checkbox" class="sr-only peer">` inside a `<label>`; Playwright
  cannot click the input. Click the label. `toggleSetting()` handles this, and
  scopes to the right row via `div[x-data^="settingsParameterRow"]` filtered by
  title — several nested divs match the title text, so an unscoped locator
  matches the wrong element and times out.
- **Saving a setting is per-row.** Each row has its own *Enregistrer* button;
  there is no global save. Click the one inside the row you changed.
- **Asserting on translated text is fragile.** A tab's label appears in the tab
  strip whatever tab is active, so "component rendered" assertions that look for
  its heading pass vacuously. Assert on `data-test-id` or on `aria-selected`.
- **The test locale is `zz`,** which renders raw translation keys — that is in
  the PHP test suite, not the browser. In the browser you get French, so match
  on French strings or on markers.
- **A 404 for `storage/news/…webp` on the homepage is pre-existing** and
  unrelated to whatever you are testing.

## Troubleshooting

| Symptom | Fix |
|---|---|
| `playwright is missing from node_modules` | `npm install` |
| `chromium will not launch` naming a missing `.so` | `npx playwright install-deps chromium` |
| `no response from http://localhost` | `./vendor/bin/sail up -d` |
| `Login failed for …` | Wrong `APP_USER`/`APP_PASSWORD`; the message includes the app's own error |
| `No settings row titled "…"` | The label is French and must match exactly, e.g. `Masquer mon carnet de citations` |
| Flow times out clicking a toggle | You are clicking the `sr-only` input — click the `label` (see Gotchas) |
