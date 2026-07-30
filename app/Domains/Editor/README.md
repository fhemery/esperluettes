# Editor Domain

## Purpose and scope

The Editor domain owns **rich-text and block-based content authoring**: the
rendering of block documents to sanitized HTML, and (progressively) the Blade
components, translations and assets that produce those documents.

It owns **no database table**. Content belongs to the domain that stores it
(News, Story, FAQ…); Editor only knows how a block document is shaped and how it
becomes HTML.

> Extraction in progress: today the domain holds the block renderer, its public
> API, the two authoring components, their translations and the Quill bundle
> (`Private/Resources/js/editor-bundle.js`, pushed by the components themselves).
> The editor CSS still lives in `Shared/Resources/css/app.css` — it moves here in
> a later step.

## Block schema

A block document is an **ordered array of plain arrays** — deliberately untyped,
so a new block type costs one branch and no DTO. Two types exist:

```php
['type' => 'text',  'html' => '<p>…</p>']

['type'  => 'image',
 'path'  => 'news/x.jpg',   // Media storage path
 'alt'   => '…',
 'caption'       => '…',    // optional
 'keep_original' => bool]   // optional; true = no responsive variants
```

Rules:

- Order in the array is order on the page.
- Text HTML is sanitized with the `multiedit-text` Purifier profile — `<img>` is
  stripped, so images only ever come from image blocks.
- A text block that sanitizes to an empty string is skipped; an image block
  without a `path` is skipped.
- `path` is a **Media** path. Editor never uploads or deletes files — the storing
  domain calls `MediaPublicApi` and registers its `MediaUsageProvider`.

Rendered output wraps each block in `<div class="ce-block ce-block--text">` or,
for images, a `<x-media::image>` carrying `class="ce-block ce-block--image"`.

## Public API

`EditorPublicApi` is the only entry point other domains use:

| Method | Purpose |
|--------|---------|
| `render(array $blocks): string` | Block document → sanitized HTML |
| `sanitizeText(string $html): string` | One text block's HTML through the `multiedit-text` profile |
| `plainTextLength(array $blocks): int` | Character count across **text** blocks only, for min/max validation |

It is a concrete class resolved from the container (no interface, no binding) —
inject it by type, as `NewsService` does.

## Blade components

Two anonymous components, registered by `EditorServiceProvider` under the
`editor` namespace. **Only the prefixed form exists** — there is deliberately no
unprefixed `<x-editor>` alias.

### `<x-editor::rich-text>`

A single Quill field: a hidden `textarea[name]` the form submits, plus a counter.

| Prop | Default | Purpose |
|------|---------|---------|
| `name` | *required* | Submitted field name |
| `id` | *required* | DOM id; also keys `quill-editor-area-{id}` and `quill-counter-{id}` |
| `defaultValue` | `''` | Initial HTML (`old()` wins) |
| `min` / `max` | `null` | Character bounds, shown in the counter and enforced client-side |
| `nbLines` | `5` | Height in lines |
| `placeholder` | `''` | Placeholder text |
| `isMandatory` | `false` | Marks the field required client-side |
| `indentParagraphs` | `false` | Adds `ql-indent` to the surface |
| `resizable` | `true` | Vertically resizable |
| `toolbar` | `'default'` | Preset name (see below) or an explicit token array |

### Toolbar presets

Both components take `toolbar` as a **preset name** resolved by
`Private/Support/ToolbarPresets`:

| Preset | Tokens |
|--------|--------|
| `default` | `bold, italic, underline, strike, blockquote, align, list, custom-emoji` |
| `links` | `default` + `link` |
| `editorial` | `bold, italic, underline, strike, header, blockquote, align, list, custom-emoji, link` |
| `narrative` | `default` + `link` + `spoiler` |

Presets are named after the **capability** they add, never after the domain that
uses them — Editor does not encode who its consumers are. An unknown name falls
back to `default`. `:toolbar="['bold', …]"` still passes an explicit token list
and bypasses presets entirely.

The `link` and `spoiler` tokens are not decoration: they switch on extra
component wiring (`data-link-*`, `data-spoiler-label`).

### `<x-editor::multi>`

The opt-in block editor: a mode toggle between one `rich-text` field (*simple*)
and an ordered stack of text/image blocks (*advanced*) serialized as
`name[uid][…]` alongside `mode` and `name_order`. Image blocks compose
`<x-media::image-field>`; the server branches on `mode` (see `NewsService`).
Props are documented in the component's own header comment.

Translations live in `Private/Resources/lang/fr/` under the `editor::` namespace:
`editor::rich-text.*` and `editor::multi.*`.

## What this domain does not do

- No routes, no controllers, no policies, no events.
- No image storage or deletion — that is Media's.
- No persistence of documents — the consuming domain owns the column.
