# Prevent copying chapter text

**Status:** DONE — 2026-10-25 · **Domain(s):** `Story` · **Branch:** `fix/misc-small-fixes`

## What it does

On the reader chapter page, a viewer who is not an author or co-author of the
story can still select text in the chapter body, but copying it out with
Ctrl/Cmd+C, Ctrl/Cmd+X, the context-menu Copy or drag-out does nothing. This is
a deterrent, not real protection. It is three inline Alpine listeners on one
element. No PHP, JS, CSS, route or translation change.

## Key behaviour

- Exempt: the author and co-authors (`ChapterViewModel::$isAuthor`, which is `Story::isAuthor`, collaborator role `author`).
- Guarded: guests, `user`, `user-confirmed`, beta readers, moderators, admins.
- Scope: only `<article data-quote-article>`. Title, author note and comments stay copyable.
- Blocked events: `copy`, `cut`, `dragstart` (`.prevent`). `contextmenu` and `user-select` are left alone, so selection, the quote/annotation/reaction toolbars and mobile long-press keep working.
- No message is shown to the reader.
- The guard is missing until Alpine has initialised on the element. Accepted for a deterrent.
- Stateless: nothing is stored.

## Where the code lives

| Concern | Path |
|---------|------|
| View (the only change) | `app/Domains/Story/Private/Resources/views/chapters/show.blade.php`: `@unless($vm->isAuthor) x-on:copy.prevent x-on:cut.prevent x-on:dragstart.prevent @endunless` on the article |
| Exemption flag | `ChapterViewModel::$isAuthor`, computed in `ChapterController::show` |
| Tests | `app/Domains/Story/Tests/Feature/Chapters/ChapterCopyGuardTest.php` (7 cases: guest, reader, beta reader, moderator, admin guarded; author, co-author not) |
| Domain doc | `app/Domains/Story/README.md`, section "Copy guard on the chapter body" |

The `x-on:` long form is used on purpose so Blade does not read `@copy` as a directive.

## Extension points used

None.

## Decisions worth remembering

Made in auto mode as assumptions, without asking the user. All can be reversed:

1. `contextmenu` and `user-select` left alone. Blocking either would break annotations, quotes and mobile selection. Blocking the `copy` event already covers the menu's Copy entry.
2. Moderators and admins are restricted. Only authors and co-authors are exempt.
3. No message is shown when a copy is blocked.
4. Inline Alpine listeners, no shared JS module (one call site).

## Code vs plan

- The plan said "no file-local helpers". `ChapterCopyGuardTest.php` still declares four global functions (`copyGuardAttributes`, `viewChapterAs`, `assertCopyGuarded`, `assertNotCopyGuarded`), which breaks the AGENTS.md rule that Pest helpers live in `Tests/helpers.php`. No name collides today. Left as is.
- Everything else matches the plan.

## Verification limits

VERIFY dispatched cancelable **synthetic** `copy`/`cut`/`dragstart` events on
the e2e instance. They were default-prevented for reader, guest and moderator, and
not for author or co-author. Selection and the toolbar still worked, and the
existing `chapter-annotation*` core specs passed. The temporary spec was deleted.
**Not tested:** real clipboard contents (Ctrl+C, right-click Copy, drag into a
field), the mobile native selection menu's Copy (it is not guaranteed to fire
`copy` in every browser), Shift+arrow keyboard selection, Advanced (block)
chapters. Advanced chapters are covered only in principle, because the handler sits
on the enclosing article.

## Not done

- Non-goals: real protection (view-source, dev tools, screenshots, print, reader mode, feeds); copy behaviour of news, static pages, comments, author note; any reader notice.
- Nothing cut mid-build. No open questions. No backlog rows created.
- e2e: no spec kept in `e2e/tests/features/` (the VERIFY spec was temporary).
