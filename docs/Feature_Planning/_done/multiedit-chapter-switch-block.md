# Multi-edit — chapter switch block

**Status:** DONE — 2026-09-27 · **Domain(s):** `Editor`, `Story`. Decision
numbers (#n, An) refer to the task's `DECISIONS.md`; "spec", "arch." and "plan"
to its `01`–`03` documents. All are deleted; git history has them.

## What it does

Advanced (multi-edit) chapters can hold a « Choix de chapitres » block: an
ordered group of buttons, each linking to a chapter of the same story (the
holding chapter included — loops allowed). Built for "histoire dont vous êtes le
héros" stories. To make it possible, Editor gained a **block-type registry**:
`text` and `image` became built-in registrations, and any domain can register a
type that a consumer opts into per `<x-editor::multi>` instance. Only the
chapter form opts into `chapter-choice`; News and static pages cannot reach it.

## Key behaviour

- **Rendered once, at save time**, into `story_chapters.content`. No read-time
  check at all: a choice to an unpublished or deleted chapter is a plain link
  that 404s (deleted target → rendered as nothing, since no URL can be built).
- **Reader page is identical for everyone**, author included — no « non publié »
  marker (the spec's read-time visibility rules #1/#2/#4 were replaced at
  DESIGN by #9–#11).
- **Per-choice `enabled` toggle** (default on): a disabled choice is never
  rendered, for anyone. That is how an author prepares an unpublished branch.
  A block whose choices are all disabled renders nothing but stays in
  `content_blocks`.
- **Empty label → target title frozen at the holding chapter's last save.** A
  rename of the target does not propagate; the link still works via the
  canonical 301 (relative URLs `chapters.show`, `absolute: false`).
- **Same-story rule (security):** a `chapter_id` must be a chapter of the story
  (any status), or an id the edited chapter already stored (how a deleted
  target survives a save, shown as « Chapitre supprimé » + warning in the
  editor). Otherwise a validation error on `blocks` (not per choice).
- Choices without a target are dropped, then blocks without a choice.
- **Not quotable, not counted:** wrapper is `ce-block--chapter-choice`, never
  `ce-block--text`; word/char counts read text blocks only.
- « Simple » is refused while any **non-text** block exists (generalised from
  images only).
- On create, the chapter cannot target itself (it has no id yet) — accepted.
- Editor does **not** re-sanitize plugin output: the type escapes its own HTML.

## Where the code lives

| Concern | Path |
|---------|------|
| Registry + contract (`key`, `labelKey`, `icon`, `editorView`, `render`) | `app/Domains/Editor/Public/Blocks/` |
| Built-in types | `app/Domains/Editor/Private/Blocks/{Text,Image}BlockType.php` |
| Renderer (dispatches via registry, drops unknown types) | `app/Domains/Editor/Private/Support/ContentBlocksRenderer.php` |
| Block chrome for plugin partials | `app/Domains/Editor/Private/Resources/views/components/multi/block.blade.php` (`<x-editor::multi.block>`) |
| Choice block type (render) | `app/Domains/Story/Private/Editor/ChapterChoiceBlockType.php` |
| Target list (all chapters, reading order) | `app/Domains/Story/Private/Support/ChapterChoiceTargets.php` |
| Normalise + target check + render call | `app/Domains/Story/Private/Support/ChapterContentResolver.php` |
| Validation | `app/Domains/Story/Private/Http/Requests/ChapterRequest.php` |
| Views | `app/Domains/Story/Private/Resources/views/editor/chapter-choice-{block,row,render}.blade.php` |
| Tests | `Story/Tests/Feature/Chapters/ChapterChoice{Block,Editor}Test.php`, `Editor/Tests/Feature/{EditorBlockRegistry,ContentBlocksRenderer,MultiEditorComponent}Test.php` |
| Migrations | none — lives in `content_blocks` JSON |

## Extension points used

- **New:** `EditorBlockRegistry` (Editor). `<x-editor::multi>` props
  `blockTypes` (opt-in list, default `['text','image']`) and `blockContext`
  (passed untouched to plugin partials as `$context`);
  `EditorPublicApi::render(..., array $context = [])` with reserved `profile` key.
- Story registers `ChapterChoiceBlockType` from `StoryServiceProvider::boot()`.
- New deptrac edge `StoryPublic → EditorPublic` (arch §5 said "no new edge";
  wrong, since the provider lives in `Story/Public` — plan open item 1).

## Decisions worth remembering

- Registry + per-consumer opt-in, not hardcoding in Editor (#8).
- Static save-time render, author owns dead links, no listeners (#9); revisit
  with read-time resolution only if dead links become a support topic.
- Validation/normalisation stays in the consumer, not in the contract.
- Built-in text/image partials keep explicit `@include`s (they need component
  props); `editorView()` is only used for plugin types.
- Choice rows are server-rendered; the local Alpine scope moves DOM rows and
  re-indexes names — no `choices` array, no `x-model` on dynamic selects (A14).
- Button colour: `accent` (#13), not `<x-shared::button>` primary. **Kept at
  WRAP (2026-09-27, user)** despite spring-light contrast 3.02; the fix belongs
  to `shared-button-contrast/`, not here.

## Assumptions to confirm (reversible)

A1 story chapters only · A2 Simple refused with a choice block · A3 edit/read
rights follow the chapter · A4 empty choices/blocks dropped · A5 labels not
counted · A6 no notification/event/stat/search/moderation · A7 no cap on
choices · A8 buttons wrap on mobile · A9 label plain text ≤ 120 · A10 block
chrome reads `blockTypes` via `@aware` · A11 stored text/image of a non-enabled
type skipped too · A12 self-targeting choice rendered with pre-save slug/title ·
A13 (superseded by #13) · A14 server-rendered rows · A15 last choice removable.

## Colour check at WRAP

- #13 (accent) was applied after VERIFY without a visual check. At WRAP the
  feature spec measured it: light 3.02 (spring) · 4.71 (summer) · 5.69
  (winter) · 6.51 (autumn); dark 5.1–7.1. Spring light is below AA and worse
  than primary (3.1–3.6). No screenshot. User decision: keep accent, fix it in
  `shared-button-contrast/`.

## Not done

- Non-goals (spec §8): other editors, prev/next nav changes, cross-story or
  external links, converting inline links, branch map/story flag, reader choice
  tracking, Simple mode / author note, notifications/stats/search.
- Accepted risks (arch §9): stale fallback titles after a rename; dead links
  visible to readers; Editor includes a Story view by name (invisible to deptrac).
- Palette buttons wrap their labels onto 2–3 lines at 375px (cosmetic, not
  filed).
- Stale `public/build` let the gate pass without `no-underline!` — already
  filed in `improve-loop/`.
- Backlog: [`shared-button-contrast`](../shared-button-contrast/) —
  light-theme contrast of accent/primary buttons.
- E2E: `e2e/tests/features/multiedit-chapter-switch-block.spec.ts` **deleted**
  (all 18 tests green at WRAP; behaviour covered by PHP tests + core
  `multi-editor`). Page objects `ChapterChoiceBlock.ts`, `ChapterCreatePage.ts`
  and the new `ChapterPage` helpers were kept; `ChapterCreatePage` is now unused.
