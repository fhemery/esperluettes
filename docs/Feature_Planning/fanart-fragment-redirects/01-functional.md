# Fanart — lazy fragments inject a full page on redirect — functional specification

## 1. Overview

The Fanart UI lazily loads HTML fragments (Fanarts tab, chapter groups, profile-tab stories, viewer modal). When the server answers such a request with a redirect (CGU not accepted, expired session), the browser follows it and the whole destination page gets injected into the fragment area. Instead, the reader must be taken to that page, as a normal navigation would.

## 2. Vocabulary

| Term | Meaning |
|------|---------|
| Fragment | HTML snippet fetched by script and injected in the page |

## 3. Roles & visibility

Concerns any user whose request to a Fanart fragment route is redirected by `auth` or `compliant` (no accepted CGU, parental consent pending, expired session). No change to who may see what.

## 4. Functional requirements

### 4.1 Redirected fragment request
1. A fragment request in the four Fanart places is answered with a redirect.
2. The browser window navigates to the redirect's destination (CGU / login page). Nothing is injected.
3. No half-loaded state is left behind in the tab / group / modal.

### 4.2 Other failures
- A non-successful answer (403, 404, 5xx) or a network error injects nothing: tab and profile groups stay empty as today, the viewer modal closes as today. The chapter group, which currently injects any body, is aligned on the same rule.

### 4.3 Normal case
Unchanged.

## 5. Lifecycle

N/A.

## 6. Cross-cutting concerns

| Concern | Decision |
|---------|----------|
| Roles | see §3 |
| Settings / Notifications / Events / Statistics / Moderation / Media / Search | N/A |
| i18n | no new text (no error message shown) |
| Mobile / Accessibility | unchanged |
| Other concerns | N/A |

## 7. Decisions confirmed

None beyond the request (navigate to the redirect destination or show the error state; fix local to Fanart unless a shared helper is worth it).

## 8. Out of scope

- Changing the `compliant` middleware, including the fact that it stores the fragment URL as the post-CGU « intended » destination (reported as a follow-up).
- The same pattern in other domains (Comment, Search, Settings, Moderation…): reported, not fixed.

## 9. Open questions

- After accepting the CGU, the user is sent back to the *fragment URL* (raw HTML), because the middleware records it as intended. That needs a middleware change (out of scope). Non-blocking, to be surfaced in the wrap-up.
