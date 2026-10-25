# Prevent copying chapter text — architecture

- Functional spec: [`01-functional.md`](./01-functional.md)

## 1. Domain placement

Story domain, reader chapter view only. No other domain is touched.

## 2. Data model

N/A.

## 3. PHP architecture

`ChapterViewModel::$isAuthor` (already true for co-authors) decides whether the
guard is emitted. No new service, route, policy or event.

## 4. Frontend architecture

On the `<article data-quote-article>` of the chapter reader, when the viewer is
not an author, add Alpine listeners `@copy.prevent`, `@cut.prevent` and
`@dragstart.prevent`. The article is already inside Alpine scopes, so no new JS
module and no new asset. `user-select` and `contextmenu` stay untouched so the
annotation and quote selection toolbars (document-level `mouseup` /
`touchend` / `selectionchange`) and mobile long-press keep working.

Known limit: Alpine must have initialised on the element; before that the
guard is absent. Acceptable for a deterrent.

## 5. Deptrac

No new edge.

## 6. Testing strategy

Feature tests on the chapter page: the guard attributes are present for a
reader, a guest and a moderator, absent for the author and a co-author. The
actual blocking of the browser event is checked in VERIFY (visual / e2e),
since tests do not run Alpine.

## 7. Tradeoffs locked

| # | Question | Options considered | Chosen | Why |
|---|----------|--------------------|--------|-----|
| 1 | Where to implement | Shared JS module + directive; inline Alpine listeners | Inline Alpine listeners | Three attributes, one call site; a module is speculative |
| 2 | Block `user-select` / `contextmenu` | yes; no | no | Would break annotations/quotes and mobile selection |
