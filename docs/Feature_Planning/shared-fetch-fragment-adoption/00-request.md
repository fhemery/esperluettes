# Shared — adopt fetchFragment in other domains' fragment loaders — request

*Written by the user. Free form, may be three lines. Everything below is
optional prompting, not a form to fill.*

## What I want

Leftover from [`fanart-fragment-redirects`](../_done/fanart-fragment-redirects.md).
Shared now provides `window.fetchFragment` (`app/Domains/Shared/Resources/js/fragment-fetch.js`):
it navigates the window on a redirect (CGU), reloads the page on 401 / 419
(expired session) and returns `null` on any other failure, instead of letting
the caller inject a whole page into a fragment area.

Other domains still use a raw `fetch` + inject pattern and can hit the same
page-in-a-page bug. Candidates seen at WRAP (to confirm at REFINE):

- Comment lazy list (`Comment/Private/Resources/views/components/comment-list.blade.php`)
- Search dropdown (`Search/Private/Resources/views/components/header-search.blade.php`)
- Settings tabs (`Settings/Private/Resources/views/pages/index.blade.php`)
- Moderation (`Moderation/Private/Resources/js/moderation.js`, `pages/admin/user-management.blade.php`)
- Story admin moderation (`Story/Private/Resources/views/pages/admin/moderation/index.blade.php`)
- Profile user search (`Profile/Private/Resources/views/components/user-search-input.blade.php`)

Only HTML-fragment loaders qualify; JSON calls (Quote, Comment annotations API…)
do not.

## Why

Same failure mode as Fanart had: a user whose CGU were reset, or whose session
expired, gets the CGU / an error page injected inside a widget.

## Constraints or ideas I already have

- Callers treat `null` as "inject nothing".
- The 401 / 419 reload is only loop-safe where the widget is not rendered for
  guests; check each site.

## Explicitly out of scope

Changing the `compliant` middleware (see `auth-compliant-xhr-intended/`).
