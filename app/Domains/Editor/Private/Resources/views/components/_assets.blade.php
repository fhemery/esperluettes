{{--
    Editor asset loader. Included by <x-editor::rich-text> and <x-editor::multi>
    so consumer pages never hand-write an @vite line for the editor.

    Two entries: the chrome stylesheet (toolbar, tooltip, .ql-editor surface)
    first, then the Quill bundle. Read-side rules stay in Shared's app.css so a
    page without an editor still renders stored content correctly.

    One @once for both components: @once is keyed by its own call site, so a
    per-component guard would push twice on a page mixing the two.

    Caveat: a @push executed while rendering an AJAX fragment is discarded — a
    page that renders an editor *only* inside a fragment must push the assets
    itself.
--}}
@once
  @push('scripts')
    @vite([
      'app/Domains/Editor/Private/Resources/css/editor.css',
      'app/Domains/Editor/Private/Resources/js/editor-bundle.js',
    ])
  @endpush
@endonce
