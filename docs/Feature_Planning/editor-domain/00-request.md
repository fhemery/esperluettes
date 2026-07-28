# Extract an Editor domain from Shared — request

## What I want

Move the editors out of `Shared` into a new `Editor` domain: the Quill rich-text
component, the multi-editor, the block renderer and their assets.

## Why

`deptrac.yaml` says it plainly — `Shared` depends on `MediaPublic`:

```yaml
Shared:
  - SettingsPublic
  - ConfigPublic
  - MediaPublic
```

Shared is the base layer every domain depends on, and it reaches *up* into a
feature domain. That edge exists for exactly one reason: the multi-editor's
`_image-block.blade.php` composes `<x-media::image-field>`, and
`ContentBlocksRenderer` emits `<x-media::image>`.

When Media was built we considered putting the editor there and decided it was
not the right home. It is not Shared's either. The editor has a real public
surface — a component contract, a toolbar token list, a block JSON schema,
sanitisation rules — and a real dependency of its own. That is a domain.

Two payoffs:

- `Shared → MediaPublic` disappears, replaced by `EditorPrivate → MediaPublic`,
  an ordinary domain-to-domain edge.
- The seven consumers become explicit and deptrac-enforceable, instead of
  implicitly-everything via Shared.

## What moves

- `Shared/Resources/views/components/editor.blade.php` (Quill, with the
  `:toolbar` token contract) and `Shared/Resources/js/editor-bundle.js`
- `Shared/Resources/views/components/multi-editor.blade.php` and its
  `multi-editor/` partials (`_text-block`, `_image-block`,
  `_insert-affordance`)
- `Shared/Support/ContentBlocksRenderer.php`
- `Shared/Resources/lang/fr/editor.php`, `Shared/Resources/lang/fr/multi-editor.php`
- `Shared/Tests/Feature/{ContentBlocksRendererTest,MultiEditorComponentTest}.php`

## What does NOT move

`Shared/Resources/js/anchoring/` — canonical text, anchor extraction and
re-anchoring are **read-side** concerns for quotes and annotations, not editing.
Different consumers, different lifecycle. Leave them in Shared.

## Call sites

Ten Blade files across seven domains: Calendar, Comment, FAQ, News, Profile,
StaticPage, Story. The component prefix changes (`<x-shared::editor>` →
`<x-editor::…>`), so every call site is touched. Mechanical, but wide.

## Sequencing

**Do this before `chapters-multi-edit/`.** That task adds chapter-specific block
logic; it should land in Editor rather than growing Shared further and being
moved afterwards.

## Open questions for REFINE

- Component naming: `<x-editor::rich-text>` and `<x-editor::multi>`, or keep
  `editor` / `multi-editor`?
- Does the Editor domain need a `Public/Api` at all, or is the Blade component
  plus the renderer its whole public surface?
- Should the block JSON schema and its sanitisation rules become a public
  contract, given `chapters-multi-edit/` and `multiedit-static-pages/` will both
  consume them?

## Explicitly out of scope

- Any change to what the editors *do*. This is a move, not a redesign.
- `Shared → SettingsPublic` and `Shared → ConfigPublic`, the other two edges out
  of Shared. Same smell, different features; note them, do not fix them here.
