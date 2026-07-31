# Extract an Editor domain from Shared

> WRAP output — the compact record. **This is the only file in the folder an
> agent should load by default.** `01`–`03` remain as history.

**Status:** DONE — 2026-07-30 (VERIFY skipped, see *Not done*) ·
**Domain(s):** `Editor` (new), `Shared`, `Media`, + 11 consumer call sites ·
**Spec:** [functional](./01-functional.md) · [architecture](./02-architecture.md) ·
[plan](./03-plan.md) · [decisions](./DECISIONS.md)

## What it does

A pure move: everything about *authoring* rich text left `Shared` and became the
`Editor` domain — the two Blade components, their French lang files, the Quill
bundle and the editor chrome CSS, and `ContentBlocksRenderer` (now private,
reached through `EditorPublicApi`). No behaviour, prop or block-type change.
`<x-shared::editor>` / `<x-shared::multi-editor>` are now
`<x-editor::rich-text>` / `<x-editor::multi>`, and the 15 hand-written `@vite`
lines at the call sites are gone: the components load their own assets. The
motive was the `Shared → MediaPublic` deptrac edge, which is now deleted.

**The live documentation is the domain's own:**
[`app/Domains/Editor/README.md`](../../../app/Domains/Editor/README.md) (block
schema, props, presets, assets) and
[`AGENTS.md`](../../../app/Domains/Editor/AGENTS.md) (invariants). Read those
before this file; nothing below is repeated there.

## Key behaviour

- Editor owns **no table** and no route. Consumers store the content; Editor
  only shapes and renders it.
- Two entry points only: `EditorPublicApi` (PHP) and `<x-editor::…>` (Blade).
  There is deliberately **no unprefixed `<x-editor>`** alias any more.
- `toolbar` takes a **preset name** (`default` / `links` / `editorial` /
  `narrative`); an unknown name falls back to `default` silently. An explicit
  array still bypasses presets.
- Assets: the components `@include('editor::components._assets')`, one shared
  `@once` pushing **two** Vite entries (CSS then JS). A page with no editor
  loads nothing. A `@push` inside an AJAX fragment is discarded — Comment's
  fragments work only because the page renders an editor first.
- CSS is split by *who needs it*: chrome in `Editor/…/css/editor.css`, anything
  present in **stored** HTML (`.rich-content`, `.ql-align-*`,
  `.ql-custom-emoji*`, read-only spoiler) stays in `Shared/…/app.css`.
  Misfiling a rule breaks a read-only page with no test failure.
- `EditorServiceProvider` is registered **after** Media in
  `bootstrap/providers.php` — the image block composes `<x-media::image-field>`.

## Where the code lives

| Concern | Path |
|---------|------|
| Public API | `app/Domains/Editor/Public/Api/EditorPublicApi.php` |
| Provider | `app/Domains/Editor/Public/Providers/EditorServiceProvider.php` |
| Renderer / presets | `app/Domains/Editor/Private/Support/{ContentBlocksRenderer,ToolbarPresets}.php` |
| Components | `app/Domains/Editor/Private/Resources/views/components/` (`rich-text`, `multi`, `_assets`, `multi/_*`) |
| Lang (`editor::`) | `app/Domains/Editor/Private/Resources/lang/fr/{rich-text,multi}.php` |
| JS / CSS entries | `app/Domains/Editor/Private/Resources/{js/editor-bundle.js,css/editor.css}`, declared in `vite.config.js` |
| Tests | `app/Domains/Editor/Tests/Feature/` (6 files: renderer, public API, both components, presets, assets) |
| Deptrac | `deptrac.yaml` — layers `EditorPublic/Private/Tests`; `Shared` lost `MediaPublic` |
| Migrations | *(none — no table)* |

## Extension points used

None. Editor registers nothing in any registry and plugs into no host — it is
consumed, not consuming. Its only framework hooks are the `editor` Blade
component namespace, the `editor` view/lang namespaces and two Vite entries.

## Decisions worth remembering

- **#3 / #4** — `EditorPublicApi` is the PHP surface; blocks stay plain arrays.
  Typed DTOs were rejected: the seam that pays off is a **block-type registry**,
  to be built the day a third block type appears, not before.
- **#11** — `EditorPublic` is granted only where PHP references Editor
  (`NewsPrivate` today), not to all nine Blade-only consumers. Deptrac analyses
  PHP only, so those grants would be config that lies. **Consequence: the
  request's "consumers become deptrac-enforceable" payoff is not obtainable.**
- **#12** — presets are named after the capability they add, never after the
  consumer; resolved inside the component via `@use()`, no FQCN in Blade.
- **#5 / #7 / #9** — CSS split by read-side vs chrome; components self-load;
  chrome CSS is its own Vite entry rather than an import inside the JS bundle.
- **A6 (plan drift)** — `02-architecture.md` §5 and plan phase 1 write
  `EditorPublic: [Shared]`. The shipped ruleset is `[Shared, EditorPrivate]`;
  `[Shared]` would have failed deptrac immediately, since the API injects the
  private renderer.
- **A11/A12 (plan drift)** — the plan's phase 3 deliverables name only
  `rich-text`; `multi` resolves presets too (its own converted call site, News,
  is a `multi`), and both components' `toolbar` **default is now the string
  `'default'`**, not an inline array. Rendered output is unchanged.
- **O2** — `Shared → MediaPublic` was already vacuous (no PHP in Shared touched
  Media). Removing it stops pre-approving a future edge; it does not *prove* the
  extraction. Do not cite the green gate as evidence that it did.

## Off-plan side deliverable

`app/Domains/Shared/Tests/Unit/TranslationKeysExistTest.php` (commit
`d6e84626`) was not in the plan. `.env.testing` runs `APP_LOCALE=zz`, so
key-level assertions cannot tell a registered lang namespace from an
unregistered one — without it the `editor::` registration was untested. It scans
static `ns::` keys under `app/Domains` + `resources/views` against `fr` and
found 7 pre-existing defects (Notification, Comment ×3, SecretGift), fixed in
the same commit. Concatenated keys and JS-built keys stay uncovered.

## Not done

- **Non-goals (§8):** any redesign of the editors; the block-type registry;
  typed block DTOs; moving `Shared/Resources/js/anchoring/` (read-side, stays);
  the `Shared → SettingsPublic` / `ConfigPublic` edges; the `multiedit-text`
  Purifier profile (stays in `config/purifier.php`); chapter block logic
  (`chapters-multi-edit/`).
- **VERIFY was skipped by the user.** Four Playwright flows were written and run
  on 2026-07-30 — screenshots exist in the *gitignored*
  `.agents/skills/run-app/shots/` — but **no row of the Visual QA checklist in
  `03-plan.md` was ever filled and no report was written**. Every row 1–20
  therefore counts as unverified: read-only pages loading no editor asset,
  the AJAX-fragment risk (R1), the asset-once-per-page guarantee, all 11
  editing surfaces, mobile and dark mode. PHP + vitest are green and
  `EditorAssetsTest` covers the `@vite` deletions at Blade level, but nothing
  client-side has a recorded pass. **This is the residual risk of the task.**
  → pushed back as
  [`editor-domain-visual-qa/`](../editor-domain-visual-qa/00-request.md), which
  holds the four flows and their seed fixtures.
- The `storage/qa-seed-editor-domain*.php` files were never part of the app;
  they moved into that folder as `seed-*.php` and `storage/` is clean.
