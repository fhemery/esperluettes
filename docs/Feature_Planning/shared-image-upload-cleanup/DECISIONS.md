# SecretGift gift images on Media (private) — decisions log

Append-only. Every question the user arbitrated, with the answer as given.
BUILD reads this before asking anything already settled.

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|
| 1 | 2026-07-31 | REFINE | Accept leftover request (collapse into SecretGift)? | Accepted, then **rejected after VERIFY** — wrong architecture. | — |
| 2 | 2026-07-31 | DESIGN | Privacy for gift images on Media? | **A** — extend Media with private/auth-gated store+serve; gifts stay confidential. Not B (public disk) or C (UI-only). | reverses prior A2 "never Media" |
| 3 | 2026-07-31 | DESIGN | Add `allowLibrary` to `<x-media::image-field>`? | Yes. | — |
| 4 | 2026-07-31 | REFINE | Gift text / Editor? | Leave `<x-editor::rich-text>` as-is. | — |
| 5 | 2026-07-31 | REFINE | Sound in this task? | No — image only; push sound→Media to backlog. | supersedes prior A3 (collapse sound into SecretGift) |
| 6 | 2026-07-31 | — | First implementation (collapse into SecretGift)? | **Reverted** (commits 6260fe45 / 580d1024). | — |

## Assumptions made without asking

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| A1 | Private disk = Laravel `private` (`storage/app/private`), not `local`. Migrate existing gift images off `local`. | DESIGN | Mild — path/disk only |
| A2 | `MediaPublicApi::storePrivate` + `stream`; Calendar keeps `secret-gift.image` route and `canViewImage` — Media never learns SecretGift rules. | DESIGN | Expensive if inverted toward a Media visibility registry |
| A3 | Private gifts store **original only** (no `-400w`/`-800w` variants). | DESIGN | Yes — can add widths later |
| A4 | Deferred GC for gift images via `SecretGiftMediaUsageProvider`; no synchronous file delete on replace/remove. | DESIGN | Yes |
| A5 | Scope string `secret-gift/{activityId}`; path-prefix implies private disk. | DESIGN | Yes |
| A6 | Delete `<x-shared::image-upload>` Blade after migration; keep Shared lang; leave `<x-shared::sound-upload>` in Shared. | DESIGN | Yes |
| A7 | One-shot data move for existing `gift_image_path` files (`local` → `private` + Media path shape) in BUILD. | DESIGN | Yes — re-upload-only is worse |
| A8 | VERIFY skipped unless user asks; smoke-check checklist remains in the plan. | WRAP | — |
| A9 | `MediaPublicApi::stream()` ships as `stream(string $path, array $headers = [])` — without the `?int $width` listed in architecture §3.1. A3 means private images have no variants, so the parameter would select nothing. | PLAN | Yes — re-add when private variants exist |
| A10 | Media's zero-claim GC guard is applied at the **private scope root** (`secret-gift/`), swept recursively, not per `secret-gift/{activityId}` folder. Per-folder would make an activity with no remaining claimed gift permanently unsweepable, contradicting `01-functional.md` §5 (shuffle frees paths for GC). Public-disk behaviour is unchanged. | PLAN | Yes |
| A11 | `hasVariants()` is left untouched and returns `false` for a private path (it looks on the `public` disk) rather than throwing like `originalUrl` / `variantUrl`. `false` is the true answer under A3 and is what a renderer needs; throwing would buy nothing. | BUILD (phase 1) | Yes |
| A12 | `store()` refuses a private scope symmetrically to `storePrivate()` refusing a public one, so a typo in a scope string cannot put confidential bytes on the `public` disk. The plan only asked for the test; this is the guard behind it. | BUILD (phase 1) | Yes |
| A13 | Phases 1 and 2 were built concurrently in the **same worktree**, so phase 2's `git commit` swept up phase 1's simultaneously-staged files: both ship as the single commit `0eb759e3`, whose message describes both halves. Not split afterwards — rewriting history under a live sibling build was the larger risk. Consequence: phase 1 has no commit of its own. | BUILD ph.2 | — |
| A14 | `LegacyGiftImageMover` copies bytes with `Storage::disk()` on both `local` and `private` directly, rather than through `MediaPublicApi`. Architecture §1 says Calendar never touches disks for images, but the public API can only *store an upload* — it cannot adopt an existing file — and the plan's phase-3 deliverable specifies the copy. Scoped to the one-shot move; normal saves still go through `storePrivate`. | BUILD (phase 3) | Yes — a Media `adopt()` would remove it |
| A15 | The mover's report is `array{moved:int, already_migrated:int, missing:array<assignmentId,path>}` — missing sources are keyed by assignment id so the deploy log names the rows to fix by hand. | BUILD (phase 3) | Yes |
| A16 | SecretGift tests assert an upload leaves the `public` and `local` disks **unchanged** rather than empty: the user factories seed `profile_pictures/*.svg` on the public fake disk, so "empty" was never the right assertion. Same guarantee, delta-based. | BUILD (phase 3) | — |
| A17 | The new Shared test is `app/Domains/Shared/Tests/Feature/View/Components/UploadComponentsTest.php`, not the plan's `Feature/SharedUploadComponentsTest.php` (open item O8). No existing Shared feature test fits — they are all page-level — and `Feature/View/Components/` is the domain's existing home for component tests. | BUILD (phase 4) | Yes |
| A18 | `saveGift()` **never adopts** a submitted `gift_image.path`: a non-empty path means "keep whatever the row holds", an empty one means removal. The plan said "assign it if it differs", which is a privacy hole — `canViewImage()` grants the giver unconditional read, so a crafted path would let any giver stream any file on the private disk. With `allowLibrary=false` the form has no legitimate way to produce a *different* path, so nothing is lost. Locked by a denial test (`it ignores a submitted path that is not the stored one`). | BUILD (phase 4) | Expensive to invert — would need a per-scope ownership check in SecretGift |
| A19 | The "lang keys survive" test asserts `Lang::has('shared::image-upload.…', 'fr')` rather than comparing `__()` output: the suite runs under `APP_LOCALE=zz` (see `TranslationKeysExistTest`), where every `__()` returns its own key and a string comparison would pass vacuously. | BUILD (phase 4) | — |
