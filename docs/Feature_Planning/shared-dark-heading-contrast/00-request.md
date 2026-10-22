# Shared — dim accent headings in dark mode — request

*Written by the user. Free form, may be three lines. Everything below is
optional prompting, not a form to fill.*

## What I want

Leftover from `fanart-artwork-module` VERIFY (dark-mode row): the magenta
headings look dim on the near-black background. They are
`<x-shared::title>` (`app/Domains/Shared/Resources/views/components/title.blade.php`),
which always applies `text-accent`, so this is site-wide, not Fanart-specific.
Measure the contrast of `text-accent` on the dark background for each theme
and raise it to WCAG AA where it falls short.

## Why

Readability of every section heading in dark mode.

## Constraints or ideas I already have

- Sibling of `shared-button-contrast/` (light-theme buttons); the two may be
  done together.

## Explicitly out of scope

Redesigning the palette.
