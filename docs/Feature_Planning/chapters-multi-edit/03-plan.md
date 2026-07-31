# Chapters — MultiEdit content — implementation plan

> PLAN output. The phase index at the top is the summary; everything below is
> detail. BUILD reads **one phase at a time** and nothing else of this file, so
> every phase must stand alone: name the `02-architecture.md` sections it needs,
> and state what earlier phases left behind rather than assuming it was read.

- Functional spec: [`01-functional.md`](./01-functional.md)
- Architecture: [`02-architecture.md`](./02-architecture.md)
- Decisions: [`DECISIONS.md`](./DECISIONS.md)

## Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | Editor — profile-aware sanitizing + `multiedit-narrative` + `plainText()` | M | — | DONE |
| 2 | Editor — `<x-editor::multi>` writing-surface props (`nbLines`, `indentParagraphs`) | S | — | DONE |
| 3 | Story — `content_blocks` column, model cast, snapshot reads persisted counts | S | — | DONE |
| 4 | Story — content resolver, request branching, counts from text blocks, moderation | M | 1, 3 | DONE |
| 5 | Story — media usage provider (the data-loss surface) | M | 3, 4 | DONE |
| 6 | Story — chapter form swaps to MultiEdit + read-side CSS re-scoping | S | 2, 4, 5 | DONE |

Sizes: S ≈ half a day, M ≈ 1–2 days, L → split it.
Status per phase: `TODO` · `WIP` · `DONE`. BUILD updates this table as it goes;
it is what lets `WIP:BUILD (3/7)` resume correctly.

Why this order: the two Editor phases are shared infrastructure and ship first
with tests proving News is untouched (slicing rule 4). The provider (phase 5)
registers `chapters/*` as a *claimed* Media folder, which is the destructive
step — it lands with its exhaustiveness test and a real `media:gc` run in the
same phase, and *before* the UI that lets authors create image blocks (phase 6).
Storing image paths without a registered provider merely leaks files;
registering an under-reporting provider destroys them (architecture §3.4).

## Working agreement

- One phase = one commit (or one PR). Each phase ships independently, keeps
  `npm run gate` green, and is revertable on its own.
- Failing test first, then the implementation.
- We do not move to phase N+1 until phase N's acceptance criteria are met.
- Re-ordering phases mid-build is a decision to surface, not to take silently.
- No phase may "adjust" `ChapterConversionCountsTest` to new numbers — a red run
  there means the feature is broken, not the test (architecture §9 risk 3).

---

## Phase 1 — Editor: profile-aware sanitizing, `multiedit-narrative`, `plainText()`

**Goal.** Let a second consumer pass its own Purifier profile to MultiEdit
rendering, and expose the raw text-block string a consumer needs to count, with
News's behaviour unchanged by construction.

**Reads.** Architecture §1.1 ("Editor — becomes profile-aware", "Editor —
`plainText()` for counting"), §7 tradeoffs 1 and 2. Decisions 9 and 10.

**Context.** Today `ContentBlocksRenderer::render()` and `sanitizeText()`
hardcode the `multiedit-text` Purifier profile, and `EditorPublicApi` exposes
`render(array $blocks)`, `sanitizeText(string $html)` and
`plainTextLength(array $blocks)`. News is the only consumer. Nothing in Story
changes in this phase.

**Deliverables.**
- `config/purifier.php` — new `multiedit-narrative` profile. It carries the
  element set of `strict-with-links` **minus `<img>`** (image blocks are the
  only image source, which is what keeps the used-path set enumerable), plus
  `p.class` / `span.class` and the same `Attr.AllowedClasses` whitelist
  `strict-with-links` uses (`ql-align-*`, `ql-spoiler`, `ql-custom-emoji-*`).
  Copy the class list from `strict-with-links`; do not invent a subset.
- `app/Domains/Editor/Private/Support/ContentBlocksRenderer.php` — `render()`
  and `sanitizeText()` take `string $profile = 'multiedit-text'`; `render()`
  forwards its profile to the per-block `sanitizeText()` call. New
  `plainText(array $blocks): string` returning the **concatenated `html` of
  text blocks only, in order, unmodified** — no `strip_tags`, no whitespace
  collapsing, no trimming (that is exactly what disqualifies `plainTextLength()`
  for counting; see architecture §1.1 and decision 10).
- `app/Domains/Editor/Public/Api/EditorPublicApi.php` — signatures become:
  ```php
  public function render(array $blocks, string $profile = 'multiedit-text'): string;
  public function sanitizeText(string $html, string $profile = 'multiedit-text'): string;
  public function plainTextLength(array $blocks): int;   // unchanged
  public function plainText(array $blocks): string;      // new
  ```
- `app/Domains/Editor/README.md` (and `AGENTS.md` if it documents the API
  surface) — document the profile argument and `plainText()`, and state the
  naming rule: profiles are named after the **capability**, never after the
  consumer.

**Tests.** Extend `app/Domains/Editor/Tests/Feature/EditorPublicApiTest.php` and
`ContentBlocksRendererTest.php`:
- `test_render_defaults_to_multiedit_text_profile` — no-argument `render()`
  produces byte-identical output to the pre-change behaviour for a block
  document containing `ql-align-center` and a `span.class` (both stripped).
- `test_render_honours_a_passed_profile` — the same document rendered with
  `multiedit-narrative` keeps the `p.class` / `span.class`.
- `test_narrative_profile_preserves_alignment_spoiler_and_emoji_classes` —
  `ql-align-right`, `ql-spoiler`, `ql-custom-emoji-*` survive `sanitizeText(…,
  'multiedit-narrative')`.
- `test_narrative_profile_strips_img_tags` — an `<img>` inside a text block is
  removed by `multiedit-narrative`.
- `test_narrative_profile_still_permits_internal_anchor_markup` — `<a href>`
  survives sanitizing (external-link stripping is Story's job, not the
  profile's).
- `test_plain_text_returns_text_blocks_only_in_order` — image blocks contribute
  nothing; two text blocks concatenate in document order.
- `test_plain_text_does_not_collapse_or_trim_whitespace` — the returned string
  is byte-identical to the input `html` for a single text block, including
  leading/trailing whitespace. This is the property §4.6.2 rests on.

**Acceptance.**
- ✅ `EditorPublicApi::render($blocks)` and `sanitizeText($html)` called with no
  profile produce the same output as before this phase.
- ✅ No News file is modified in this phase; the existing News advanced-mode
  tests pass untouched.
- ✅ `multiedit-narrative` preserves `ql-align-*`, `ql-spoiler` and
  `ql-custom-emoji-*`, and strips `<img>`.
- ✅ `plainText([['type'=>'text','html'=>$h]]) === $h`.
- ✅ `npm run gate` green.

---

## Phase 2 — Editor: `<x-editor::multi>` writing-surface props

**Goal.** Give MultiEdit text blocks the same editing surface
`<x-editor::rich-text>` already offers, so Advanced mode is not visibly worse
than Simple mode inside the same form.

**Reads.** Architecture §1.1 ("`<x-editor::multi>` prop parity"), assumption A10
in `DECISIONS.md`.

**Context.** `_text-block.blade.php` hardcodes `data-nb-lines="5"` and passes no
indent flag; `<x-editor::multi>` accepts neither prop. `<x-editor::rich-text>`
already supports both and is the reference for the attribute names emitted onto
the Quill mount node — read it and mirror it exactly rather than inventing new
attribute names. Chapters will pass `:nbLines="15" :indentParagraphs="true"` in
phase 6; no Story file changes here.

**Deliverables.**
- `app/Domains/Editor/Private/Resources/views/components/multi.blade.php` —
  accepts `nbLines` (default: today's `5`) and `indentParagraphs` (default:
  today's behaviour, i.e. off), and passes both to every text block, including
  blocks created dynamically by the Alpine template (the "add text block"
  affordance must produce the same attributes as a server-rendered block).
- `app/Domains/Editor/Private/Resources/views/components/multi/_text-block.blade.php`
  — emits the two values instead of the hardcoded `data-nb-lines="5"`.
- Editor README/AGENTS prop table updated.

**Tests.** In `app/Domains/Editor/Tests/Feature/MultiEditorComponentTest.php`:
- `test_text_blocks_default_to_five_lines_and_no_indent` — a component rendered
  with no new props emits exactly today's attributes (the News regression
  guard).
- `test_nb_lines_prop_is_applied_to_every_text_block` — a two-text-block
  document rendered with `:nbLines="15"` emits `data-nb-lines="15"` **twice**.
- `test_indent_paragraphs_prop_is_applied_to_every_text_block` — same, for the
  indent attribute name used by `<x-editor::rich-text>`.
- If the dynamic "add block" template is a JS-side clone, add a Vitest case
  asserting a newly inserted text block carries the same two attributes;
  otherwise a Blade assertion on the Alpine `<template>` is enough — say which
  in the commit message.

**Acceptance.**
- ✅ A `<x-editor::multi>` with no new props renders byte-identical HTML to
  before this phase; News admin pages are unaffected.
- ✅ Every text block — server-rendered and dynamically added — carries the
  passed `nbLines` and indent flag.
- ✅ `npm run gate` green.

---

## Phase 3 — Story: `content_blocks` column, model cast, snapshot counts

**Goal.** Add the storage that makes Advanced mode possible, and make
`ChapterSnapshot` read the persisted counts instead of recomputing them — both
inert on their own, and both prerequisites for the resolver.

**Reads.** Architecture §2.1, §2.2, §3.1, §7 tradeoff 3, §9 risk 3. Assumptions
A7 and A9 in `DECISIONS.md`.

**Context.** `story_chapters` has `content` (rendered HTML), `word_count` and
`character_count`; the counts are maintained by `ChapterObserver::saving()`
from `$chapter->content`. `ChapterSnapshot::fromModel()` currently recomputes
`wordCount` with `WordCounter::count($chapter->content)` and `charCount` with
`mb_strlen(strip_tags($chapter->content))` — two counting rules for one value.
Nothing in this phase makes any chapter Advanced; `content_blocks` stays NULL
everywhere until phase 4.

**Deliverables.**
- `app/Domains/Story/Database/Migrations/YYYY_MM_DD_HHiiss_add_content_blocks_to_story_chapters_table.php`
  — nullable `json content_blocks`, no index, `down()` drops the column. NULL is
  Simple mode and is the meaning of every existing row: **no data backfill, now
  or ever, without a new decision** (decision 3).
- `app/Domains/Story/Private/Models/Chapter.php` — `content_blocks` added to
  `#[Fillable]`, `'content_blocks' => 'array'` added to `$casts`. No accessor,
  no scope, no `mode` column (assumption A9).
- `app/Domains/Story/Public/Events/DTO/ChapterSnapshot.php` —
  `fromModel()` reads `(int) $chapter->word_count` and
  `(int) $chapter->character_count`. Drop the `WordCounter` import if it becomes
  unused. `toPayload()`/`fromPayload()` and the constructor are **unchanged** —
  the DTO shape must not move (architecture §3.1).

**Tests.** New `app/Domains/Story/Tests/Feature/Chapters/ChapterSnapshotCountsTest.php`:
- `test_snapshot_reports_the_persisted_word_and_character_counts` — create a
  chapter through the normal flow, assert `ChapterSnapshot::fromModel()` returns
  exactly the values in the `word_count` / `character_count` columns.
- `test_snapshot_char_count_matches_the_column_for_entity_heavy_content` — a
  chapter containing `&amp;`/`&nbsp;` entities: the snapshot's `charCount` equals
  the column (this is the accepted side effect of A7 — the column is the value
  already shown to users, so aligning is a correction).
- Existing tests that assert snapshot counts (statistics listeners, event
  payloads) must pass unchanged; if one breaks it is because it encoded the old
  `strip_tags` rule — fix the expectation and say so in the commit message.
- A migration round-trip assertion is not needed beyond the gate's own
  migrate/rollback coverage; if the suite has no rollback step, add
  `test_migration_down_drops_the_column` rather than assuming.

**Acceptance.**
- ✅ `php artisan migrate` then `migrate:rollback` is clean.
- ✅ An existing chapter's rendered HTML, `word_count` and `character_count` are
  untouched by this phase.
- ✅ `ChapterSnapshot::fromModel()` no longer calls `WordCounter` or
  `strip_tags`.
- ✅ The four consuming statistics (`TotalWords`, `TotalChapters` and their
  per-user forms) still pass their existing tests.
- ✅ `npm run gate` green.

---

## Phase 4 — Story: content resolver, request branching, counts, moderation

**Goal.** Make the server accept, sanitize, store and count Advanced chapter
content, with conversion proven count-stable — all before any UI exposes it.

**Reads.** Architecture §3.2, §3.3, §3.5, §2.3, §5 (deptrac), §6, §9 risk 3.
Functional §4.2–§4.4, §4.6, §5. Decisions 6, 7, 8, 10.

**Context left by earlier phases.** `story_chapters.content_blocks` exists,
nullable, cast to `array` on `Chapter` (phase 3). `EditorPublicApi` takes an
optional Purifier profile on `render()`/`sanitizeText()` and exposes
`plainText(array $blocks): string`, the raw concatenated HTML of text blocks
only; the `multiedit-narrative` profile exists (phase 1). No chapter form change
yet — this phase is exercised through the existing POST/PUT chapter routes with
`mode=advanced` in the payload.

**Deliverables.**
- `app/Domains/Story/Private/Support/ChapterContentResolver.php` — new:
  ```php
  /** @return array{content: string, content_blocks: ?array<int,array<string,mixed>>} */
  public function resolve(array $data, int $actingUserId): array;
  ```
  - Simple (`mode !== 'advanced'`): `content` = the already-purified author HTML
    from the request, `content_blocks` = `null`. Byte-identical to today.
  - Advanced: walk the submitted order and per block —
    **text**: `EditorPublicApi::sanitizeText($html, 'multiedit-narrative')`, then
    `HtmlLinkUtils::stripExternalLinks()`, then drop the block if empty;
    **image**: store a new upload via `MediaPublicApi` under scope
    `chapters/{$actingUserId}` or keep the reused path, drop if no path.
    Then `content = EditorPublicApi::render($blocks, 'multiedit-narrative')` and
    `content_blocks` = the normalized list.
  - The scope is built from `$actingUserId` — **never** from the request
    (architecture §3.3). Mirror `NewsService::resolveContent()` for the upload /
    reuse / `keep_original` handling rather than inventing a second shape.
- `app/Domains/Story/Private/Http/Requests/ChapterRequest.php` — branch on
  `mode` (`nullable`, `in:simple,advanced`):
  - Simple: `content` stays `required`; `prepareForValidation()` keeps purifying
    with `strict-with-links` + `stripExternalLinks`, exactly as today.
  - Advanced: `content` becomes `nullable` (derived output, not input);
    `blocks` `required|array|min:1`, with per-block rules for `type`, `html`,
    `path`, `alt` (**required for image blocks** — functional §4.4.3),
    `caption`, `keep_original`, `file`, mirroring `NewsRequest`.
  - `prepareForValidation()` must **not** purify block HTML — that happens once,
    in the resolver, with the narrative profile. Two purifier passes with two
    profiles is how the policies silently diverge (architecture §3.5).
  - New messages go in `story::validation` (French). No new toggle/block strings
    — the component reuses `editor::multi.*` (assumption A6).
- `app/Domains/Story/Private/Services/ChapterService.php` —
  `createChapter()`/`updateChapter()` call the resolver and assign both fields
  instead of reading `content` off the request; `emptyContentBySlug()` sets
  `content = ''` **and** `content_blocks = null` (architecture §2.3 — clearing
  only one lets the next ordinary save resurrect the moderated text).
- `app/Domains/Story/Private/Observers/ChapterObserver.php` — the counts stay
  here, the single writer. Source the counted string from
  `EditorPublicApi::plainText($chapter->content_blocks)` when blocks are
  present, from `$chapter->content` when they are not; then apply today's
  `WordCounter`/`CharacterCounter` unchanged. Recompute when `content` **or**
  `content_blocks` is dirty (the existing null-guard stays).
- `deptrac.yaml` — add `StoryPrivate → EditorPublic` and
  `StoryPrivate → MediaPublic` (both mirror edges News already has). See
  `.agents/skills/fix-deptrac` if the run is red.

**Tests.** All Laravel feature tests hitting the real chapter routes as a
`user-confirmed` author of the story.
- New `app/Domains/Story/Tests/Feature/Chapters/ChapterConversionCountsTest.php`
  — the §4.6.2 acceptance criterion:
  - `test_converting_a_chapter_without_changing_a_word_keeps_both_counts` —
    save Simple, capture `word_count`/`character_count`, re-save as
    `mode=advanced` with one text block whose `html` is the **byte-identical**
    stored `content`, assert equality **against the captured values**, never
    against constants.
  - `test_image_alt_and_caption_do_not_count` — adding an image block with a
    long alt and caption leaves both counts unchanged.
  - `test_returning_to_simple_keeps_the_counts` — one text block, no images,
    saved as `mode=simple`: `content_blocks` is NULL, counts unchanged.
- New `app/Domains/Story/Tests/Feature/Chapters/ChapterAdvancedModeTest.php`:
  - `test_advanced_save_stores_blocks_and_rendered_content` — `content_blocks`
    holds the normalized list and `content` is the rendered HTML.
  - `test_external_link_in_a_text_block_is_stripped` /
    `test_internal_link_survives`.
  - `test_alignment_spoiler_and_emoji_classes_survive_conversion` — the classes
    a Simple chapter carries today are still present after conversion (this is
    what `multiedit-narrative` exists for).
  - `test_image_block_without_alt_is_rejected` — 422 / validation error.
  - `test_advanced_with_zero_blocks_is_rejected`.
  - `test_simple_mode_still_requires_content`.
  - `test_upload_scope_is_the_acting_user` — the stored path sits under
    `chapters/{acting user id}` even when the payload names another folder.
  - `test_non_confirmed_user_still_cannot_reach_the_chapter_edit_route` — the
    existing gate is unchanged, and this phase does not widen it.
- Extend `app/Domains/Story/Tests/Feature/ChapterModerationEmptyContentTest.php`:
  - `test_emptying_an_advanced_chapter_clears_its_blocks` and
  - `test_a_subsequent_save_does_not_resurrect_the_moderated_text`.
- Existing `CreateChapterTest` / `EditChapterTest` must pass **unmodified** —
  they are the Simple-mode regression guard.

**Acceptance.**
- ✅ A Simple-mode save produces byte-identical `content` to before this phase,
  and `content_blocks` NULL.
- ✅ Converting a chapter without changing a word changes neither `word_count`
  nor `character_count`.
- ✅ An image block with a blank `alt` is rejected server-side (not merely
  prompted).
- ✅ Image paths are scoped to the authenticated user's id, never to a
  request-supplied folder.
- ✅ `emptyContentBySlug()` leaves `content = ''` and `content_blocks = NULL`.
- ✅ `./vendor/bin/sail composer deptrac` green with the two new edges.
- ✅ `npm run gate` green.

---

## Phase 5 — Story: media usage provider (the data-loss surface)

**Goal.** Report every image path any chapter references, exhaustively, and
prove with a real `media:gc` run that a live chapter image survives while a true
orphan in the same folder is swept.

**Reads.** Architecture §3.4, §5 (deptrac), §9 risk 1. Functional §4.7, §5.
`DECISIONS.md` open-question 1 (chapters **are** soft-deleted).

**Context left by earlier phases.** Chapters can already be saved in Advanced
mode through the API, storing image paths under `chapters/{userId}` inside
`content_blocks` (phase 4). No provider is registered yet, so `chapters/*` is an
**unclaimed** folder and the GC never sweeps it — files leak but nothing is
deleted. This phase flips that, which is why the exhaustiveness test and the
`media:gc` test ship in this phase and never in a later one.

**Why exhaustiveness is a correctness requirement, not a nicety.** Registering
the provider is what makes `chapters/{userId}` a *claimed* folder. The GC's
folder-level guard only protects a folder with **zero** claimed paths — so once
one chapter image is claimed, every unclaimed original in that same author's
folder becomes deletable after the grace window. Under-reporting destroys user
data; over-reporting only leaks.

**Deliverables.**
- `app/Domains/Story/Private/Support/ChapterMediaUsageProvider.php` —
  implements `App\Domains\Media\Public\Contracts\MediaUsageProvider`,
  `usedPaths(): iterable`, yielding every `path` of every `image` block of every
  chapter with `whereNotNull('content_blocks')` — **`->withTrashed()`**, because
  `Chapter` uses `SoftDeletes` and restoring a soft-deleted chapter whose paths
  went unreported would restore a chapter full of dead images. Follow
  `app/Domains/News/Private/Support/NewsMediaUsageProvider.php` for shape;
  chapters have no header-image equivalent, so blocks are the only source.
  Chunk or lazy-iterate rather than `get()`-ing every chapter's JSON at once.
- `app/Domains/Story/Public/Providers/StoryServiceProvider.php` — in `boot()`,
  beside the existing observer/registry wiring:
  `app(MediaUsageRegistry::class)->register(new ChapterMediaUsageProvider());`
- `deptrac.yaml` — add `StoryPublic → MediaPublic` (mirrors
  `NewsPublic → MediaPublic`).
- `app/Domains/Story/README.md` (or `AGENTS.md`) — one line recording the
  standing rule from risk 1: **any new place that stores an image path outside
  `content_blocks` must be added to `usedPaths()` in the same commit.** Do not
  reference `docs/Feature_Planning` from a domain doc.

**Tests.** New
`app/Domains/Story/Tests/Feature/Chapters/ChapterMediaUsageProviderTest.php`:
- `test_reports_paths_from_published_draft_and_scheduled_chapters`.
- `test_reports_paths_from_soft_deleted_chapters` — the `withTrashed()` guard;
  this test failing means a restore yields dead images.
- `test_reports_a_repeated_image_for_each_occurrence`.
- `test_ignores_text_blocks_and_blocks_without_a_path`.
- `test_ignores_simple_mode_chapters_without_blocks` (no crash on NULL).
- `test_media_gc_keeps_a_live_chapter_image_and_sweeps an orphan in the same
  folder` — the decisive one: seed a chapter referencing image A under
  `chapters/{userId}`, drop an unreferenced original B in that same folder, age
  both past the grace window, run `media:gc` for real, assert A still exists and
  B is gone. Name it
  `test_media_gc_keeps_live_chapter_images_and_sweeps_orphans`.
  Follow `app/Domains/Media/Tests/Feature/MediaServiceTest.php` and
  `News`'s equivalent for how the grace window is faked.
- `test_gc_leaves_a_soft_deleted_chapters_image_alone` — belt and braces on the
  restore path.

**Acceptance.**
- ✅ `usedPaths()` yields paths from published, draft, scheduled **and
  soft-deleted** chapters.
- ✅ A real `media:gc` run keeps a referenced chapter image and deletes an
  unreferenced original in the same folder.
- ✅ The provider is registered exactly once, in `StoryServiceProvider::boot()`.
- ✅ `./vendor/bin/sail composer deptrac` green with `StoryPublic → MediaPublic`.
- ✅ `npm run gate` green.

---

## Phase 6 — Story: chapter form swaps to MultiEdit, read-side CSS re-scoping

**Goal.** Give authors the mode toggle and block controls in the chapter form,
and keep the reading page's typography identical for converted and unconverted
chapters.

**Reads.** Architecture §4, §1.1 ("Shared — one read-side CSS rule"), §9 risk 5.
Functional §4.1–§4.3, §4.5. Assumptions A4, A11.

**Context left by earlier phases.** The server already accepts, sanitizes,
stores, counts and GC-tracks Advanced content (phases 4–5), and
`<x-editor::multi>` accepts `nbLines` / `indentParagraphs` (phase 2). This phase
is presentation only: no service, request or resolver change.

**Deliverables.**
- `app/Domains/Story/Private/Resources/views/chapters/partials/form.blade.php` —
  the content field swaps `<x-editor::rich-text>` for:
  ```blade
  <x-editor::multi
      scope="chapters/{{ auth()->id() }}"
      name="blocks" contentName="content"
      :contentValue="old('content', $chapter->content ?? '')"
      :blocks="old('blocks', $chapter->content_blocks ?? [])"
      toolbar="links" :nbLines="15" :indentParagraphs="true" />
  ```
  `blocks` non-empty is what reopens the form in Advanced mode — mode is
  restored from stored data, with no extra field (assumption A9). A new chapter
  has no blocks and therefore opens Simple (assumption A4).
  **`author_note` is untouched** — it keeps `<x-editor::rich-text>` with the
  `narrative` toolbar (decision 5).
- `app/Domains/Shared/Resources/css/app.css` — re-scope
  `.rich-content p:last-of-type { padding-bottom: 0 }` so it applies to the last
  paragraph of the **last block**, not of every block. Today every `<p>` shares
  the `<article>` as parent so exactly one paragraph loses its bottom padding;
  under Advanced mode each `.ce-block--text` div is a parent, so spacing
  collapses at every block boundary (a §4.5.2 violation). A rule of the shape
  ```css
  .rich-content > p:last-of-type,
  .rich-content > .ce-block:last-child p:last-of-type { padding-bottom: 0; }
  ```
  satisfies both layouts — verify it against the real rendered markup rather
  than copying it blind. It belongs beside the existing read-side rule in
  `Shared` (a reading page never loads the editor CSS), not in
  `Editor/…/editor.css`, whose `.ql-editor p:last-of-type` rule is a separate,
  edit-side concern and stays as it is.
- **The reading page is not touched.** `<article data-quote-article class="prose
  rich-content max-w-none [text-indent:2rem] text-xl">` stays exactly as it is:
  one quote root for the whole chapter (§4.5.1). Per-block `[data-annotable]`
  regions remain out of scope.

**Tests.**
- Extend `ChapterAdvancedModeTest` (created in phase 4) with the round trip:
  - `test_edit_form_reopens_an_advanced_chapter_in_advanced_mode` — the form
    response contains the stored blocks and the MultiEdit mount, not a bare
    rich-text field.
  - `test_edit_form_opens_a_simple_chapter_in_simple_mode`.
  - `test_new_chapter_form_opens_in_simple_mode`.
  - `test_chapter_text_blocks_get_fifteen_lines_and_indentation` — the props of
    phase 2 actually reach the form.
- New `test_reading_page_prints_advanced_content_in_a_single_quote_root` — the
  published chapter page contains exactly **one** `[data-quote-article]`
  element, containing every block.
- `test_author_note_is_still_a_simple_rich_text_field`.
- If a Vitest suite covers the read-side CSS bundle, no JS test is warranted
  here — the CSS regression is a VERIFY item (risk 5), not a unit-testable one.

**Acceptance.**
- ✅ An author sees the Simple/Avancé toggle on the chapter edit form and can
  add, reorder and delete blocks.
- ✅ A converted chapter reopens in Advanced mode; an unconverted one in Simple.
- ✅ The reading page has exactly one `[data-quote-article]` root.
- ✅ A `<p>` at the end of a non-final text block keeps its `padding-bottom`.
- ✅ `npm run gate` green.
- ✅ `npm run e2e` green (this feature has browser-only behaviour).

---

## Visual QA checklist

Filled by VERIFY. Run the app per `.agents/skills/run-app`; screenshots go to
`shots/`.

| Surface | Check | OK? |
|---------|-------|-----|
| Chapter edit form, unconverted chapter (author) | Opens in Simple mode with the `links` toolbar, 15 visible lines, indented paragraphs — indistinguishable from before the feature | |
| Chapter edit form, new chapter (author) | Opens in Simple mode, empty, toggle available | |
| Chapter edit form — conversion | Clicking **Avancé** turns the existing HTML into one text block with no visible content change | |
| Chapter edit form — block controls | Add / reorder / delete text and image blocks under Alpine; each new text block has the same height and indentation as the first | |
| Chapter edit form — return to Simple | **Simple** is disabled with its French tooltip when there is more than one block or any image; enabled and working with exactly one text block | |
| Image block — upload | Upload lands in `chapters/{my user id}`; preview renders; caption optional | |
| Image block — reuse picker | Shows only my own chapter images; the same image can be inserted twice | |
| Image block — missing alt | Saving with a blank alt shows the French validation error and does not save | |
| **Typography, side by side** (risk 5, open question 3) | An unconverted chapter and a converted one with 3+ text blocks, screenshotted together: paragraph indent (2rem), inter-paragraph spacing, and spacing **across a block boundary** are identical | |
| Reading page — converted chapter | Text and images interleave; images are responsive; captions render below | |
| Reading page — quotes | Selecting text on a converted chapter still opens the quote affordance; existing quotes on an *unconverted* chapter are still highlighted | |
| Reading page — mobile (375px) | Blocks, images and captions stack correctly; no horizontal scroll | |
| Chapter edit form — mobile (375px) | Toggle and block controls reachable and usable | |
| Word count display | The chapter's displayed word count is unchanged immediately after a no-op conversion | |
| Guest / non-confirmed reader | Sees the converted chapter exactly as before; no edit affordance anywhere | |
| Moderation panel | A converted chapter's snapshot still shows its rendered content; "empty content" leaves the chapter blank and it stays blank after the author saves again | |
| Co-authored story | A co-author converting a chapter uploads into *their own* folder; both authors' images render on the reading page | |
| Soft-deleted then restored chapter | After a delete + restore cycle (and a `media:gc` run), the chapter's images still display | |

## Open items

- **`<x-editor::rich-text>`'s indent attribute name** — phase 2 must mirror the
  exact attribute `rich-text.blade.php` emits for `indentParagraphs`; this plan
  did not read it. Resolve by reading that file at the start of phase 2, not by
  inventing a name.
- **How `<x-editor::multi>` adds a text block client-side** — whether new blocks
  come from a Blade `<template>` or a JS clone decides whether phase 2 needs a
  Vitest case. Resolve at the start of phase 2.
- **`MediaPublicApi`'s store/reuse signature** — phase 4's resolver mirrors
  `NewsService::resolveContent()`; the exact method names and the
  `keep_original` handling must be read from News at the start of phase 4
  rather than assumed from this plan's prose.
- **How the Media GC grace window is faked in tests** — phase 5's decisive
  `media:gc` test needs it. `app/Domains/Media/Tests/Feature/MediaServiceTest.php`
  and News's provider test are the references; confirm before writing phase 5.
- **Existing snapshot-count expectations** — phase 3 changes `charCount` for
  entity-heavy content. Which existing tests encode the old `strip_tags` value is
  not known; phase 3 finds out by running the suite and must fix expectations
  deliberately, noting each in the commit message.
- **`required_trimmed` / `maxstripped` custom rules under Advanced mode** —
  `ChapterRequest` uses both today. Phase 4 must confirm they are only applied to
  `title` / `author_note` and not accidentally to derived `content`.
- **Not verified, carried from the architecture:** whether any *other* Story
  surface (imports, admin tooling, seeders) writes `story_chapters.content`
  directly. Decision 8's single-writer invariant depends on the answer. Phase 4
  greps for direct `->content =` assignments on `Chapter` and reports what it
  finds; if a second writer exists, that is a decision to surface, not to patch
  quietly.
