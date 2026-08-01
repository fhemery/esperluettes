# MultiEdit — advanced mode for static pages — decisions log

Append-only. Every question the user arbitrated, with the answer as given.
BUILD reads this before asking anything already settled.

Format: one row per decision. Never edit a row — if a decision is reversed, add
a new row that supersedes it and note the number.

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|
| 1 | 2026-08-01 | REFINE | Literal News mirror, or stricter Phase 5a validation (path-in-scope, summed-text min/max)? | Literal News mirror — same validation, mode rules, and behaviour as News today. | — |
| 2 | 2026-08-01 | REFINE | Header image placement relative to the body editor? Also apply to News? | Put the header image **above** the simple/advanced body editor (currently below). Do the same reorder on the News admin form in this task. | — |
| 3 | 2026-08-01 | REFINE | Exact admin field order for StaticPage and News? | **title → slug → header image → summary → body** on both forms. | 2 |
| 4 | 2026-08-01 | REFINE | Final scope confirmation after replay? | Only two deliverables: (1) StaticPage body → MultiEdit (News mirror); (2) reorder StaticPage and News create/edit forms. Nothing else. | — |
| 5 | 2026-08-01 | DESIGN | Extract shared MultiEdit persistence into Editor, or copy News’s service pattern into StaticPage? | **A** — Duplicate the News pattern in StaticPage (service / request / form / usage provider). Do not extract in this task. | — |
| 6 | 2026-08-01 | DESIGN | Add a backlog refactor to share MultiEdit persistence across News, Chapters, StaticPage, future FAQ? | **No.** A full shared `resolveContent` does not apply everywhere: Chapters diverge (profile `multiedit-narrative`, strip external links, `chapters/{userId}` scope, simple-mode purify in the request, alt via `required_if`). FAQ is rich-text only today. News+StaticPage could share a narrow advanced helper later; that is not worth a backlog row until a third News-like consumer exists (e.g. FAQ adopts blocks). | — |

## Assumptions made without asking

Used in `auto` mode, or when the user was unavailable. Each one is a decision
the user may want to reverse — surface these in the WRAP summary.

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| 1 | No new events, notifications, settings, search indexing, or length limits beyond News parity. | REFINE | Yes |
| 2 | No data backfill — existing static pages keep `content_blocks = null` (Simple). | REFINE | Yes |
| 3 | StaticPage drops the standalone Media section once the header sits mid-form. | REFINE | Yes |
| 4 | Settings / status / meta blocks on StaticPage and News admin forms stay where they are; only the five named fields (title → slug → header → summary → body) are reordered. | DESIGN | Yes |
| 5 | Existing StaticPage `PATCH` publish/unpublish routes are untouched (out of scope; WAF rule is pre-existing). | DESIGN | Yes |
