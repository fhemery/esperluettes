# Extract an Editor domain from Shared — functional specification

> REFINE output. Describes **what** the feature does, never **how** it is built.
> Every statement here is either something the user confirmed or a stated
> assumption. No invented requirements.

## 1. Overview

The rich-text editor (Quill), the block editor and the block renderer move out
of `Shared` into a new `Editor` domain. This is a **refactor with no
user-visible change**: every page that edits or displays content must behave
byte-for-byte as it does today.

The value is architectural. `Shared` is the base layer every domain depends on,
and today it reaches *up* into a feature domain (`Shared → MediaPublic`) for one
reason only: the editor composes `<x-media::image-field>` and the renderer emits
`<x-media::image>`. After the move that edge becomes `EditorPrivate →
MediaPublic`, an ordinary domain-to-domain dependency, and the nine consumers of
the editor become explicit and deptrac-enforceable instead of implicitly-
everything via `Shared`.

The audience is developers. `chapters-multi-edit/` and `multiedit-static-pages/`
both add block logic; they should land in `Editor`, not grow `Shared` further.

## 2. Vocabulary

| Term | Meaning |
|------|---------|
| **Editor** | The new domain. Owns rich-text editing, block editing, and the rendering of stored blocks to HTML. No tables. |
| **rich-text** | The single-field Quill editor. Blade component `<x-editor::rich-text>` (today `<x-shared::editor>`). |
| **multi** | The block editor: an ordered stack of typed blocks with simple/advanced modes. Blade component `<x-editor::multi>` (today `<x-shared::multi-editor>`). |
| **block** | One entry of the advanced-mode document: `['type' => 'text', 'html' => …]` or `['type' => 'image', 'path' => …, 'alt' => …, 'caption' => …]`. |
| **block schema** | The shape above. It is Editor's data contract with its consumers, documented in the domain README. |
| **chrome CSS** | Styling of the editing UI (toolbar, tooltip, active editor area). Belongs to Editor. |
| **read-side CSS** | Styling of *stored* content on read-only pages (alignment classes, `.rich-content`, spoiler display). Belongs to Shared — every domain that renders saved text needs it, editor or not. |

No user-facing vocabulary is introduced: nothing in this task reaches an end
user.

## 3. Roles & visibility

N/A — no role, permission or visibility rule changes. No route, no controller,
no authorisation check is added, removed or altered. Every editing surface
remains available to exactly the roles that reach it today, because the pages
themselves are not touched beyond the component name and the removal of their
asset include.

## 4. Functional requirements

### 4.1 Behaviour preservation (the acceptance criterion)

1. Every page that today renders `<x-shared::editor>`, `<x-editor>` or
   `<x-shared::multi-editor>` renders the same editor, with the same toolbar,
   the same placeholder, the same character counter and the same min/max
   behaviour.
2. Every page that displays stored MultiEdit content produces the same HTML as
   before, including sanitisation (the `multiedit-text` Purifier profile) and
   the `ce-block` / `ce-block--text` / `ce-block--image` classes.
3. Character counting (`plainTextLength`) and text sanitisation
   (`sanitizeText`) return identical results, so News's min/max validation is
   unaffected.
4. All existing tests continue to pass, moved but not rewritten.

### 4.2 Developer-facing contract after the move

1. **Blade components.** `<x-editor::rich-text>` and `<x-editor::multi>`, with
   their current props unchanged. No unprefixed alias: `<x-editor>` stops
   working and `Message/.../compose.blade.php` is updated to the prefixed form.
2. **PHP.** One entry point, `EditorPublicApi`, exposing `render(array $blocks)`,
   `sanitizeText(string $html)` and `plainTextLength(array $blocks)`.
   `ContentBlocksRenderer` becomes an implementation detail of the domain.
   `News/Private/Services/NewsService.php` injects the API instead of the
   renderer.
3. **Assets.** The editor components load their own JS **and** their own chrome
   stylesheet, which is a separate Vite entry rather than a CSS import inside
   the JS bundle. Consumer pages delete their hand-written
   `@vite('…/editor-bundle.js')` line rather than rewriting its path; Editor's
   file layout stops being public knowledge. A page that renders no editor now
   loads neither asset.
4. **Toolbar.** The toolbar belongs to Editor. Five consumers today repeat a
   near-identical 9–11 token literal, differing only by `header`, `link` or
   `spoiler`; Editor publishes those token sets as named presets and consumers
   name one instead of restating the list. Passing an explicit token array
   stays possible — the presets are a default vocabulary, not a restriction,
   and the resulting toolbars must be token-for-token what they are today.
5. **Translations.** The `shared::editor.*` and `shared::multi-editor.*` keys
   become `editor::…`. Three test files outside the domain assert those keys and
   are updated with them.
6. **deptrac.** `Shared → MediaPublic` is removed. `EditorPublic` is added as an
   explicit dependency of the nine consumer domains; `EditorPrivate` depends on
   `MediaPublic` and `Shared`.

### 4.3 Scope of the change — the files this touches

| Surface | Count | Domains |
|---------|-------|---------|
| Component call sites | 11 Blade files | Calendar, Comment, FAQ, Message, News, Profile, StaticPage, Story |
| Hand-written `@vite` of the bundle | 15 Blade files | Calendar, Comment, FAQ, News, Profile, StaticPage, Story |
| PHP consumers | 1 (`NewsService`) | News |
| Tests asserting moved lang keys | 3 (Comment ×2, Story ×1) | Comment, Story |
| Files moved out of Shared | Blade components + partials, `ContentBlocksRenderer`, 2 lang files, 2 test files, `editor-bundle.js`, `quill-emoji/`, chrome CSS | Shared → Editor |

### 4.4 Edge path — content rendered in an AJAX fragment

`Comment` renders `comment::fragments.items` server-side into an AJAX response,
and that fragment contains editors (the edit and reply forms in
`comment-item.blade.php`). A Blade `@push` executed during a fragment render is
discarded — there is no layout to flush it into. This is already true today of
the editor's own init `@push`, and it works because `comment-list.blade.php`
renders an editor at page level, which loads the bundle for the whole page.

Requirement: after the move, comment editing and replying must still work.
Self-loading components must not regress this — the page-level editor in
`comment-list` must still cause the bundle to load for the fragments injected
later. This is an explicit VERIFY item, not an assumption.

## 5. Lifecycle

N/A — the Editor domain owns no table, no row and no user-owned data. Nothing
is created, deleted or cascaded. Deactivating or deleting a user has no effect
on this domain. Stored content continues to belong to the domains that already
own it (News, Story, StaticPage, …); their storage format is unchanged.

## 6. Cross-cutting concerns

| Concern | Decision |
|---------|----------|
| Roles | N/A — no permission surface changes (§3). |
| Visibility / privacy | N/A — no data, no visibility rule. |
| Settings | N/A — no new preference. The existing appearance settings stay registered by `Shared`. |
| Notifications | N/A — nothing happens that anyone is told about. |
| Domain events | N/A — no event emitted, none listened to. |
| Statistics | N/A — no counter. |
| Moderation | N/A — no reportable content is introduced. Sanitisation behaviour is preserved exactly (§4.1.2). |
| Lifecycle / cascade | N/A (§5). |
| Media | `EditorPrivate → MediaPublic` replaces `Shared → MediaPublic`. The image block keeps using `<x-media::image-field>` and `<x-media::image>` unchanged; image ownership, scopes and GC usage providers stay with the consuming domains. |
| Search | N/A — nothing indexed. |
| i18n | Lang files move as-is, namespace `shared::` → `editor::`. No string is added, removed or reworded. |
| Mobile | N/A — no markup change to the components themselves beyond the asset push. |
| Accessibility | N/A — no markup change; existing aria labels move with their files. |
| Architecture boundaries | The core of the task. A new domain is justified: the editor has a real public surface (component contract, toolbar token list, block schema, sanitisation rules) and a real dependency of its own. `deptrac.yaml` changes as described in §4.2.5. |
| UI surface | ~26 Blade files across nine domains, all mechanical (component prefix, component name, deleted `@vite` line). No layout, spacing or copy change anywhere. |
| Performance | Unchanged. One caveat: pages that today load the bundle unconditionally will load it only when an editor component actually renders — strictly less work, never more. |
| Data & migration | N/A — no table, no migration. |

## 7. Decisions confirmed

| # | Question | Decision |
|---|----------|----------|
| 1 | Component naming under the new namespace | `<x-editor::rich-text>` and `<x-editor::multi>` — rename rather than carry the `editor::editor` stutter, since every call site is touched anyway |
| 2 | Keep an unprefixed `<x-editor>` alias? | No. Only the `editor::` namespace is registered; `Message/.../compose.blade.php` is fixed. An unprefixed global component is exactly the implicit coupling this task removes |
| 3 | Editor's PHP public surface | `EditorPublicApi` facade with `render` / `sanitizeText` / `plainTextLength`; `ContentBlocksRenderer` becomes private. Matches the one-API-per-domain convention (Media's sole entry point is `MediaPublicApi`) |
| 4 | Type the block schema now? | No. More block types are expected (link buttons, statistics), but typed DTOs would not help — `render()` would still branch on `type`. The seam that pays off is a **block-type registry**, and that is a redesign belonging to the task that first needs a third type. Here: keep arrays, document the schema as Editor's contract, and keep the per-type dispatch in one place so a registry can replace it without touching consumers |
| 5 | Quill CSS | Split. Chrome rules (`.ql-toolbar`, `.ql-tooltip`, `.ql-editor`) move to Editor; read-side rules (`.ql-align-*`, `.rich-content`, spoiler display) stay in Shared, because read-only pages in eight domains need them without depending on the editor |
| 6 | `editor-bundle.js` and `quill-emoji/` | Move to Editor with the domain |
| 7 | Asset loading | The Editor components push their own `@vite` inside `@once`. The 15 hand-written `@vite` lines are deleted, not rewritten (precedent: `Quote/.../mini-form.blade.php`) |
| 8 | Does deleting SecretGift's page-level `@vite` risk an editor-less page? | No. The page does not always show an editor, which is the reason to bundle the asset with the component rather than the page. Confirmed by the user as the intended behaviour |
| 9 | How is the chrome CSS emitted? | Its own Vite entry — explicit — rather than an `import './editor.css'` hidden inside `editor-bundle.js`. The components push both entries |
| 10 | Where does the toolbar token list live? | Inside Editor. The toolbar belongs with the editor that renders it, not with the call sites; Editor publishes it rather than each consumer repeating the literal |

## 8. Out of scope

- **Any change to what the editors do.** This is a move, not a redesign. No new
  prop, no new toolbar token, no new block type, no behaviour change.
- **A block-type registry** — see decision #4. Noted as the intended next step,
  deliberately not built here.
- **Typed block DTOs** (decision #4).
- **`Shared/Resources/js/anchoring/`** — canonical text, anchor extraction and
  re-anchoring are read-side concerns for quotes and annotations, not editing.
  Different consumers, different lifecycle. They stay in Shared.
- **`Shared → SettingsPublic` and `Shared → ConfigPublic`**, the other two edges
  out of Shared. Same smell, different features. Noted, not fixed here.
- **The `multiedit-text` Purifier profile** stays in `config/purifier.php` as
  global framework configuration.
- **Chapter-specific block logic** — that is `chapters-multi-edit/`, which this
  task must land before.

## 9. Open questions

All three questions raised at REFINE were resolved by the user on 2026-07-29
(decisions #8–#10). None remain, blocking or otherwise.

- **SecretGift** — resolved: the page does not always show an editor, which is
  precisely why bundling the asset with the component is correct. Deleting its
  page-level `@vite` is right, not risky: the bundle now loads exactly when an
  editor renders. Still a VERIFY item, no longer a design risk.
- **Chrome CSS** — resolved: its own Vite entry, for explicitness (#9).
- **Toolbar** — resolved: it belongs to Editor (#10).
