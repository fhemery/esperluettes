# Multi-edit — chapter switch block — implementation plan

> PLAN output. The phase index at the top is the summary; everything below is
> detail. BUILD reads **one phase at a time** and nothing else of this file, so
> every phase must stand alone: name the `02-architecture.md` sections it needs,
> and state what earlier phases left behind rather than assuming it was read.

- Functional spec: [`01-functional.md`](./01-functional.md) — **read
  `02-architecture.md` §0 first**: decisions #9–#11 replaced the read-time
  visibility rules of the spec (no « non publié » marker on the reader page,
  dead choices 404, per-choice *enabled* toggle, fallback title frozen at save).
- Architecture: [`02-architecture.md`](./02-architecture.md)
- Decisions: [`DECISIONS.md`](./DECISIONS.md) — never re-ask a settled row.

## Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | Editor — block-type registry and render delegation (shared infrastructure) | S | — | DONE |
| 2 | Editor — `<x-editor::multi>` driven by the registry (`blockTypes`, `blockContext`, block chrome component) | M | 1 | DONE |
| 2v | Checkpoint — visual regression check of the refactored editor on existing consumers (`visual-verifier`, no code) | S | 2 | DONE |
| 3 | Story — `chapter-choice` server side: request, resolver, target check, rendering | M | 1 | DONE |
| 4 | Story — `chapter-choice` editor partial and chapter form wiring | M | 2, 3 | TODO |

Sizes: S ≈ half a day, M ≈ 1–2 days, L → split it.
Status per phase: `TODO` · `WIP` · `DONE`. BUILD updates this table as it goes;
it is what lets `WIP:BUILD (3/7)` resume correctly.

Phases 2 and 3 are independent of each other (both only need phase 1); keep the
numeric order anyway, one phase per commit.

## Working agreement

- One phase = one commit (or one PR). Each phase ships independently, keeps
  `pnpm run gate` green, and is revertable on its own.
- Failing test first, then the implementation.
- We do not move to phase N+1 until phase N's acceptance criteria are met.
- Re-ordering phases mid-build is a decision to surface, not to take silently.
- Plan-level choices that refine the architecture are listed in "Open items"
  at the bottom with the phase they belong to; they are resolved here unless
  marked otherwise.

---

## Phase 1 — Editor: block-type registry and render delegation

**Goal.** Give the Editor domain a public block-type registry with `text` and
`image` as built-in registrations, and make `ContentBlocksRenderer` render every
block through it, with a consumer-supplied render context — no visible change
for News, StaticPage or chapters.

Architecture: §1.1 (Editor bullets), §3.1, §8. Shared infrastructure: phase 2
(component) and phase 3 (Story's block type) both build on it.

**Deliverables.**
- `app/Domains/Editor/Public/Blocks/EditorBlockType.php` — interface, as in
  architecture §3.1 **plus one method** `icon(): string` (a Material Symbols
  name for the palette / insert-menu button: `notes` for text, `image` for
  image). Methods: `key()`, `labelKey()`, `icon()`, `editorView()`,
  `render(array $block, array $context): string`.
- `app/Domains/Editor/Public/Blocks/EditorBlockRegistry.php` — `final`;
  `register(EditorBlockType)` throws `\InvalidArgumentException` on a duplicate
  key; `get(string): ?EditorBlockType`; `all(): list<EditorBlockType>` in
  registration order.
- `app/Domains/Editor/Private/Blocks/TextBlockType.php` — key `text`, label
  `editor::multi.add_text`, view `editor::components.multi._text-block`.
  `render()` moves the current text branch of `ContentBlocksRenderer::render()`
  here verbatim: `Purifier::clean($html, $context['profile'])`, skip if empty,
  wrap in `<div class="ce-block ce-block--text">`.
- `app/Domains/Editor/Private/Blocks/ImageBlockType.php` — key `image`, label
  `editor::multi.add_image`, view `editor::components.multi._image-block`.
  `render()` moves the current image branch verbatim (`Blade::render` of
  `<x-media::image … class="ce-block ce-block--image">`, skip without `path`).
- `app/Domains/Editor/Public/Providers/EditorServiceProvider.php` — in
  `register()`: bind `EditorBlockRegistry` as a singleton whose factory
  registers `TextBlockType` then `ImageBlockType`. Doing it in the factory (not
  in `boot()`) guarantees built-ins come first in `all()` whatever the provider
  boot order, so the palette order stays text, image, then plugins.
- `app/Domains/Editor/Private/Support/ContentBlocksRenderer.php` — inject the
  registry; `render(array $blocks, string $profile = 'multiedit-text', array $context = [])`
  loops blocks, `get($block['type'])`, drops unknown/missing types, calls
  `$type->render($block, ['profile' => $profile] + $context)`. **`profile` is a
  reserved context key** set by the renderer (it wins over a consumer key of the
  same name). `sanitizeText()`, `plainText()`, `plainTextLength()` unchanged
  (text blocks only — A5 holds by construction).
- `app/Domains/Editor/Public/Api/EditorPublicApi.php` — `render()` gains
  `array $context = []`, forwarded.
- `app/Domains/Editor/README.md` — "Block schema" and "Public API": document the
  registry, the contract (incl. `icon()`), the reserved `profile` key, that
  plugin types own their own normalisation/validation (arch §7 #6), and that
  rendered HTML from a plugin is **not** re-sanitized by Editor (the plugin
  escapes its own output). Remove the "No block-type registry…" line (≈ line
  183). `app/Domains/Editor/AGENTS.md` — replace the "a redesign to make the day
  a third block type appears, not before" sentence with a pointer to the
  registry.

**Tests.**
- `app/Domains/Editor/Tests/Feature/EditorBlockRegistryTest.php`
  - `it lists text then image as built-in types`
  - `it rejects a second registration of the same key`
  - `it returns null for an unknown key`
- `app/Domains/Editor/Tests/Feature/ContentBlocksRendererTest.php` (extend; use a
  fresh registry instance bound in the test so the fake does not leak):
  - `it delegates a registered plugin type to its render() with the consumer context`
  - `it passes the profile to plugin types under the reserved profile key`
  - `it still drops a block whose type is not registered`
  - existing text/image cases stay unchanged and green (they are the
    no-regression proof for News/StaticPage/chapters).
- `app/Domains/Editor/Tests/Feature/EditorPublicApiTest.php` — `it forwards the render context to plugin types`.

**Acceptance.**
- ✅ `EditorBlockRegistry::all()` returns `[text, image]` on a fresh app.
- ✅ Registering a type with key `text` throws.
- ✅ `EditorPublicApi::render([...text, image...])` output is byte-identical to
  before (existing renderer/API tests unchanged and green).
- ✅ A fake registered type receives `['profile' => …] + $context` and its HTML
  lands in the output in block order; an unregistered type is silently dropped.
- ✅ News, StaticPage and Story suites green without touching their code.
- ✅ `pnpm run gate` green.

---

## Phase 2 — Editor: `<x-editor::multi>` driven by the registry

**Goal.** Make the multi-editor component offer, template and render any
registered block type a consumer opts into via `blockTypes`, pass a
`blockContext` to plugin partials, and generalise the "can go Simple" guard —
News and StaticPage unchanged.

Architecture: §1.1 (Editor bullets), §4.1. Builds on phase 1:
`App\Domains\Editor\Public\Blocks\EditorBlockRegistry` exists, holds `text` and
`image` (in that order) and any plugin a domain registers; each type exposes
`key()`, `labelKey()`, `icon()` (Material Symbols name) and `editorView()`.

**Deliverables.**
- `app/Domains/Editor/Private/Resources/views/components/multi.blade.php`
  - `@php`: `$enabledTypes` = registry `all()` filtered by `in_array($t->key(), $blockTypes, true)`,
    in registry order. A key in `blockTypes` that is not registered is ignored.
  - New prop `blockContext` (array, default `[]`), documented in the header
    comment; Editor never reads it.
  - **Palette**: `@foreach ($enabledTypes as $t)` one button. **Keep the exact
    attribute** `x-on:click="appendBlock('{{ $t->key() }}')"` — the e2e page
    object `e2e/pages/MultiEditor.ts` selects on it.
  - **Existing blocks** (`@foreach ($blocks …)`): `text` and `image` keep their
    current explicit `@include`s (their partials need component-level props:
    toolbar, min/max, scope, …). Any other type whose key is enabled and
    registered: `@include($type->editorView(), ['name' => $name, 'uid' => 'b'.$i, 'block' => $block, 'context' => $blockContext])`.
    A stored block whose type is not enabled/registered is skipped (today it
    would silently render as a text block — that fallback is removed for
    non-text types).
  - **Templates**: one `<template data-block-template="{{ key }}">` per enabled
    type (text/image with their current includes and `__UID__`; plugins with
    `block => null`). Replace `x-ref="tplText"` / `x-ref="tplImage"`.
  - Pass the enabled types to the insert affordance (see below).
  - JS: `_make(type, uid)` looks up
    `this.$root.querySelector('template[data-block-template="'+type+'"]')` (or a
    map captured in `init()`, like `containerEl`); unknown type → no-op.
    `syncState()`: replace `imageCount` with `nonTextCount`
    (`dataset.type !== 'text'`), `canGoSimple = blockCount === 1 && nonTextCount === 0`.
    `goAdvanced()` still seeds a `text` block.
- `app/Domains/Editor/Private/Resources/views/components/multi/_insert-affordance.blade.php`
  — loop over the enabled types (fixes the menu ignoring `blockTypes`). Keep
  the exact attribute `x-on:click="insertAfter($el, '{{ key }}'); open = false"`
  (used by `e2e/pages/MultiEditor.ts`). The enabled list reaches it through
  Blade include-scope inheritance from `multi.blade.php`; pass it explicitly to
  the new block component (below) so plugin partials need not know about it.
- **New** `app/Domains/Editor/Private/Resources/views/components/multi/block.blade.php`
  — anonymous component `<x-editor::multi.block :type :name :uid>` with a
  default slot. Renders the block chrome every block must have: root
  `div.ce-block.ce-block--{type}[data-block][data-type][data-uid]` with the
  card classes of `_image-block`, the up/down/delete buttons (same
  `x-on:click="moveUp($el)"` etc. and `labels.*` titles), the hidden
  `{name}[{uid}][type]` input, the slot, then the insert affordance. This is
  the **public** way for a plugin partial (another domain) to get the chrome
  without `@include`-ing Editor's private `_insert-affordance` partial. The
  existing `_text-block` / `_image-block` partials are **not** refactored onto
  it (touch only what you must).
- `app/Domains/Editor/Private/Resources/lang/fr/multi.php` — replace
  `to_simple_disabled` text with a type-neutral wording, e.g. « Ne gardez qu’un
  seul bloc de texte (sans image ni autre bloc) pour revenir en mode Simple. »
- `app/Domains/Editor/Tests/Fixtures/views/fake-block.blade.php` — test-only
  partial using `<x-editor::multi.block>` and echoing `$context['marker'] ?? ''`
  and `$block['value'] ?? ''`.
- `app/Domains/Editor/README.md` — `<x-editor::multi>` props table: `blockTypes`
  now drives palette, insert menu, templates and rendering; new `blockContext`;
  new "Plugin block types" subsection documenting the partial contract
  (`$name`, `$uid`, `$block` (null for a new one), `$context`) and
  `<x-editor::multi.block>`.

**Tests.**
- `app/Domains/Editor/Tests/Feature/MultiEditorComponentTest.php` (extend; the
  fake type is registered in a fresh registry bound per test, its view
  namespace added with `view()->addNamespace('editortest', …/Tests/Fixtures/views)`):
  - `it offers only text and image by default, in the palette and in the insert menu`
  - `it hides the image entry from the insert menu when blockTypes is ['text']`
    (regression for the affordance ignoring the prop)
  - `it offers a registered plugin type only when blockTypes enables it`
  - `it ignores a blockTypes entry that is not registered`
  - `it renders a stored plugin block through its editor view with the block context`
  - `it renders one hidden template per enabled type, keyed by type`
  - `it skips a stored block whose type is not enabled instead of rendering it as text`
  - existing cases (text/image serialisation by uid) unchanged.
- `app/Domains/News/Tests/Feature/Admin/NewsFormRendersMultiEditorTest.php` and
  `app/Domains/StaticPage/Tests/Feature/Admin/StaticPageFormRendersMultiEditorTest.php`
  — must stay green unchanged.
- E2E: `pnpm run e2e -- multi-editor` (core spec `e2e/tests/core/multi-editor.spec.ts`)
  green — it covers `_make` by key, insert/reorder/delete and state sync, which
  PHP cannot see. No Vitest: the component JS is inline Blade (arch §6).

**Acceptance.**
- ✅ News and static-page edit forms render exactly the text + image palette and
  insert-menu entries as before.
- ✅ `<x-editor::multi :blockTypes="['text']">` shows no image entry anywhere
  (palette **and** "+" menu).
- ✅ A registered, enabled fake type appears in both menus with its label, and a
  stored block of that type renders through its view receiving `$context`.
- ✅ `canGoSimple` is false as soon as any non-text block exists (checked by the
  core e2e spec still passing + manual check in VERIFY).
- ✅ `pnpm run e2e -- multi-editor` green.
- ✅ `pnpm run gate` green.

---

## Checkpoint 2v — visual regression check after the Editor refactor

Phases 1–2 refactor a component used by News, FAQ, static pages and chapters.
Check them in a real browser before Story builds on top, so a regression is
traced to the refactor and not to the new block. Dispatched to the
`visual-verifier` agent; **no code change** — a regression goes back to a
`phase-implementer` as a fix of phase 2 before phase 3 starts.

**Scope.** Existing behaviour only; no chapter-choice block exists yet.
- Run the full core e2e suite (`pnpm run e2e -- core`), not only `multi-editor`.
- News, FAQ and static-page admin editors, Avancé mode: palette and "+" menu show
  text + image with the same labels/icons as before; insert, reorder, delete,
  image upload; block chrome (from `<x-editor::multi.block>` if text/image use it,
  otherwise unchanged) looks as before at desktop and ≈375px.
- « Simple » switch: disabled with an image block present, enabled again with a
  single text block.
- Save and reopen each form; the public page renders the same HTML as before.
- Chapter edit form: unchanged (no `blockTypes` opt-in yet).

**Output.** Screenshots in `shots/checkpoint-2v/`, a short pass/fail list
appended under this section, status `DONE` in the phase index.

**Result (2026-09-27, HEAD 7f3a05ff) — PASS, no regression.** Method: every
page below was captured on HEAD and again with `app/Domains/Editor` checked
out at c0da6420 (pre-refactor), then compared; code restored afterwards.
- ✅ `pnpm run e2e:core` — 26/26 green (incl. `multi-editor`).
- ✅ News + StaticPage admin, Avancé: palette and "+" menu are
  `notes` « Ajouter du texte » + `image` « Ajouter une image », identical
  before/after (chapter create form too).
- ✅ Insert (+ menu, palette), move down/up (`blocks_order` follows), delete,
  image upload with alt/caption — both forms; desktop and 375px look right
  (`after-{news,static}-editor-375.png`).
- ✅ « Simple » disabled with an image block (tooltip is the new phase-2
  wording), still disabled with two text blocks, enabled with one.
- ✅ Save + reopen: reopens Avancé as text,image,text; public page shows
  text / image / caption / text (`after-*-qa-public.png`).
- ✅ Before/after diff: editor blocks DOM identical (modulo Alpine transition
  styles and random media-field ids), public rendered HTML identical for the
  new QA items and the existing `news/test` and `qui-sommes-nous`; all
  before/after screenshots byte-identical, so only the `after-*` set is kept.
- n/a FAQ admin — uses `<x-editor::rich-text>`, not the multi-editor.
- ✅ Chapter create form (author): unchanged, byte-identical screenshots.

---

## Phase 3 — Story: `chapter-choice` server side

**Goal.** Accept, validate, normalise, store and render `chapter-choice` blocks
on chapter create/update, with the server-side guard that a choice can only
target a chapter of the same story — before any UI exposes the block.

Architecture: §0 (spec deltas), §2.1 (block shape), §2.3, §3.1 (`render()`
context), §3.2, §3.3, §3.5, §4.3 (quoting constraint). Builds on phase 1:
`App\Domains\Editor\Public\Blocks\{EditorBlockType, EditorBlockRegistry}` exist
(registry is a container singleton; contract methods `key`, `labelKey`, `icon`,
`editorView`, `render(array $block, array $context)`), and
`EditorPublicApi::render($blocks, $profile, array $context = [])` forwards
`$context` to plugin types (the key `profile` is reserved by Editor).

**Deliverables.**
- `app/Domains/Story/Private/Support/ChapterChoiceTargets.php` — one query over
  the story's chapters, **all statuses**, `orderBy('sort_order')`, selecting
  `id, title, slug, status`. `forStory(Story $story): array<int, array{id:int, title:string, url:string, published:bool}>`
  keyed by id, in reading order. `url` = `route('chapters.show', ['storySlug' => $story->slug, 'chapterSlug' => $c->slug], false)`
  — **relative** so stored HTML survives a domain/APP_URL change; a later slug
  change is absorbed by the canonical 301 in `ChapterController`.
  Reused by phase 4 for the editor dropdown.
- `app/Domains/Story/Private/Editor/ChapterChoiceBlockType.php` — implements
  `EditorBlockType`: key `chapter-choice`, label `story::chapters.choice.block_label`,
  icon `alt_route`, view `story::editor.chapter-choice-block` (created in
  phase 4 — nothing renders it before then because no consumer enables the
  type). `render()`: for each choice, skip if `enabled` is false or
  `$context['chapters'][chapter_id]` is absent (deleted); link text = label if
  non-empty else the target's title; return `''` when no choice survives,
  else `view('story::editor.chapter-choice-render', …)->render()`.
- `app/Domains/Story/Private/Resources/views/editor/chapter-choice-render.blade.php`
  — `<div class="ce-block ce-block--chapter-choice not-prose indent-0 flex flex-wrap gap-2 …">`
  with one `<a href="{{ url }}" class="… no-underline! …">{{ text }}</a>` per
  choice (Blade escaping — plugin output is not re-sanitized by Editor).
  Constraints: the wrapper **never** carries `ce-block--text` and is never
  nested in one (quote exclusion, arch §4.3). `indent-0` cancels the article's
  inherited `[text-indent:2rem]`; `no-underline!` is needed because the
  unlayered `.rich-content a { text-decoration: underline }` in
  `Shared/Resources/css/app.css` beats layered utilities. Buttons wrap on
  mobile (A8). No `data-*` attributes needed.
- `app/Domains/Story/Public/Providers/StoryServiceProvider.php` — in `boot()`:
  `app(EditorBlockRegistry::class)->register(new ChapterChoiceBlockType())`.
- `deptrac.yaml` — add `EditorPublic` to `StoryPublic`'s allowed list with a
  comment « Registers the chapter-choice block type » (same precedent as the
  `MediaPublic` edge for the media usage provider). **Architecture §5 said "no
  new edge"; that was wrong for the provider** — see Open items.
- `app/Domains/Story/Private/Http/Requests/ChapterRequest.php`
  - `blocks.*.type` → `Rule::in(['text', 'image', 'chapter-choice'])`.
  - `blocks.*.choices` nullable array; `blocks.*.choices.*.chapter_id`
    nullable integer; `blocks.*.choices.*.label` nullable string max:120;
    `blocks.*.choices.*.enabled` nullable boolean.
  - `trimmedBlockLabels()` also trims each `choices.*.label`.
  - `messages()`: `blocks.*.choices.*.label.max` → `story::validation.chapter.choice.label_max`.
- `app/Domains/Story/Private/Support/ChapterContentResolver.php`
  - Signature → `resolve(array $data, int $actingUserId, Story $story, ?Chapter $chapter): array`.
  - Advanced walk, new branch `chapter-choice`: iterate `choices` in submitted
    order; drop a choice whose `chapter_id` is empty/non-numeric (A4); cast
    `chapter_id` to int, `label` trimmed → `null` if empty, `enabled` →
    bool (missing ⇒ `true`); drop the block if no choice is left (A4).
  - **Target check (security):** allowed ids = keys of
    `ChapterChoiceTargets::forStory($story)` ∪ every `chapter_id` found in
    `chapter-choice` blocks of `$chapter?->content_blocks` (the stored version —
    this is how a deleted target survives a save, #4). Any other id ⇒
    `ValidationException::withMessages(['blocks' => __('story::validation.chapter.choice.foreign_target')])`.
    Error on the `blocks` key (shown by the form) rather than per choice: only a
    crafted request can hit it, the dropdown never offers such ids.
  - Render with `$this->editor->render($blocks, 'multiedit-narrative', ['chapters' => $targets])`,
    `$targets` computed once and used for both the check and the render.
  - Stored shape exactly as arch §2.1:
    `['type' => 'chapter-choice', 'choices' => [['chapter_id' => int, 'label' => ?string, 'enabled' => bool], …]]`.
  - Update the class docblock.
- `app/Domains/Story/Private/Services/ChapterService.php` — `createChapter`
  passes `($story, null)`, `updateChapter` passes `($story, $chapter)`.
- `app/Domains/Story/Private/Resources/lang/fr/validation.php` —
  `chapter.choice.foreign_target` (« Un choix pointe vers un chapitre qui
  n’appartient pas à cette histoire. »), `chapter.choice.label_max`
  (« Le libellé d’un choix ne peut pas dépasser 120 caractères. »).
- `app/Domains/Story/Private/Resources/lang/fr/chapters.php` — `choice.block_label`
  => « Choix de chapitres » (needed now for `labelKey()`; the other strings come
  in phase 4).
- `app/Domains/Story/README.md` — short "Chapter choice block" subsection:
  block shape, save-time rendering (no read-time status check, dead links 404,
  frozen fallback title), the same-story target rule, relative URLs, not
  quotable because not `ce-block--text`.

**Tests.** `app/Domains/Story/Tests/Feature/Chapters/ChapterChoiceBlockTest.php`
(real author via `alice($this)`, `createStoryForAuthor`, `createPublishedChapter` /
`createUnpublishedChapter`; posts to `chapters.store` / `chapters.update` like
`ChapterAdvancedModeTest`):
- `it stores a choice block with the normalised shape`
- `it renders each enabled choice as a link to the target chapter`
  (relative `href` of `chapters.show`, inside `div.ce-block--chapter-choice`)
- `it uses the label as link text and falls back to the target title when empty`
- `it keeps the fallback title of the last save after the target is renamed`
- `it does not render a disabled choice`
- `it renders nothing for a block whose choices are all disabled but keeps it in content_blocks`
- `it renders a choice to an unpublished chapter` (#9 — no status check)
- `it drops a choice without target and a block left without choice`
- `it rejects a choice pointing to another story's chapter` (security — 302
  back with a `blocks` error, nothing saved)
- `it rejects a choice pointing to a non-existent id never stored`
- `it keeps a stored choice whose target was deleted, and renders nothing for it`
- `it lets a chapter target itself on update`
- `it rejects a label longer than 120 characters`
- `it does not count labels in word and character counts` (A5)
- `it never wraps a choice block in ce-block--text` (quote exclusion)
- `it escapes labels in the rendered HTML`
- Guest/confirmed reader: `it shows the choice links on the reader page`
  (GET `chapters.show` of the published holding chapter contains the link).
- `app/Domains/News/Tests/…` and `app/Domains/StaticPage/Tests/…`: one test each —
  `it rejects a chapter-choice block` (their `Rule::in(['text','image'])` is
  untouched; the test pins A1 server-side). Add them to
  `app/Domains/News/Tests/Feature/Admin/NewsAdvancedModeTest.php` and
  `app/Domains/StaticPage/Tests/Feature/Admin/StaticPageAdvancedRequestTest.php`.
- `app/Domains/Editor/Tests/…` nothing new.

**Acceptance.**
- ✅ An author posting a `chapter-choice` block targeting a chapter of **another
  story** gets a validation error and nothing is stored.
- ✅ A stored choice to a since-deleted chapter survives an unrelated save and
  renders nothing.
- ✅ Disabled choices never appear in `content`; a block with none left renders
  nothing but is kept in `content_blocks`.
- ✅ Rendered links are relative `chapters.show` URLs, text = label or title at
  save time, escaped.
- ✅ `word_count` / `character_count` identical with and without the block.
- ✅ News and StaticPage reject `type=chapter-choice`.
- ✅ Deptrac green with the new `StoryPublic → EditorPublic` edge.
- ✅ `pnpm run gate` green.

---

## Phase 4 — Story: `chapter-choice` editor partial and chapter form wiring

**Goal.** Let authors add, edit, reorder, enable/disable and remove choice
blocks in the chapter Avancé editor, with the chapter dropdown and the
« Chapitre supprimé » flag — chapter forms only.

Architecture: §0, §4.1, §4.2, §4.4. Builds on:
- phase 2 — `<x-editor::multi>` accepts `blockTypes` (list of registered keys to
  offer) and `blockContext` (array passed untouched to plugin partials as
  `$context`); a plugin partial receives `$name`, `$uid`, `$block` (`null` for a
  new block, cloned from a `<template>` with `__UID__` placeholders) and
  `$context`, and wraps itself in `<x-editor::multi.block type="…" :name :uid>`
  which provides the root `[data-block][data-type][data-uid]`, move/delete
  controls, hidden type input and "+" insert menu; the Simple switch is
  refused as soon as any non-text block exists;
- phase 3 — the `chapter-choice` type is registered (view name
  `story::editor.chapter-choice-block`, not yet created), `ChapterRequest` and
  `ChapterContentResolver` accept `blocks[uid][choices][i][chapter_id|label|enabled]`,
  and `App\Domains\Story\Private\Support\ChapterChoiceTargets::forStory($story)`
  returns the story's chapters (all statuses, reading order) as
  `id => {id, title, url, published}`.

**Deliverables.**
- `app/Domains/Story/Private/Resources/views/editor/chapter-choice-block.blade.php`
  - Wrapped in `<x-editor::multi.block type="chapter-choice" :name="$name" :uid="$uid">`.
  - Heading « Choix de chapitres ». A small local Alpine `x-data` (inline
    object, no bundle entry, no shared store) holding `choices` initialised
    from `$block['choices'] ?? [one empty enabled choice]`, with `add()`,
    `remove(i)`, `up(i)`, `down(i)`. **Use method names distinct from the
    multiEditor's `moveUp`/`moveDown`/`removeBlock`** — `e2e/pages/MultiEditor.ts`
    selects block controls by those exact handler strings.
  - Per choice, fields named `{name}[{uid}][choices][{i}][chapter_id|label|enabled]`
    (index bound from Alpine so reordering re-indexes; submitted order = order
    on screen):
    - `<select>` « Chapitre cible »: empty option, then every
      `$context['chapters']` entry in order, drafts suffixed « (non publié) »;
      the current chapter is in the list (loops allowed).
    - If the stored `chapter_id` is not in `$context['chapters']`: an extra
      selected option « Chapitre supprimé » for that id, plus a visible warning
      on the choice (#4). Saving keeps it (phase 3 allows stored ids).
    - Text input « Libellé (facultatif) », `maxlength="120"`, placeholder hints
      that the target title is used when empty.
    - Checkbox « Actif », default checked; an unchecked box must still submit
      `0` (hidden input before the checkbox, or a bound hidden value).
    - Up / down / remove buttons with `aria-label`s; all controls native and
      keyboard-operable.
  - « Ajouter un choix » button.
  - Tailwind utilities, stacks on small screens.
- `app/Domains/Story/Private/Resources/lang/fr/chapters.php` — under `choice`:
  `add` « Ajouter un choix », `target` « Chapitre cible », `label`
  « Libellé (facultatif) », `label_placeholder`, `enabled` « Actif »,
  `unpublished` « non publié », `deleted` « Chapitre supprimé »,
  `deleted_warning`, `move_up`, `move_down`, `remove`.
- `app/Domains/Story/Private/Controllers/ChapterController.php` — `create()` and
  `edit()` pass `choiceTargets` (`ChapterChoiceTargets::forStory($story)`) to
  the view (controllers go through the support/service, not the model).
- `app/Domains/Story/Private/Resources/views/chapters/partials/form.blade.php`
  - `<x-editor::multi … :blockTypes="['text', 'image', 'chapter-choice']" :blockContext="['chapters' => $choiceTargets]">`.
  - `old()` rebuild: keep `choices` for a `chapter-choice` block
    (`'choices' => $oldBlock['choices'] ?? []`, values as submitted — the
    partial casts `chapter_id` to int and `enabled` to bool).
- `e2e/tests/features/multiedit-chapter-switch-block.spec.ts` (+ page-object
  additions in `e2e/pages/MultiEditor.ts` only if needed; selectors stay in
  `pages/`): author on the seeded advanced chapter
  (`STORY.advancedChapter` in `e2e/support/fixtures.ts`):
  - inserting a « Choix de chapitres » block via the palette and via "+" gives
    a working block (template clone with a fresh uid);
  - add / reorder / remove choices, save, reopen: order, labels, enabled
    state preserved;
  - « Simple » is disabled while a choice block exists;
  - on the reader page the buttons navigate to the target chapter, and
    selecting a label shows no « Citer » toolbar.

**Tests.** `app/Domains/Story/Tests/Feature/Chapters/ChapterChoiceEditorTest.php`:
- `it offers the chapter choice block in the chapter edit and create forms`
  (palette entry label « Choix de chapitres »)
- `it lists every chapter of the story in reading order, drafts marked non publié, current chapter included`
- `it does not list chapters of another story`
- `it renders a stored choice block with its target, label and enabled state`
- `it flags a stored choice whose target was deleted as Chapitre supprimé`
- `it keeps choice blocks on a failed validation re-render` (old input)
- `it round-trips an edit without changes` (GET edit → POST the rendered
  values → same `content_blocks`)
- `app/Domains/News/Tests/Feature/Admin/NewsFormRendersMultiEditorTest.php` and
  `app/Domains/StaticPage/Tests/Feature/Admin/StaticPageFormRendersMultiEditorTest.php`:
  add `it does not offer the chapter choice block` (A1).
- Security: the edit form is only reachable by authors — already covered by
  `EditChapterTest`; add `it returns 404 on the edit form for a non-author` only
  if that test does not already cover it for a story with choice blocks (it
  does not need to — the rule is unchanged, arch §3.3).

**Acceptance.**
- ✅ An author sees « Choix de chapitres » in the palette and the "+" menu of a
  chapter form; News and static-page forms never show it.
- ✅ The dropdown lists exactly the story's chapters in reading order, drafts
  suffixed « (non publié) », the current chapter included, no other story's
  chapter.
- ✅ A stored choice to a deleted chapter shows « Chapitre supprimé » with a
  warning, and saving keeps it.
- ✅ Add / reorder / remove / disable choices then save round-trips exactly.
- ✅ A failed validation re-render keeps the choice blocks.
- ✅ `pnpm run e2e -- multiedit-chapter-switch-block` and
  `pnpm run e2e -- multi-editor` green.
- ✅ `pnpm run gate` green.

---

## Visual QA checklist

Filled by VERIFY. One row per surface worth looking at with real eyes, written
during PLAN while the flows are fresh.

| Surface | Check | OK? |
|---------|-------|-----|
| Chapter edit (author), Avancé | Palette and "+" menu show text, image, « Choix de chapitres » with icons; inserting one gives a block with one empty choice | |
| Chapter edit — choice block | Add / move up / move down / remove choices; controls aligned, keyboard-reachable, labelled | |
| Chapter edit — dropdown | All story chapters in reading order, drafts « (non publié) », current chapter present | |
| Chapter edit — deleted target | Choice to a deleted chapter shows « Chapitre supprimé » + warning; save succeeds | |
| Chapter edit — disabled choice | « Actif » unchecked survives save and reopen | |
| Chapter edit — mode switch | « Simple » disabled with tooltip while a choice block exists; enabled again after deleting it (single text block left) | |
| Chapter edit — validation error | Label > 120 (forced via devtools) or other error: form re-renders with choice blocks intact | |
| Chapter create (author) | Block available; current (unsaved) chapter absent from dropdown | |
| Chapter edit — mobile (≈375px) | Choice rows stack, select/label usable, no horizontal scroll | |
| Reader page — guest | Buttons render, wrap, no underline, no text indent, not styled as body text; click opens target | |
| Reader page — confirmed user | Same as guest; selecting a label shows no « Citer » toolbar; selecting text then dragging onto buttons hides « Citer » | |
| Reader page — author | Identical to reader view (no « non publié » marker — arch §0) | |
| Reader page — disabled / all-disabled | Disabled choice absent; all-disabled block leaves no empty box or gap | |
| Reader page — dead link | Choice to an unpublished chapter 404s for a guest (accepted, #9); deleted target renders nothing | |
| Reader page — renamed target | Old fallback title kept; link still resolves via canonical redirect | |
| Reader page — mobile (≈375px) | Buttons stack/wrap, tappable, no overflow | |
| Reader page — dark/theme | Button colours readable on the reading surface | |
| Author heat / quote highlights | Existing quotes in the chapter still highlight; no heat on choice labels | |
| News & static-page editors (admin) | No « Choix de chapitres » in palette or "+" menu; "+" menu honours `blockTypes` | |

## Open items

Plan-level refinements of the architecture, resolved here unless marked; BUILD
applies them without re-asking. Surface to the user at plan approval.

1. **Deptrac edge — architecture/code mismatch (phase 3).** Arch §5 says "no
   new edge", but registration happens in `StoryServiceProvider`, which lives
   in `Story/Public/Providers` (layer `StoryPublic`), and `StoryPublic` may not
   depend on `EditorPublic` today (`deptrac.yaml`). The plan adds
   `EditorPublic` to `StoryPublic`, mirroring the existing `MediaPublic` edge
   for `ChapterMediaUsageProvider`. Alternative if the user refuses the edge:
   register from a Story-private class the provider calls — more indirection
   for no gain.
2. **Contract gains `icon()` (phase 1).** Arch §3.1 lists `key`, `labelKey`,
   `editorView`, `render`; the palette and "+" menu render a Material icon per
   type, so the contract needs one more string method.
3. **Plugin block chrome as `<x-editor::multi.block>` (phase 2).** Arch §4.1
   says plugin partials "keep the existing contract" (root attributes, type
   input, controls, insert affordance). Doing that by hand would make a Story
   view `@include` Editor's private `_insert-affordance` partial. The plan adds
   a public anonymous component instead; text/image partials are left as is.
4. **Built-in partials keep explicit includes (phase 2).** Text and image
   partials need component-level props (toolbar, min/max, scope, …) that the
   generic `($name, $uid, $block, $context)` contract does not carry, so
   `multi.blade.php` keeps its two explicit includes and uses `editorView()`
   only for plugin types. Palette, "+" menu and templates do iterate the one
   registry list.
5. **Reserved `profile` context key (phase 1).** Built-in text rendering needs
   the Purifier profile; the renderer passes it as `$context['profile']`,
   overriding any consumer key of that name.
6. **Relative URLs in stored HTML (phase 3).** Arch §3.2 only says `url`; the
   plan stores `route(…, absolute: false)`.
7. **Reader-page styling (phase 3).** The article carries an inherited
   `text-indent` and an unlayered `.rich-content a { underline }` rule, so the
   render view needs `indent-0` and the important modifier `no-underline!`.
   Tailwind utility classes stored in `content` go stale if renamed later —
   same as any stored HTML; accepted. VERIFY confirms the look.
8. **Foreign-target error on the `blocks` key (phase 3)**, not per choice (uids
   are re-generated on re-render; only a crafted request can trigger it).
9. **Stored blocks of a non-enabled type are now skipped in the editor
   (phase 2)** instead of falling back to a text block. No consumer stores
   such blocks today (News/StaticPage validate `text|image`).
