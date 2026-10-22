# Fanart — lazy fragments inject a full page on redirect — request

*Written by the user. Free form, may be three lines. Everything below is
optional prompting, not a form to fill.*

## What I want

Leftover from `fanart-artwork-module` VERIFY. The Fanart UI loads HTML
fragments with `fetch()` and injects them (`x-html` / `innerHTML`): the
Fanarts tab (`story-tabs.blade.php`), each chapter group (`fragments/tab.blade.php`),
each profile-tab story (`components/profile-tab.blade.php`) and the viewer
modal (`js/fanart-viewer.js`). Their routes sit behind the `compliant`
middleware (and `auth`). For a user who has not accepted the CGU, `compliant`
answers a 302 to the CGU page; `fetch` follows it, gets a 200, and the
**whole CGU page is injected into the viewer modal** (or the tab). An expired
session would do the same with the login page.

Fix: treat `response.redirected` (or a non-fragment response) as a failure —
either navigate the window to `response.url`, or show the existing error
state. Same treatment in the four call sites.

## Why

A user who has not yet accepted new CGU sees a page-in-a-page instead of being
sent to the CGU.

## Constraints or ideas I already have

- If a shared fetch-fragment helper looks worth it, say so; otherwise keep the
  fix local to Fanart.

## Explicitly out of scope

Changing the `compliant` middleware itself.
