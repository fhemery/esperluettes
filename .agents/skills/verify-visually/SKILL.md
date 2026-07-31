---
name: verify-visually
description: Verify a finished feature in a real browser by adding specs to the E2E suite. Use at the VERIFY step of the loop, or when asked to check that a change actually works in the UI rather than only in tests. Extends e2e/ with page objects and specs, runs them per role, and reports against the visual QA checklist.
---

# Verify in a browser

Tests render Blade; they do not run Alpine. Anything client-side, any asset
loading, any "the tab appeared for the wrong user" bug is only provable in a
browser. That is what this step is for.

**The output of this step is specs that stay.** Not a throwaway script — a spec
in [`e2e/`](../../../e2e/README.md) that runs on every later `npm run e2e` and
catches the regression next time. Read `e2e/README.md` before writing anything.

## 1. Cut the checklist down — most rows do not belong here

**A row belongs in the browser only if a PHP integration test cannot assert
it.** That is the whole filter, and applying it honestly is what decides
whether VERIFY costs minutes or hours.

Keep:

- client-side behaviour (Alpine, Quill, counters, drag-and-drop),
- which assets a page actually loads,
- anything that only exists after JavaScript has run,
- layout that only breaks at a real viewport.

Push back to BUILD as a feature test — do not write these as specs:

- authorisation ("a non-author gets a 404"),
- validation and what gets stored,
- what a Blade template renders for a given state,
- redirects and status codes.

Say in your report which rows you moved and where. A row that neither a
feature test nor a browser can settle — "does this spacing feel right" — goes
to the user as a question, not as a screenshot.

## 2. Write the spec in `features/`, not `core/`

```bash
npm run e2e:setup      # first time only
./vendor/bin/sail up -d
```

New specs go in `e2e/tests/features/<task-slug>.spec.ts`. They are
**temporary**: at WRAP they are deleted, or promoted to `e2e/tests/core/` if —
and only if — they guard something used across the app and breakable from
anywhere. `core/` is the permanent net and stays small; every spec in it costs
every future run.

- Fixtures first: if the feature needs data that does not exist, add it to the
  owning domain's `app/Domains/<domain>/Database/Seeders/E2e*Seeder.php` **and**
  to `e2e/support/fixtures.ts`. Never seed from inside a spec.
- Selectors go in `e2e/pages/`, as a page or component object. A spec
  containing a raw CSS selector is a spec that breaks when the markup moves.
- Add `data-testid` to the *new* markup while you are in BUILD, and reach for
  it rather than for a clever selector. Do not retrofit the rest of the app.
- Use the per-role page fixtures (`guest`, `user`, `confirmed`, `author`,
  `moderator`, `admin`) from `e2e/support/test.ts`.

## 3. Run

```bash
npm run e2e -- <your-spec>
npm run e2e                    # then the whole suite, to catch regressions
```

The database is rebuilt before every run, so specs may write freely and must
not clean up.

If a spec passes first try and you are not sure it asserts anything, invert one
expectation and confirm it fails. A green test that cannot fail is worse than
no test — and a spec that passes while the session is not really logged in has
happened here before.

## 4. Report

- Fill the checklist's `OK?` column: ✅, ❌ with the failure, or `n/a` with the
  reason.
- On failure, the trace and screenshot are already captured —
  `npm run e2e:report`. Do not add success screenshots; they cost tokens and
  prove nothing an assertion does not.
- Run `npm run gate` one last time.
- Report: rows covered by new specs, rows pushed back to BUILD as feature
  tests, rows that failed, rows you could not automate and why.

Rows that genuinely cannot be automated — "does this spacing feel right", "is
this the correct shade" — go to the user as a short list to eyeball, not as a
screenshot dump.

A defect goes back to BUILD as a fix, not into the summary as a known issue,
unless the user decides otherwise.

## 5. At WRAP

State, for each spec in `e2e/tests/features/`, whether it is deleted or
promoted to `core/`. Deleting is the default and needs no justification;
promoting does, written into the spec's header comment. A feature spec left
behind after WRAP is a bug in the process.
