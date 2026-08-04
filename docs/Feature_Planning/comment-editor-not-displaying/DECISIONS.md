# Comment editor not displaying — decisions log

Append-only. Every question the user arbitrated, with the answer as given.
BUILD reads this before asking anything already settled.

Format: one row per decision. Never edit a row — if a decision is reversed, add
a new row that supersedes it and note the number.

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|
| 1 | 2026-08-04 | REFINE | Mode | `auto` (bug fix); user invited clarifying questions if needed | — |
| 2 | 2026-08-04 | REFINE | E2E after fix? | Yes — permanent Playwright coverage for comment reply/edit (user: "going to need some E2E tests on this one after it is fixed") | — |
| 3 | 2026-08-04 | DESIGN | Where to load Editor assets | Comment list shell: `@include('editor::components._assets')` when `!$isGuest && !$error` (auto-picked A) | — |
| 4 | 2026-08-04 | DESIGN | Reply open re-init | Mirror edit: call `initQuillEditor` when opening Répondre (auto-picked A) | — |
| 6 | 2026-08-04 | BUILD | E2E seeder insert path | `DB::table` direct insert (pinned id 1) — avoids `CommentPosted` side effects and 140-char API validation | — |

## Assumptions made without asking

Used in `auto` mode, or when the user was unavailable. Each one is a decision
the user may want to reverse — surface these in the WRAP summary.

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| 1 | Only chapter comments are in scope (sole shipped Comment host) | REFINE | Yes |
| 2 | E2E must cover the `canCreateRoot=false` path (author / already-rooted), not only users who see the root form | REFINE | Yes |
| 3 | E2E covers both Répondre and Éditer | REFINE | Yes |
| 4 | No policy / API behaviour change — restore editor display only | REFINE | Yes |
| 5 | Likely cause: Editor Vite assets never pushed when root `<x-editor::rich-text>` is absent; fragment `@push` discarded — fix ensures assets on the comment list/host | REFINE | Yes (DESIGN may refine) |
