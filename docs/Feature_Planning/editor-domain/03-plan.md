# Extract an Editor domain from Shared — implementation plan

> PLAN output. The phase index at the top is the summary; everything below is
> detail. BUILD reads one phase at a time.

- Functional spec: [`01-functional.md`](./01-functional.md)
- Architecture: [`02-architecture.md`](./02-architecture.md)
- Decisions: [`DECISIONS.md`](./DECISIONS.md)

## Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | PHP core — renderer, `EditorPublicApi`, News, deptrac layers | S | — | DONE |
| 2 | Blade components, lang and the 11 call sites | M | 1 | DONE |
| 3 | Toolbar presets | S | 2 | DONE |
| 4 | JS bundle move + self-loading components + 15 `@vite` deletions | M | 2 | TODO |
| 5 | CSS split into its own Vite entry | S | 4 | TODO |
| 6 | Domain documentation | S | 5 | TODO |

Sizes: S ≈ half a day, M ≈ 1–2 days, L → split it.
Status per phase: `TODO` · `WIP` · `DONE`. BUILD updates this table as it goes;
it is what lets `WIP:BUILD (3/7)` resume correctly.

## Working agreement

- One phase = one commit (or one PR). Each phase ships independently, keeps
  `npm run gate` green, and is revertable on its own. **Risk R5 makes this
  non-negotiable**: `chapters-multi-edit/` and `annotations/` touch the same
  files, so nothing here may sit on a long-lived branch. Merge each phase.
- Failing test first, then the implementation. Every phase below names its red
  test concretely.
- We do not move to phase N+1 until phase N's acceptance criteria are met.
- Re-ordering phases mid-build is a decision to surface, not to take silently.
- **This is a refactor with no user-visible change.** The acceptance criterion of
  every phase is: behaviour preserved, `npm run gate` green. No phase has a demo.
- Docs are cleaned up in the phase that moves the thing they describe (rule 3 of
  `AGENTS.md`); phase 6 only consolidates.

### Why the 11 call sites are not phased per consumer domain

Considered: one phase per consumer domain (Calendar, Comment, FAQ, …), enabled by
keeping the old `<x-shared::editor>` as a thin wrapper delegating to
`<x-editor::rich-text>` for the duration. Rejected:

- A wrapper resurrects exactly the implicit Shared coupling the task deletes, and
  decision #2 already ruled against keeping a compatibility alias. It costs two
  extra commits (add shim, remove shim) and a window in which both spellings work.
- The rename is mechanical and **test-covered at the source**: every consumer page
  already has a feature test that renders it, so a missed or mistyped call site is
  a red test in the same run, not a silent regression. That is a stronger net than
  incremental exposure.
- The two file lists overlap (11 call-site files, 15 `@vite` files, ~26 distinct;
  `comment-list.blade.php`, `profile/edit.blade.php`, the Story and News forms are
  in both). Per-domain slicing means touching several of those files twice in two
  different phases — more diff, more conflict surface against R5, not less.
- The slice that *does* pay off is by **concern**, not by consumer: rename
  (phase 2) → preset vocabulary (phase 3) → assets (phases 4–5). Each of those has
  a different failure mode and a different test, and each produces a diff a human
  can review in one sitting.

So: all call sites move at once in phase 2, but phase 2 carries *only* the
mechanical rename — no toolbar change, no asset change.

---

## Phase 1 — PHP core: renderer, `EditorPublicApi`, News, deptrac layers

**Goal.** Create the `Editor` domain with its PHP surface and move
`ContentBlocksRenderer` into it, with no Blade, lang or asset change.

**Deliverables.**
- `app/Domains/Editor/Private/Support/ContentBlocksRenderer.php` — moved verbatim
  from `app/Domains/Shared/Support/`, namespace changed, body untouched.
- `app/Domains/Editor/Public/Api/EditorPublicApi.php` — `final class`, constructor
  injects the renderer, three delegating methods (`render`, `sanitizeText`,
  `plainTextLength`). Concrete class, autowired like `MediaPublicApi`; **no service
  provider is created in this phase** — nothing needs binding yet (minimum code).
- `app/Domains/News/Private/Services/NewsService.php` — injects `EditorPublicApi`
  instead of `ContentBlocksRenderer`; call sites updated.
- `deptrac.yaml` — add layers `EditorPublic` / `EditorPrivate` / `EditorTests`;
  rulesets `EditorPublic: [Shared]`,
  `EditorPrivate: [Shared, EditorPublic, MediaPublic]`, `EditorTests` as the other
  `*Tests` layers do; add `EditorPublic` to `NewsPrivate`.
  **`Shared: - MediaPublic` is NOT removed here** — see phase 2.
- `app/Domains/Editor/README.md` + `AGENTS.md` — stub: domain purpose, "owns no
  table", the block schema contract from architecture §3.1. Grown in later phases.
- `AGENTS.md` (root) — add the `Editor` row to the Domain Registry table.
- Deleted: `app/Domains/Shared/Support/ContentBlocksRenderer.php`,
  `app/Domains/Shared/Tests/Feature/ContentBlocksRendererTest.php`.

**Tests.**
- `app/Domains/Editor/Tests/Feature/ContentBlocksRendererTest.php` — the moved
  file, re-namespaced, **assertions unchanged** (assumption A5). *This is the red
  test*: on the first commit it references
  `App\Domains\Editor\Private\Support\ContentBlocksRenderer`, which does not exist.
- `app/Domains/Editor/Tests/Feature/EditorPublicApiTest.php` — new. Asserts
  `app(EditorPublicApi::class)` resolves without an explicit binding, and that
  `render()` / `sanitizeText()` / `plainTextLength()` return exactly what the
  renderer returns for one text block, one image block and a mixed document
  (including the `ce-block` / `ce-block--text` / `ce-block--image` classes).
- Existing `app/Domains/News/Tests/**` — untouched, must stay green. They are the
  regression signal that News's min/max validation is unaffected (§4.1.3).

**Acceptance.**
- ✅ `app/Domains/Shared/Support/ContentBlocksRenderer.php` no longer exists and
  nothing under `app/Domains/Shared` references it.
- ✅ `grep -rn "ContentBlocksRenderer" app/Domains` returns hits only under
  `app/Domains/Editor`.
- ✅ `EditorPublicApi::render()` output is byte-identical to the pre-move renderer
  for the fixtures in `ContentBlocksRendererTest`.
- ✅ Deptrac reports no violation with the four ruleset changes above.
- ✅ No Blade file, no lang file, no asset, no `vite.config.js` entry changed in
  this phase.
- ✅ `npm run gate` green.

---

## Phase 2 — Blade components, lang and the 11 call sites

**Goal.** Move the two components, their partials and their two lang files into
`Editor`, register the `editor::` namespace, and repoint all 11 call sites — with
props, toolbar arrays and asset lines untouched.

**Deliverables.**
- `app/Domains/Editor/Public/Providers/EditorServiceProvider.php` — new;
  `loadViewsFrom(…, 'editor')`, `Blade::anonymousComponentPath(…, 'editor')`,
  `loadTranslationsFrom(…, 'editor')`, exactly as `MediaServiceProvider` does.
  **Prefixed path only** — no unprefixed alias (decision #2).
- `bootstrap/providers.php` — register `EditorServiceProvider` (after
  `MediaServiceProvider`, since the components compose `<x-media::image-field>`).
- Moved into `app/Domains/Editor/Private/Resources/`:
  - `views/components/rich-text.blade.php` ← `Shared/…/components/editor.blade.php`
  - `views/components/multi.blade.php` ← `Shared/…/components/multi-editor.blade.php`
    (its internal `<x-shared::editor>` on line 79 becomes `<x-editor::rich-text>`)
  - `views/components/multi/{_text-block,_image-block,_insert-affordance}.blade.php`
  - `lang/fr/rich-text.php` ← `Shared/…/lang/fr/editor.php`
  - `lang/fr/multi.php` ← `Shared/…/lang/fr/multi-editor.php`
  - all `shared::editor.*` / `shared::multi-editor.*` references inside the moved
    files become `editor::rich-text.*` / `editor::multi.*` (assumption A2). No
    string is added, removed or reworded.
- The 11 call-site files, prefix + name only (13 occurrences — `comment-item` and
  `chapters/partials/form` each hold two):
  - `Calendar/…/SecretGift/…/partials/_gift-preparation.blade.php`
  - `Calendar/…/pages/admin/activities/_form.blade.php`
  - `Comment/…/components/comment-list.blade.php`
  - `Comment/…/components/partials/comment-item.blade.php` ×2
  - `FAQ/…/pages/admin/faq-questions/_form.blade.php`
  - `Message/…/pages/compose.blade.php` (`<x-editor>` → `<x-editor::rich-text>`)
  - `News/…/pages/admin/news/_form.blade.php` (`multi-editor` → `multi`)
  - `Profile/…/pages/edit.blade.php`
  - `StaticPage/…/pages/admin/_form.blade.php`
  - `Story/…/chapters/partials/form.blade.php` ×2
  - `Story/…/components/form.blade.php`
- `deptrac.yaml` — **remove `MediaPublic` from the `Shared` ruleset.** This is the
  phase that moves the last file responsible for that edge (`_image-block` composes
  `<x-media::image-field>`); removing it here is what architecture §5 requires.
  See Open item O2 for what this does and does not prove.
- `app/Domains/Shared/{README,AGENTS}.md` — remove the editor-component and
  `shared::editor` sections; `Resources/js/editor-bundle.js` rows stay until
  phase 4.
- `app/Domains/Shared/Providers/SharedServiceProvider.php` — drop the editor from
  the comment on line 66 and, if the unprefixed anonymous-component registration
  exists only for the editor, remove it (Open item O5).
- `app/Domains/Editor/README.md` — component contract section (names, props).
- Deleted: `Shared/…/components/editor.blade.php`,
  `Shared/…/components/multi-editor.blade.php`, `Shared/…/components/multi-editor/`,
  `Shared/…/lang/fr/editor.php`, `Shared/…/lang/fr/multi-editor.php`,
  `Shared/Tests/Feature/MultiEditorComponentTest.php`.

**Tests.**
- `app/Domains/Editor/Tests/Feature/MultiEditorComponentTest.php` — the moved file
  with `<x-shared::multi-editor …>` rewritten to `<x-editor::multi …>`, assertions
  otherwise unchanged (A5). *This is the red test*: `editor::` is not a registered
  Blade namespace until the provider lands.
- `app/Domains/Editor/Tests/Feature/RichTextComponentTest.php` — new, small. The
  `rich-text` component has no test today. Asserts that
  `<x-editor::rich-text name="body" id="e1" :min="10" :max="100" />` renders the
  hidden `textarea[name=body]`, the `#quill-counter-e1` block, the
  `data-toolbar` attribute with the default token list, and the
  `editor::rich-text.character` / `.min-characters` strings — i.e. that the lang
  namespace resolved.
- Updated (lang keys only, `shared::editor.` → `editor::rich-text.`):
  - `app/Domains/Comment/Tests/Feature/CommentFragmentControllerTest.php:249`
  - `app/Domains/Comment/Tests/Feature/Views/RenderCommentListComponentTest.php:91`
  - `app/Domains/Story/Tests/Feature/Chapters/ViewChapterComments.php:23`
- Untouched and green: every consumer page test (Calendar, Comment, FAQ, Message,
  News, Profile, StaticPage, Story). **They are the real regression net for the
  rename** — a missed call site renders a Blade "component not found" error and
  turns them red.

**Acceptance.**
- ✅ `grep -rn "x-shared::editor\|x-shared::multi-editor\|<x-editor[ >]" app/Domains`
  returns nothing.
- ✅ `grep -rn "shared::editor\|shared::multi-editor" app/Domains` returns nothing.
- ✅ The `Shared` ruleset in `deptrac.yaml` no longer lists `MediaPublic`, and
  deptrac is green.
- ✅ `<x-editor>` (unprefixed) no longer resolves — asserted by the absence of any
  unprefixed registration, and by `Message` compose rendering through the prefixed
  form in its page test.
- ✅ No `@vite` line, no `vite.config.js` entry, no CSS rule changed in this phase;
  `editor-bundle.js` still lives in `Shared` and the 15 hand-written `@vite` lines
  still point at it.
- ✅ Toolbar props are still the literal arrays they are today (presets are phase 3).
- ✅ `npm run gate` green.

---

## Phase 3 — Toolbar presets

**Goal.** Publish the four token sets as named presets inside Editor and replace
the five repeated array literals at call sites, with token-for-token identical
rendered toolbars.

**Deliverables.**
- `app/Domains/Editor/Private/Support/ToolbarPresets.php` — the §4.2 mapping:
  - `default` → `bold, italic, underline, strike, blockquote, align, list, custom-emoji`
  - `links` → `default` + `link`
  - `editorial` → `default` + `header` + `link` (order must match today's literals)
  - `narrative` → `default` + `link` + `spoiler`
  - unknown name → `default`.
- `rich-text.blade.php` — the `toolbar` prop accepts a string (resolved through
  `ToolbarPresets`) or an array (used as-is, bypassing presets). Default unchanged.
- Call sites switched from `:toolbar="[…]"` to `toolbar="<preset>"` — **only where
  the token list matches a preset exactly**, verified literal by literal:
  - `Calendar/…/activities/_form.blade.php` → `links`
  - `FAQ/…/faq-questions/_form.blade.php` → `editorial`
  - `News/…/news/_form.blade.php` → `editorial`
  - `StaticPage/…/admin/_form.blade.php` → (inventory first, Open item O3)
  - `Story/…/chapters/partials/form.blade.php` author note → `narrative`,
    chapter content → `links`
  - Any literal that does not match a preset token-for-token **stays a literal**
    and is reported, not bent into a preset.
- `app/Domains/Editor/README.md` — preset table.

**Tests.**
- `app/Domains/Editor/Tests/Feature/EditorToolbarPresetTest.php` — new, *the red
  test*. For each of the four preset names, render
  `<x-editor::rich-text name="x" id="x" toolbar="<name>" />` and assert the
  `data-toolbar` JSON equals the exact ordered token list of §4.2. Plus: an
  explicit `:toolbar="['bold']"` array renders exactly `["bold"]`; an unknown
  name (`toolbar="nope"`) renders the `default` list; the `link` and `spoiler`
  branches (`data-link-*`, `data-spoiler-label`) still fire for `links` /
  `narrative`.
- A pre/post diff check by hand at each converted call site: the rendered
  `data-toolbar` string must be unchanged. Where a consumer page test already
  asserts editor markup, it must stay green untouched.

**Acceptance.**
- ✅ Each of the four presets renders the exact token list of architecture §4.2,
  in order.
- ✅ Every converted call site renders a `data-toolbar` value identical to the one
  it rendered before the conversion.
- ✅ A call site whose literal does not match any preset is left alone and named in
  the phase report.
- ✅ `:toolbar="[…]"` still bypasses presets entirely.
- ✅ No preset is named after a consumer domain.
- ✅ `npm run gate` green.

---

## Phase 4 — JS bundle move, self-loading components, 15 `@vite` deletions

**Goal.** Move `editor-bundle.js` and `quill-emoji/` into Editor, make the
components push their own `@vite` once, and delete the 15 hand-written lines.

**⚠️ Build ordering (risk R4, verified).** `scripts/gate.js` runs its steps in the
order `docs → deptrac → php → js → build` — the asset build is **last**. Renaming
the Vite entries invalidates the gitignored `public/build/manifest.json`, so every
test that renders `@vite` fails on a missing manifest key. In this phase, run
`npm run build` (or `npm run gate -- --only=build`) **immediately after moving the
asset and editing `vite.config.js`, before running any PHP test**, then run the
full `npm run gate`. Same applies to phase 5.

**Deliverables.**
- Moved: `app/Domains/Shared/Resources/js/editor-bundle.js` →
  `app/Domains/Editor/Private/Resources/js/editor-bundle.js`, and
  `Shared/Resources/js/quill-emoji/` → `Editor/Private/Resources/js/quill-emoji/`.
  **Byte-identical** apart from relative import paths that the move forces.
- `vite.config.js` — the `app/Domains/Shared/Resources/js/editor-bundle.js` input
  is replaced by `app/Domains/Editor/Private/Resources/js/editor-bundle.js`.
- `app/Domains/Editor/Private/Resources/views/components/_assets.blade.php` — new.
  One `@once` wrapping one `@push('scripts')` with the `@vite([...])` call. Both
  `rich-text.blade.php` and `multi.blade.php` `@include` it (architecture §4.3 —
  a per-component `@once` would push twice on a page mixing the two).
- The 15 `@vite('…editor-bundle.js')` lines deleted:
  `Calendar/…/secret-gift.blade.php`, `Calendar/…/activities/{create,edit}`,
  `Comment/…/comment-list.blade.php`, `FAQ/…/faq-questions/{create,edit}`,
  `News/…/news/{create,edit}`, `Profile/…/pages/edit.blade.php`,
  `StaticPage/…/admin/{create,edit}`, `Story/…/chapters/{create,edit}`,
  `Story/…/{create,edit}.blade.php`.
- `app/Domains/Shared/{README,AGENTS}.md` — the `editor-bundle.js` /
  `initQuillEditor` / Quill sections removed; the corresponding content lands in
  `app/Domains/Editor/AGENTS.md` (idempotent `initQuillEditor`, images blocked at
  the Quill level).

**Tests.**
- `app/Domains/Editor/Tests/Feature/EditorAssetsTest.php` — new, *the red test*
  (tradeoff #3). Three cases:
  1. `<x-editor::rich-text …>` alone emits the `editor-bundle.js` Vite tag; same
     for `<x-editor::multi …>`.
  2. A view rendering both components emits that tag **exactly once**
     (assert the substring count, not just presence).
  3. A view rendering no editor emits **neither** tag.
- Untouched and green: the consumer page tests for all 15 files above. A page that
  lost its `@vite` but whose editor still renders must still carry the tag — this
  is what proves the deletion was safe for the pages a test covers.

**Acceptance.**
- ✅ `grep -rn "editor-bundle" app/Domains --include=*.blade.php` returns only
  `Editor/…/_assets.blade.php`.
- ✅ `grep -rn "editor-bundle\|quill-emoji" app/Domains/Shared` returns nothing.
- ✅ `npm run build` succeeds and `public/build/manifest.json` contains the
  `app/Domains/Editor/Private/Resources/js/editor-bundle.js` key and no longer
  contains the Shared one.
- ✅ A page rendering two editors emits the script tag once.
- ✅ A page rendering no editor emits no editor script tag — including
  `secret-gift.blade.php` when it is in a phase with no editor (decision #8).
- ✅ `npm run gate` green **after** a fresh `npm run build`.

---

## Phase 5 — CSS split into its own Vite entry

**Goal.** Move the chrome rules out of `Shared/Resources/css/app.css` into a
second Editor Vite entry pushed by the same `_assets` partial, leaving read-side
rules in Shared.

Kept separate from phase 4 deliberately: phase 4's failure mode is a missing
script (caught by a test), phase 5's is a silently unstyled read-only page
(risk R2, caught only by eyes). Isolating them keeps the bisect honest.

**Deliverables.**
- `app/Domains/Editor/Private/Resources/css/editor.css` — new. Moves, per
  architecture §4.4 (line numbers in `Shared/Resources/css/app.css` today):
  - `.ql-snow .ql-tooltip[data-label-*]` rules (≈275–293)
  - `.rich-content .ql-editor…` rules (≈320–333)
  - `.rich-content .ql-indent .ql-toolbar` (≈337)
  - `.ql-editor .ql-spoiler` (≈343)
  - `.ql-toolbar button.ql-spoiler`, `.ql-snow .ql-toolbar button.ql-spoiler svg`,
    `… .ql-active` (≈349–363)
- `app/Domains/Shared/Resources/css/app.css` — keeps `.ql-align-*`,
  `p.ql-align-center`, `.rich-content` and its descendants, `.rich-content
  .ql-indent p`, `.ql-spoiler:not(.ql-editor .ql-spoiler)` and
  `.ql-spoiler--revealed:not(…)`, and the `.ql-custom-emoji*` family (Open item
  O4). Rule of thumb, applied rule by rule: **if a page that never loads the
  editor needs it, it stays in Shared.**
- `vite.config.js` — add `app/Domains/Editor/Private/Resources/css/editor.css` as
  a second input.
- `_assets.blade.php` — the `@vite([...])` array now lists both entries, CSS first.
- `app/Domains/Editor/README.md` — the asset contract (two entries, self-pushed)
  and the chrome/read-side boundary.

**Tests.**
- `app/Domains/Editor/Tests/Feature/EditorAssetsTest.php` — extended, *the red
  part*: the three existing cases now assert **both** tags (CSS and JS), and the
  "exactly once" case asserts each of the two tags appears exactly once on a page
  rendering both components.
- No automated test can prove a CSS rule landed on the right side of the split.
  A grep-based assertion on `app.css` was considered and rejected as brittle and
  low-value; the split is a VERIFY item (rows 1–3 of the checklist), which is why
  this phase must run its own `npm run build` before the gate.

**Acceptance.**
- ✅ `grep -n "ql-toolbar\|ql-tooltip\|ql-editor" app/Domains/Shared/Resources/css/app.css`
  returns only the read-side exceptions explicitly listed above, each with a
  comment saying why it stays.
- ✅ `public/build/manifest.json` contains the `editor.css` entry.
- ✅ A page with an editor emits both tags; a page without emits neither.
- ✅ Visual QA rows 1–3 pass before the phase is called done.
- ✅ `npm run gate` green **after** a fresh `npm run build`.

---

## Phase 6 — Domain documentation

**Goal.** Leave `Editor` documented to the standard of `Media`, and `Shared` with
no dangling editor references.

**Deliverables.**
- `app/Domains/Editor/README.md` — consolidated: purpose, "owns no table", the
  two components and their props, the block schema contract (§3.1), the toolbar
  preset table, `EditorPublicApi`, the asset contract, and the chrome/read-side
  CSS boundary with its rule of thumb.
- `app/Domains/Editor/AGENTS.md` — the operational notes: `initQuillEditor` is
  idempotent and keyed by container id; Quill drops pasted/dropped images by
  design; components self-load their assets, so never hand-write `@vite` for them;
  **the fragment caveat (risk R1)** — a `@push` inside an AJAX-rendered fragment is
  discarded, so a domain that renders an editor *only* inside a fragment must push
  the assets from the page.
- `app/Domains/Shared/{README,AGENTS}.md` — final sweep for editor references;
  note explicitly that `Resources/js/anchoring/` stays in Shared and why (§8 of the
  functional spec), so the next reader does not "finish the job".
- Neither file may reference `docs/Feature_Planning` (enforced by the gate's docs
  step).

**Tests.** None — documentation. The gate's `docs` step (`scripts/check-docs.js`)
is the check: no `docs/Feature_Planning` reference, every relative link resolves.

**Acceptance.**
- ✅ `app/Domains/Editor/README.md` documents the block schema, the four presets
  and the two Vite entries.
- ✅ No `app/Domains/**/{README,AGENTS}.md` mentions the editor as living in Shared.
- ✅ The root `AGENTS.md` Domain Registry lists `Editor` with "owns no table".
- ✅ `npm run gate` green.

---

## Visual QA checklist

Filled by VERIFY. Runs after phase 5 at the earliest (rows 1–3 gate phase 5
itself). Every row's expected result is **"identical to before the refactor"** —
compare against `main` where in doubt.

| Surface | Check | OK? |
|---------|-------|-----|
| Published chapter (read-only) | No editor bundle and no `editor.css` in the network tab; centred/right/justified paragraphs still aligned, indented paragraphs still indented, spoilers still hidden and revealing on click | |
| Published news article with image blocks (read-only) | No editor assets loaded; `ce-block--text` / `ce-block--image` spacing unchanged; images responsive; captions styled | |
| Published static page + a profile page (read-only) | No editor assets loaded; `.rich-content` typography, lists, links and emoji (`.ql-custom-emoji*`) render as before | |
| Comment list loaded via AJAX, then **edit** a comment (risk R1) | The injected fragment's editor initialises, toolbar styled, save works — proves the page-level reply-form editor still loads the assets for later fragments | |
| Comment list loaded via AJAX, then **reply** to a comment (risk R1) | Same, on the reply form inside the fragment | |
| Page with two editors (comment list: reply form + an open edit form) | Both editors work; `editor-bundle.js` and `editor.css` each appear **once** in the network tab | |
| SecretGift page **with** an editor (gift preparation phase) | Editor renders, assets load, toolbar styled, submit works | |
| SecretGift page **without** an editor (any other phase) | Page renders correctly, **no** editor asset loaded, no JS console error | |
| Calendar — admin activity create + edit | `links` toolbar token-for-token as before (link button present, no header, no spoiler); link tooltip labels in French | |
| Comment — new comment form on a chapter | `default` toolbar; min-characters counter and its French pluralisation; submit blocked under the minimum | |
| FAQ — admin question create + edit | `editorial` toolbar (header + link); answer saves and renders | |
| Message — compose | Editor renders through the prefixed component (this call site used the removed unprefixed `<x-editor>`); message sends | |
| News — admin create + edit, simple mode | Single editor, `editorial` toolbar, content saves; min/max validation still fires | |
| News — admin create + edit, advanced (multi) mode | Add/reorder/delete text and image blocks; Media picker opens; saved article renders identically | |
| Profile — edit | `default` toolbar, 1000-char counter, 10-line height | |
| StaticPage — admin create + edit | Toolbar unchanged from before; page saves and renders | |
| Story — create + edit (description) | 100/1000 min-max counter, submit blocked under 100 | |
| Story — chapter create + edit | Author note = `narrative` (spoiler button present and inserting a spoiler); content = `links` with `indentParagraphs` still indenting; resizable editor still resizes | |
| Mobile 375px — one editing page (chapter edit) | Toolbar wraps as before, editor usable, counter visible | |
| Dark mode / theme — one editing page | Toolbar, tooltip and editor surface colours unchanged | |

## Open items

Each must be resolved before the phase named.

| # | Item | Needed by |
|---|------|-----------|
| O1 | Call-site inventory confirmed by grep: **13 occurrences in 11 files** (`comment-item.blade.php` and `chapters/partials/form.blade.php` hold two each), plus one internal use inside `multi-editor.blade.php` itself. Matches architecture §4.3's "11 Blade files". Re-run the grep before editing in case a task merged meanwhile | Phase 2 |
| O2 | **`Shared → MediaPublic` looks already vacuous.** `grep -rn "Media" app/Domains/Shared --include=*.php` returns only two Blade *comments* — no PHP in Shared references Media today, so deptrac was never enforcing that edge and removing the allowance proves nothing by itself. It is still correct to remove it in phase 2 (it stops silently pre-approving a future PHP edge), but the plan should not claim the gate verifies the extraction. I could not run deptrac locally (host PHP 8.3, project needs 8.4 — needs `sail`). Confirm with `./vendor/bin/sail composer deptrac` before and after | Phase 2 |
| O3 | Toolbar literal inventory. Verified: FAQ and News = `bold,italic,underline,strike,header,blockquote,align,list,custom-emoji,link` (→ `editorial`); Calendar and Story chapter *content* = the same minus `header` (→ `links`); Story author note = that plus `spoiler` (→ `narrative`). **Note §4.2 lists `links` as Calendar-only — Story chapter content also maps to it.** `StaticPage/…/admin/_form.blade.php`, `Profile`, `Message`, `Comment` and SecretGift literals were not read; inventory all of them and only convert exact matches | Phase 3 |
| O4 | The `.ql-custom-emoji*` family (≈40 lines, `app.css` 383–428) is **not classified by architecture §4.4**. It styles emoji inside *stored* content, so by the §4.4 rule of thumb it stays in Shared — confirm by checking whether any of those rules is scoped under `.ql-toolbar` (the emoji *picker* would be chrome) | Phase 5 |
| O5 | `SharedServiceProvider.php:66` comments that anonymous components are registered "both unprefixed (`<x-editor>`) and prefixed". Determine whether the unprefixed registration serves other Shared components (e.g. `<x-input-error>`, used unprefixed in `chapters/partials/form.blade.php`). If it does, **keep it** and only remove the editor from the comment — do not delete a registration other components rely on | Phase 2 |
| O6 | Assumption **A3** ("`EditorPublic` granted to each of the nine consumer domains") is superseded by **decision #11** ("only where PHP references Editor"). `DECISIONS.md` records both without marking the supersession. The plan follows #11. Worth a superseding row in `DECISIONS.md` at WRAP | WRAP |
| O7 | `ContentBlocksRenderer` renders `<x-media::image>` through `Blade::render()` at runtime. Nothing about the move changes that, but the moved test is the only proof — confirm `ContentBlocksRendererTest`'s image cases pass unchanged after the namespace change rather than assuming | Phase 1 |
