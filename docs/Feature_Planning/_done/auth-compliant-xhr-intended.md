# Auth — compliant middleware stores XHR fragment URLs as intended

**Status:** DONE — 2026-10-25 · **Domain(s):** `Auth`

## What it does

`EnsureUserCompliance` still redirects non-compliant users to the compliance
page for every request, but a script-loaded fragment request (`fetchFragment`,
`X-Requested-With: XMLHttpRequest`) no longer makes its own URL the
post-compliance destination (it rendered as raw HTML after accepting the CGU).
For such requests the same-host `Referer` (the page being viewed) is stored
instead.

## Key behaviour

- Non-XHR request: `url.intended = fullUrl()` (unchanged).
- XHR request: redirect to the compliance page is kept (`fetchFragment` relies on it); `url.intended` is never the XHR URL.
- XHR with a `Referer` whose host equals `$request->getHost()`: Referer stored. Foreign or missing Referer: nothing stored, an earlier `url.intended` is left untouched.
- Compliance routes (`compliance.*`) never store anything.
- After accepting terms / uploading the authorization: `url.intended`, else dashboard (existing behaviour).
- The Referer is client-supplied: the same-host check is what prevents an open redirect. Keep it.

## Where the code lives

| Concern | Path |
|---------|------|
| Middleware (`rememberIntended`, private) | `app/Domains/Auth/Public/Middleware/EnsureUserCompliance.php` |
| Tests | `app/Domains/Auth/Tests/Feature/ComplianceTest.php` |

## Decisions worth remembering

- Same-host Referer rather than a bare dashboard fallback: better UX for a few lines; drop the referer branch if Referer proves unreliable.
- XHR detected with `$request->ajax()` only (not `expectsJson()`), matching `fetchFragment`'s header.
- No client change to `fetchFragment`.

## Not done

- Non-goals: client-side changes to `fetchFragment`; other middleware and the login-intended flow.
- No e2e spec kept (verified once in a browser via a one-off driver flow: Fanarts tab as non-compliant user, accept CGU, back on the story page).
- No leftovers pushed to the backlog. Sibling: `shared-fetch-fragment-adoption/` (other domains adopting `fetchFragment`).
