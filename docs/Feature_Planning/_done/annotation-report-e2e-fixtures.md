# Chapter annotations — e2e fixtures for reaction and co-author reply reports

**Status:** DONE — 2026-11-01 · **Domain(s):** `Comment` (e2e seeder only)

## What it does

Test support only; no production code. Seeds two annotations on chapter 11,
root 102 (`user`'s), so a browser spec can see « Signaler » on a reaction and
on a co-author's reply. Closes the rendering gap left by `annotations-v4`
VERIFY; the report rules themselves stay pinned by PHP tests.

## Seeded data

| Row | Where | Detail |
|-----|-------|--------|
| Reaction | root 102, author `user` | body `<p>❤️</p>`, highlighted text `Une chouette observe` (free passage) |
| Co-author reply | root 102, author `tech_admin` | reply to `user`'s `traverse la clairière` annotation, body `Précision de la coautrice.` |

`tech_admin` is co-author (role `author`) of story 6. The seeder's account
lookup now includes it and the guard is `count() < 5`.

## Where the code lives

| Path | Role |
|------|------|
| `app/Domains/Comment/Database/Seeders/E2eCommentsSeeder.php` | `seedInChapterAnnotations()` inserts both rows |
| `e2e/support/fixtures.ts` | `ANNOTATED.user.owl`, `bodies.reaction`, `bodies.coauthorReply` |
| `e2e/tests/core/chapter-annotation-report.spec.ts` | new test `author reports a reaction and a co-author reply from the pop-up` |
| `e2e/tests/core/chapter-annotation-overlay.spec.ts` | `AUTHOR_TINT` + user-filter exclusion gain `U.owl` |

## Counter-intuitive bits

- Rows are on `user`'s root 102, never `confirmed`'s root 101: the
  `confirmed` popover test asserts exactly one « Signaler » on the fox popover.
- The overlay spec asserts the **exact** tint list of chapter 11: any new
  annotation there must be added to `AUTHOR_TINT` and to the user-filter exclusion.
- Plan vs code: the plan wrote the account email `tech_admin@e2e.test`; the
  seeded one is `tech-admin@e2e.test` (hyphen).
- The spec is already in `core/`; no feature spec to retire.

## Verification

Full `pnpm run e2e` 41/41 and `pnpm run gate` green. Playwright is not in the gate.

## Not done

- Non-goals: re-testing report rules in the browser; new accounts/chapter/story;
  reaction or co-author cases on `confirmed`'s root 101.
- Cut mid-build: nothing. Open questions: none.
