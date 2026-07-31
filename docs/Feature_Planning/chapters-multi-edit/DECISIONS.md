# Chapters — MultiEdit content — decisions log

Append-only. Every question the user arbitrated, with the answer as given.
BUILD reads this before asking anything already settled.

Format: one row per decision. Never edit a row — if a decision is reversed, add
a new row that supersedes it and note the number.

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|
| 1 | 2026-07-31 | REFINE | Which backlog row does this session take, with a second worktree (`esperluettes-bis`) live on the e2e suite? | `chapters-multi-edit/`, in `interactive` mode. Do not run `annotations/` or `quotes-author-view/` in the other worktree while this is in flight — all three touch per-block anchoring. | — |
| 2 | 2026-07-31 | REFINE | The `editor-domain-visual-qa/` backlog row was deleted in the working tree without being moved to Done. Restore it? | Keep it deleted — absorbed by the real e2e suite built on the `bis` branch. Its folder (Playwright flows + seed fixtures) stays on disk. | — |
| 3 | 2026-07-31 | REFINE | How should existing chapters move to MultiEdit — data migration, or opt-in? | The News model: add a nullable `content_blocks` json column, keep `content` as the rendered HTML the reading page prints, NULL = Simple mode. **No data migration.** A chapter gains blocks only when its author converts it, so no existing chapter changes its rendered HTML and no existing quote can stale on day one. | — |
| 4 | 2026-07-31 | REFINE | Converting a chapter to Advanced (or splitting/reordering blocks) can silently detach readers' quotes, because `render()` wraps each text block in a `div` and `canonical-text.js` inserts a space at every block boundary. Warn, notify, block, or accept? | Accept silently. No warning to the author, no notification to readers. Rationale given: authors already break quotes today simply by editing their prose, so this is the same class of event rather than a new one. | — |
| 5 | 2026-07-31 | REFINE | Does `author_note` move to MultiEdit as well? | No — it stays simple rich-text. Out of scope. | — |
| 6 | 2026-07-31 | REFINE | Which toolbar preset for chapter text blocks? | `links` — the preset chapters use today. Keeping it means the writing experience is unchanged; switching to `narrative` would be a separate decision wearing this task's clothes. | — |
| 7 | 2026-07-31 | REFINE | Do image captions count toward a chapter's word/character count? | No. Counts are computed from **text blocks only**. User vetoed the proposed assumption that captions would count, since those counts feed the user-visible `UserTotalWordsStatistic`. Side benefit: counting text blocks directly also avoids the `</div><div>` word-collapse hazard of counting from rendered HTML. | — |
| 8 | 2026-07-31 | REFINE | Should the rendered content be stored at all, or recomputed from `content_blocks` on read? User raised the storage cost (~2× on a longtext field) and the desync risk, and left the call to the orchestrator. | **Store it.** Three reasons: (a) Simple-mode chapters have NULL blocks, so the column is required regardless; (b) chapter read is the hottest page and re-rendering costs an HTMLPurifier pass plus a Blade render per image on every view; (c) decisive — rendering at read makes output a moving target, so any future sanitiser or block-markup change would retroactively alter every chapter's text and stale every quote at once. Desync is contained by a single-writer invariant: `ChapterService` recomputes `content` from blocks on save and nothing else ever writes it. | — |
| 9 | 2026-07-31 | DESIGN | Chapter text blocks need a different sanitizer than News: `multiedit-text` strips `p.class`/`span.class` (killing `ql-align-*`, `ql-spoiler` and `ql-custom-emoji-*`, all produced by the `links` toolbar) and permits external links, which chapters strip today. Widen `multiedit-text`, accept the loss, or make Editor profile-aware? | **Make Editor profile-aware.** `EditorPublicApi::render()` and `sanitizeText()` take an optional `$profile`, defaulting to `multiedit-text` so News is untouched by construction. A new `multiedit-narrative` Purifier profile carries `strict-with-links`' element and class set minus `<img>`. External-link stripping stays Story-side — it is a content policy, not a sanitizer capability. Rejected: widening `multiedit-text` (silently widens News and static pages, and still leaves external links to Story); accepting the loss (conversion would drop alignment, spoilers and emoji — a regression decision 4 never sanctioned, since it covered quote anchoring, not formatting). | — |
| 10 | 2026-07-31 | DESIGN | §4.6 requires counts from text blocks only *and* byte-identical counts on conversion. `plainTextLength()` collapses whitespace and trims, so it would shift `character_count` and cannot be used. Add an Editor method, or read the block array from Story? | **Add `EditorPublicApi::plainText(array $blocks): string`** — the concatenated HTML of text blocks only. Story applies its existing `WordCounter`/`CharacterCounter` unchanged, so conversion is count-stable by construction. Editor keeps "what is a text block", Story keeps "how we count", for the cost of one method. Rejected: Story looping the block array itself (cheaper, but duplicates the notion of a text block into a second domain). | — |

## Assumptions accepted by the user at the REFINE replay

Presented explicitly and not vetoed (assumption 1, on captions, *was* vetoed and
became decision 7).

| # | Assumption | Status |
|---|------------|--------|
| A2 | No block count limit (`min`/`max` unset) and no per-chapter image cap | accepted |
| A3 | Alt text stays required on image blocks, mirroring News | accepted |
| A4 | Simple mode remains the default — a new chapter opens as rich-text, not blocks | accepted |
| A5 | The moderation snapshot is left as-is, including its pre-existing quirk of showing escaped HTML tags in the 300-char collapsed preview | accepted |
| A6 | No new lang strings in Story; the toggle and block controls reuse `editor::multi.*` | accepted |

## Assumptions made without asking

Used in `auto` mode, or when the user was unavailable. Each one is a decision
the user may want to reverse — surface these in the WRAP summary.

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| A7 | `ChapterSnapshot::fromModel()` stops recomputing counts from `content` and reads the persisted `word_count`/`character_count` columns instead. §4.6 leaves no alternative — recomputing from rendered Advanced content would count image captions and shift four user-visible statistics. Side effect: the emitted `charCount` now decodes HTML entities (the column always did), so it shifts by a few characters on entity-heavy chapters. | DESIGN | Yes, but reversing re-breaks §4.6.1 |
| A8 | Block resolution lives in a new `ChapterContentResolver` support class rather than inline in `ChapterService` (as News does it). `ChapterService` is already ~500 lines of event/credit/notification orchestration, and the resolver is the only Story class needing the Editor and Media deptrac edges. | DESIGN | Yes — pure layout |
| A9 | Mode is derived from `content_blocks IS NULL`; no `mode` column is added. A second source of truth could disagree with the first. | DESIGN | Expensive — would need a migration and a backfill |
| A10 | `<x-editor::multi>` gains `nbLines` and `indentParagraphs` props, threaded to every text block. Without them Advanced mode is a visibly worse writing surface than Simple mode in the same form (chapters pass `nbLines=15`, `indentParagraphs=true` today; text blocks hardcode 5 and no indent). | DESIGN | Yes |
| A11 | `.rich-content p:last-of-type { padding-bottom: 0 }` is re-scoped in `Shared/Resources/css/app.css` so it applies to the last paragraph of the last block, not of every block. Left alone, spacing collapses at every block boundary — a §4.5.2 violation. | DESIGN | Yes |
| A12 | The chapter form rebuilds its blocks from old input on a failed validation re-render, exactly as News's `_form.blade.php` does. The plan's snippet passed `old('blocks', …)` straight through, but that array is keyed by uid, so the component would derive uids from the keys and lose the submitted order — and an author who trips the alt-text rule would get the *stored* blocks back, silently discarding the edit. | BUILD (phase 6) | Yes |
| A13 | `<x-editor::multi>` keeps its generated simple-pane id; the e2e `ChapterEditPage` reaches that pane through the component root instead. Adding an `id` prop to a shared Editor component only so a test selector keeps working would be test pressure on production markup; `RichTextEditor` now accepts a locator as well as an id. | BUILD (phase 6) | Yes |

## Spec open questions closed at DESIGN

| `01-functional.md` §9 | Answer |
|---|---|
| 1. Are chapters soft-deleted? | **Yes** — `Chapter` uses `SoftDeletes` (migration `2025_10_17_161100`). The usage provider must therefore query `withTrashed()`, or restoring a soft-deleted chapter yields dead images. |
| 2. How are words counted from text blocks? | Decision 10 — a new `EditorPublicApi::plainText()`, with Story's existing counters applied to it. |
| 3. Does `[text-indent:2rem]` still reach paragraphs nested in `.ce-block--text`? | **Yes, analytically**: `text-indent` is inherited, and every `.rich-content` selector is a descendant selector. But `p:last-of-type` is *parent*-scoped and does break — see assumption A11. Stays a VERIFY item. |

## Notes for REFINE / DESIGN

- `00-request.md` was written on 2026-07-28 and still names
  `<x-shared::multi-editor>` and `Shared\Support\ContentBlocksRenderer`. The
  `editor-domain/` task has since moved those into the Editor domain
  (`<x-editor::multi>`, `EditorPublicApi`). Read the current code, not the
  request's component names.
