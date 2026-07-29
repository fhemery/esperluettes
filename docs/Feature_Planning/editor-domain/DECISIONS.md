# Extract an Editor domain from Shared — decisions log

Append-only. Every question the user arbitrated, with the answer as given.
BUILD reads this before asking anything already settled.

Format: one row per decision. Never edit a row — if a decision is reversed, add
a new row that supersedes it and note the number.

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|
| 1 | 2026-07-29 | REFINE | Component naming under the new `editor` Blade namespace | `<x-editor::rich-text>` and `<x-editor::multi>` — rename rather than keep the `editor::editor` stutter | — |
| 2 | 2026-07-29 | REFINE | Keep an unprefixed `<x-editor>` alias for `Message/.../compose.blade.php`? | No alias. Register only the `editor::` namespace and fix the call site | — |
| 3 | 2026-07-29 | REFINE | Editor's PHP public surface, given `ContentBlocksRenderer` crosses a domain boundary | `EditorPublicApi` facade (`render` / `sanitizeText` / `plainTextLength`); the renderer becomes private | — |
| 4 | 2026-07-29 | REFINE | Type the block JSON schema now, given more block types are expected (link buttons, statistics)? | No DTOs. Decided by Claude at the user's request, with the user's note that clarity may be worth the tradeoff: typed DTOs would not remove the `if/elseif` on `type`; the seam that pays off is a **block-type registry**, which is a redesign belonging to the task that first needs a third type. Here: keep arrays, document the schema as Editor's contract, keep per-type dispatch in one place | — |
| 5 | 2026-07-29 | REFINE | What happens to the ~80 lines of Quill CSS in `Shared/Resources/css/app.css`? | Split: chrome rules (`.ql-toolbar`, `.ql-tooltip`, `.ql-editor`) move to Editor; read-side rules (`.ql-align-*`, `.rich-content`, spoiler) stay in Shared | — |
| 6 | 2026-07-29 | REFINE | Move `editor-bundle.js` and `quill-emoji/` with the domain? | Yes — move both, update the `vite.config.js` input path | — |
| 7 | 2026-07-29 | REFINE | Should consumer pages keep hand-writing `@vite` of the bundle path? | No. The Editor components push their own `@vite` inside `@once`; the 15 hand-written lines are deleted, not rewritten | — |
| 8 | 2026-07-29 | REFINE | SecretGift `@vite`s the bundle on a page whose editor lives in a partial — does deleting that line risk an editor-less page? | No risk. The page does not always have an editor; bundling the script with the component is the correct fix ("I am actually wondering why I did not do it myself") | — |
| 9 | 2026-07-29 | REFINE | Chrome CSS: separate Vite entry, or `import './editor.css'` inside `editor-bundle.js`? | A separate Vite entry — it makes the dependency explicit. Components push both entries | — |
| 10 | 2026-07-29 | REFINE | Does the toolbar token list become an Editor-published constant, or stay an array literal in component props? | It lives inside the Editor module: the renderer is Editor's, so the toolbar goes with it rather than being repeated at call sites | — |
| 11 | 2026-07-29 | DESIGN | deptrac only analyses PHP, so 10 of 11 consumers create no analysable edge. Grant `EditorPublic` to all nine domains anyway? | No — add it only where PHP references Editor (`NewsPrivate` today). An allowance for an edge that cannot exist is config that lies and pre-approves unreviewed coupling. Consequence accepted: the request's "consumers become deptrac-enforceable" payoff is not obtainable; the real payoff is deleting `Shared → MediaPublic` | — |
| 12 | 2026-07-29 | DESIGN | How do the 11 call sites name a toolbar preset? | A string preset name (`toolbar="editorial"`) resolved inside the component, with `:toolbar="[…]"` still accepted. Not a PHP constants class — no FQCN in Blade for a list of strings | — |
| 13 | 2026-07-29 | DESIGN | Regression net for the 15 deleted `@vite` lines | A component-level asset test (tags emitted, emitted once, absent when no editor renders) plus browser coverage at VERIFY. Not an assertion added to every consumer domain's page tests | — |

## Assumptions made without asking

Used in `auto` mode, or when the user was unavailable. Each one is a decision
the user may want to reverse — surface these in the WRAP summary.

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| A1 | Component **props** are unchanged — same names, same defaults, same toolbar token list. Only the component's name and namespace change | REFINE | Yes, cheaply |
| A2 | Translation namespace becomes `editor::` (`editor::rich-text.*` / `editor::multi.*` naming left to DESIGN); no string is reworded | REFINE | Yes |
| A3 | ~~`EditorPublic` is granted explicitly to each of the nine consumer domains in `deptrac.yaml`~~ — **superseded by decision #11**: deptrac analyses PHP only, so eight of those grants would be unenforceable config. Granted only where PHP references Editor | REFINE | Superseded |
| A4 | Editor follows the **Media** domain shape: no table, `Public/Api` + `Public/Providers/EditorServiceProvider`, components under `Private/Resources/views/components` | REFINE | Yes |
| A5 | The moved tests (`ContentBlocksRendererTest`, `MultiEditorComponentTest`) are moved and re-namespaced, not rewritten | REFINE | Yes |
| A6 | `EditorPublic`'s ruleset is `[Shared, EditorPrivate]`, not the `[Shared]` written in 02-architecture §5 / plan phase 1. `EditorPublicApi` injects the private renderer, exactly as `MediaPublic: [Shared, MediaPrivate]` — the documented `[Shared]` would have been a deptrac violation on the first commit | BUILD phase 1 | Yes |
| A7 | Phase 1 also fixed the two stale `ContentBlocksRenderer` mentions in `Media/README.md` and `Media/AGENTS.md` (rule 3: clean up the mess the move creates), rather than leaving them for phase 6 | BUILD phase 1 | Yes |
