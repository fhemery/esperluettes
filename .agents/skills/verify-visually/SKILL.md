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

## 1. Decide what actually belongs here

Take the "Visual QA checklist" from `03-plan.md` and cut it down to rows a
browser can prove and a PHP test cannot:

- client-side behaviour (Alpine, Quill, counters, drag-and-drop),
- which assets a page loads,
- role-dependent visibility,
- layout that only breaks at a real viewport.

Everything else — rendering, validation, authorisation rules — belongs in the
PHP suite. If a row can be a feature test, push it back to BUILD and say so.
This cut is the difference between VERIFY costing minutes and costing hours.

## 2. Extend the suite

```bash
npm run e2e:setup      # first time only
./vendor/bin/sail up -d
```

- Fixtures first: if the feature needs data that does not exist, add it to the
  owning domain's `app/Domains/<domain>/Database/Seeders/E2e*Seeder.php` **and**
  to `e2e/support/fixtures.ts`. Never seed from inside a spec.
- Selectors go in `e2e/pages/`, as a page or component object. A spec
  containing a raw CSS selector is a spec that breaks when the markup moves.
- Add `data-test-id` to the *new* markup while you are in BUILD. Do not
  retrofit the rest of the app.
- One spec file per feature, in `e2e/tests/`. Use the per-role fixtures
  (`guest`, `user`, `confirmed`, `author`, `moderator`, `admin`) — role
  visibility is the failure mode this step exists to catch.

## 3. Run

```bash
npm run e2e -- <your-spec>
npm run e2e                    # then the whole suite, to catch regressions
```

The database is rebuilt before every run, so specs may write freely and must
not clean up.

If a spec passes first try and you are not sure it asserts anything, invert one
expectation and confirm it fails. A green test that cannot fail is worse than
no test.

## 4. Report

- Fill the checklist's `OK?` column: ✅, ❌ with the failure, or `n/a` with the
  reason.
- On failure, the trace and screenshot are already captured —
  `npm run e2e:report`. Do not add success screenshots; they cost tokens and
  prove nothing an assertion does not.
- Run `npm run gate` one last time.
- Report: rows covered by new specs, rows that failed, rows you could not
  automate and why.

Rows that genuinely cannot be automated — "does this spacing feel right", "is
this the correct shade" — go to the user as a short list to eyeball, not as a
screenshot dump.

A defect goes back to BUILD as a fix, not into the summary as a known issue,
unless the user decides otherwise.
