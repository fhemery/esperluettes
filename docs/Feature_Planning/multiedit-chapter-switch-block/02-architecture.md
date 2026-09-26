# Multi-edit — chapter switch block — architecture

> DESIGN output. Describes **how** the feature is built. Every tradeoff the user
> arbitrated is recorded in §7 with the rejected options.
>
> Scope: **shape and contracts, not a change list.**

- Functional spec: [`01-functional.md`](./01-functional.md)
- Decisions: [`DECISIONS.md`](./DECISIONS.md) — **#8–#12 supersede parts of the
  spec** (#1, #2, #4). Where this document and `01-functional.md` disagree, this
  document wins; PLAN should read §0 before the spec.
- **Prerequisite:** `quotable-blocks-opt-in/` must be finished first (decision
  #12). This design assumes its outcome — see §4.3.

## 0. Spec deltas agreed at DESIGN

The spec was written assuming read-time resolution of targets. DESIGN replaced
that with a static, save-time rendering (decision #9). Net effect:

| Spec § | Was | Now |
|--------|-----|-----|
| 4.2.3, 4.3.3, 4.3.4, #1 | Readers never see a choice to an unpublished/deleted chapter | **No publication/existence check at read time.** A choice to an unpublished or deleted chapter is a link that 404s for the reader — the author's responsibility, as with plain links today. |
| 4.2.4 | Authors see « non publié » markers on the reader page | Dropped. The reader page is identical for everyone. |
| new | — | Each choice has an author-controlled **enabled** toggle (default on). A **disabled** choice is **not rendered** for anyone on the reader page (#10). This is how an author prepares branches before publishing them. |
| 4.3.1, #2 | Empty label shows the target's *current* title | Empty label falls back to the target's title **as of the last save** of the holding chapter (#11). |
| 4.3.4, #4 | Deleted target flagged in the editor, save allowed | Kept — it is an edit-time check against the dropdown list, costs nothing. On the reader page a deleted target renders nothing (no URL can be built). |
| 4.3.1 | Rename keeps the link working | Holds: URLs are `slug-id`, and `ChapterController` 301-redirects stale slugs to the canonical one. |
| 4.2.5, #6 | Labels not quotable | Holds, by construction of the prerequisite: the choice block does not opt into quoting. |

Everything else in the spec stands (A1–A9, §4.1, §4.4, §5, §8).

## 1. Domain placement

- **Editor** gains the extension point: a block-type registry (decision #8).
- **Story** owns the `chapter-choice` block type entirely: its editor partial,
  its normalisation/validation, its rendering, its French strings. It registers
  it in the Editor registry and opts the chapter form into it.
- No new domain.

### 1.1 Changes in other domains

**Editor — new extension point.** Today `text`/`image` are hardcoded in ~8
places (palette, "+" menu, `@foreach` fallback, JS `_make()`, renderer, three
form requests). The registry replaces the Editor-side hardcoding:

- a public `EditorBlockType` contract and an `EditorBlockRegistry` singleton,
  both under `Editor/Public`;
- `text` and `image` become built-in registrations made by
  `EditorServiceProvider`, so the component and the renderer iterate one list;
- `<x-editor::multi>` keeps its `blockTypes` prop as the **per-consumer opt-in
  list** (default `['text','image']`). The palette, the "+" insert menu
  (which currently ignores the prop — fixed here), the hidden templates and the
  server-rendered existing blocks all iterate the enabled registered types;
- a new `blockContext` prop (array, default `[]`) is passed through untouched to
  every block partial — the Editor never interprets it. Story uses it for the
  chapter list;
- `ContentBlocksRenderer` delegates any non-built-in type to the registered
  type's `render()`; unknown types keep being dropped;
- the "can go Simple" guard counts **every non-text block**, not only images
  (A2 generalised).

**News / StaticPage — no change.** They pass no `blockTypes` (default) and keep
their own `Rule::in(['text','image'])`. The block is structurally unreachable
from them (A1).

**Quote / Shared anchoring — no change in this task.** Handled by the
prerequisite (§4.3).

## 2. Data model

### 2.1 Tables

None. The block lives in the existing `story_chapters.content_blocks` JSON, and
its rendered HTML in `story_chapters.content` (single-writer rule unchanged).

Block shape:

```php
[
    'type'    => 'chapter-choice',
    'choices' => [
        ['chapter_id' => 42, 'label' => 'Ouvrir la porte', 'enabled' => true],
        ['chapter_id' => 43, 'label' => null,             'enabled' => false],
    ],
]
```

- `chapter_id` — int, a chapter of the same story (possibly the holding one).
- `label` — `?string`, plain text, trimmed, max 120 (A9); empty → `null`.
- `enabled` — bool, default `true` (#10).

Only the id is stored, never a slug or title: the title/URL are resolved at
**save** time into the rendered HTML (#9, #11).

### 2.2 Model

No model change. `Chapter::$casts['content_blocks'] = 'array'` already covers it.

### 2.3 Lifecycle rules

- **Target renamed** — link keeps working (canonical redirect); fallback label
  stays the old title until the holding chapter is saved again (#11).
- **Target unpublished / deleted** — nothing happens. No listener, no cascade,
  no re-render (#9). Reader gets a 404 if they click; the editor flags a deleted
  target on next edit.
- **Holding chapter / story deleted, moderation « empty content »** — existing
  behaviour, the block goes with the content.
- `ChapterMediaUsageProvider` is untouched: the block stores no media path.

## 3. PHP architecture

### 3.1 Public API (Editor)

```php
namespace App\Domains\Editor\Public\Blocks;

interface EditorBlockType
{
    /** Stable key stored in content_blocks[*]['type']. */
    public function key(): string;

    /** Translation key of the palette / insert-menu label. */
    public function labelKey(): string;

    /** Blade view rendering one block in the editor. Receives
     *  $uid, $name, $block (array|null for a new one), $context (blockContext). */
    public function editorView(): string;

    /** Render one normalised block to sanitized HTML. $context is the
     *  consumer-supplied render context (see EditorPublicApi::render). */
    public function render(array $block, array $context): string;
}

final class EditorBlockRegistry
{
    public function register(EditorBlockType $type): void;   // throws on duplicate key
    public function get(string $key): ?EditorBlockType;
    /** @return list<EditorBlockType> in registration order */
    public function all(): array;
}
```

`EditorPublicApi::render(array $blocks, string $profile = 'multiedit-text', array $context = []): string`
— one new optional argument, forwarded to plugin types' `render()`. Built-in
types ignore it. `plainText()` / `plainTextLength()` unchanged: text blocks only,
so labels stay out of word/char counts (A5) by construction.

Normalisation/validation deliberately stays **out** of the contract: each
consumer already owns it (`ChapterContentResolver`, `NewsService`,
`StaticPageService`), and only Story needs to understand `chapter-choice`.

### 3.2 Services (Story)

- A Story-private `ChapterChoiceBlockType implements EditorBlockType`,
  registered from `StoryServiceProvider::boot()`.
  - `render()` expects `$context['chapters']`: a map `id → {title, url}` of the
    story's chapters. Emits nothing for a disabled choice, nothing for an id
    absent from the map (deleted), and nothing at all when no choice survives.
    Otherwise one `div.ce-block.ce-block--chapter-choice` wrapping one `<a>`
    per choice; label (escaped) or the title fallback as link text.
- `ChapterContentResolver` handles the new block type during its ordered walk:
  - drops choices without `chapter_id`, drops the block when no choice is left (A4);
  - **target check:** a `chapter_id` must be a chapter of the same story, **or**
    an id already present in the chapter's currently stored `content_blocks`
    (that is how a deleted target survives a save — #4). Anything else is a
    validation error on that choice;
  - builds the render context once (one query over the story's chapters, all
    statuses) and passes it to `EditorPublicApi::render`.
  The resolver therefore needs the story and the chapter being edited (null on
  create) — a signature change for PLAN.
- Chapter create: the chapter does not exist yet, so it cannot target itself
  until its first save. Accepted, not worth a special case.

### 3.3 Policy / authorization

Unchanged. Only authors can reach the edit form (`ChapterPolicy::edit`); the
target check in §3.2 is the server-side guard against pointing a choice at
another story's chapter. Reading follows chapter visibility (A3).

### 3.4 Events and listeners

None emitted, none listened to (A6, #9).

### 3.5 Routes, controllers, form requests

No new route. `ChapterRequest` accepts `chapter-choice` in the type rule and
validates `blocks.*.choices.*.{chapter_id,label,enabled}` (int / nullable string
max 120 / boolean). The chapter form's `old()` rebuild must preserve the new
block's keys.

## 4. Frontend architecture

### 4.1 Editor component

- Templates: one hidden `<template>` per **enabled** registered type, keyed by
  type; `_make(type, uid)` looks the template up by key instead of the
  `image ? … : text` branch.
- Palette and "+" insert menu loop over enabled types, label from `labelKey()`.
- `syncState()`: `canGoSimple = blockCount === 1 && nonTextCount === 0`.
- Block partials keep the existing contract: root `[data-block][data-type][data-uid]`,
  hidden `{name}[{uid}][type]` input, move/delete controls, insert affordance.

### 4.2 Chapter-choice editor partial (Story view)

- Server-rendered Blade + a small local Alpine `x-data` for the choice list:
  add, remove, move up/down a choice. No shared store, no bundle entry.
- Per choice: `<select>` of `blockContext['chapters']` (reading order, drafts
  suffixed « non publié », current chapter included), a label input
  (« Libellé (facultatif) », maxlength 120), an enabled checkbox.
- A stored `chapter_id` absent from the list is kept as a selected option
  labelled « Chapitre supprimé », with a visible warning (#4).
- The chapter form builds `blockContext['chapters']` from the story's chapters
  (all statuses — this is the author view).

### 4.3 Reader page and quoting

- The reader page prints `content` as today. The choice block is plain links
  styled as buttons (Tailwind, wrapping on mobile — A8), real `<a>` elements
  whose accessible name is their text.
- **Quoting**: the prerequisite `quotable-blocks-opt-in/` makes anchoring read
  only blocks that opt into it. `ce-block--chapter-choice` does not opt in, so
  its labels are neither quotable nor part of the canonical text (#6). This task
  adds no anchoring code. If the prerequisite lands with a different mechanism,
  PLAN adapts this paragraph — it must not add an opt-out marker as a stopgap.

### 4.4 Strings

Story French lang file: « Choix de chapitres », « Ajouter un choix »,
« Libellé (facultatif) », « Chapitre cible », « Actif », « non publié »,
« Chapitre supprimé ». Editor lang gains nothing type-specific.

## 5. Deptrac

No new edge.
- `StoryPrivate → EditorPublic` already exists (registration, render call).
- `EditorPrivate → EditorPublic` already exists (renderer reads the registry).
- Blade use of Story's partial by the Editor component happens by view name at
  runtime, which deptrac does not see — same limitation as editor-domain #11.

## 6. Testing strategy

- **Integration (Story)**: create/update a chapter with a choice block →
  stored `content_blocks` shape and rendered links (URL + label/title
  fallback); disabled and deleted targets render nothing; empty block dropped;
  choice to another story's chapter rejected; previously stored deleted target
  survives a save; word count unaffected; News/StaticPage requests reject
  `chapter-choice`.
- **Integration (Editor)**: registry duplicate-key guard; renderer delegates to a
  registered fake type with context; unknown type still dropped; component
  renders palette/insert entries only for enabled types.
- **Vitest**: only if the multi-editor JS is extracted to a module; otherwise the
  JS paths (`_make` by key, `canGoSimple`) are covered in VERIFY.
- **VERIFY (browser)**: add/reorder/remove choices, deleted-target flag, Simple
  switch refused, buttons wrap on mobile, labels not selectable into a quote,
  News/StaticPage editors show no « Choix de chapitres ».

## 7. Tradeoffs locked

| # | Question | Options considered | Chosen | Why |
|---|----------|--------------------|--------|-----|
| 1 | How does a Story-only block plug into the Editor? | **A** global registry + per-consumer `blockTypes` opt-in · B consumer passes type definitions per instance/call · C hardcode in Editor, gated by prop | A | Matches existing registries (ProfileTab, Moderation); Editor docs planned it for the third type. C puts Story UI in Editor; B threads definitions through every call (#8) |
| 2 | Target status/title need read-time info vs stored HTML | A placeholder in stored HTML resolved per request · B event-driven re-render of linking chapters · **C static save-time render, no status check, author-controlled enable toggle** | C | User's call: authors already manage plain links; a 404 on a forgotten one is acceptable. Zero read-time cost, no listeners, #8 of chapters-multi-edit untouched (#9) |
| 3 | What does a disabled choice do for readers? | **Hidden** · greyed out | Hidden | #10 |
| 4 | Empty label fallback after a rename | **Frozen title at save** · required label | Frozen | #11 |
| 5 | Keeping labels out of quotes | Opt-out marker now + backlog opt-in · **opt-in quotability as a prerequisite task, this one blocked** | Prerequisite | User refuses to build on a model to be reversed; the missing opt-in is a debt to repay first (#12) |
| 6 | Where does validation/normalisation of a plugin block live? | In the `EditorBlockType` contract · **in the consumer's resolver** | Consumer | Consumers already own it; only Story understands the block. Not user-arbitrated |

## 8. File layout

```
app/Domains/Editor/Public/Blocks/
    EditorBlockType.php
    EditorBlockRegistry.php
app/Domains/Editor/Private/Blocks/
    TextBlockType.php
    ImageBlockType.php
app/Domains/Story/Private/Editor/
    ChapterChoiceBlockType.php
app/Domains/Story/Private/Resources/views/editor/
    chapter-choice-block.blade.php
```

## 9. Risks acknowledged

- **Stale fallback titles** after a rename — revisit (re-render linking
  chapters on `ChapterUpdated`) if authors complain.
- **Dead links** to unpublished/deleted chapters are visible to readers —
  revisit with read-time resolution (option 2A) if it becomes a support topic.
- **Editor Blade includes a Story view by name** — invisible to deptrac.
  Acceptable for one plugin; revisit if block types multiply.
- **Prerequisite drift** — §4.3 assumes the opt-in mechanism. If
  `quotable-blocks-opt-in/` chooses differently, PLAN must reconcile.
