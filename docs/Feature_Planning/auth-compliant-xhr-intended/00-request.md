# Auth — compliant middleware stores XHR fragment URLs as intended — request

*Written by the user. Free form, may be three lines. Everything below is
optional prompting, not a form to fill.*

## What I want

Leftover from [`fanart-fragment-redirects`](../_done/fanart-fragment-redirects.md).
`EnsureUserCompliance` (`app/Domains/Auth/Public/Middleware/EnsureUserCompliance.php`)
puts `$request->fullUrl()` into `url.intended` before redirecting to the CGU /
parental-authorization page. When the request that hits it is a script-loaded
HTML fragment (e.g. a Fanart tab, sent with `X-Requested-With: XMLHttpRequest`
by `window.fetchFragment`), the fragment URL becomes the intended destination:
after accepting the CGU the user lands on raw fragment HTML instead of the page
they were on.

Proposal: do not set `url.intended` for XHR requests (`$request->ajax()` /
`expectsJson()`); the post-CGU redirect then falls back to its default (or the
`Referer` could be used instead — to decide at REFINE).

## Why

A user who has to accept new CGU while browsing ends on an unstyled HTML
snippet after accepting.

## Constraints or ideas I already have

- The redirect itself must stay: `fetchFragment` relies on it to navigate the
  window to the CGU page.

## Explicitly out of scope

Client-side changes to `fetchFragment`.
