---
name: verify-environment-gotchas
description: Before VERIFY in a browser, rebuild assets (gate skips vite when only Blade changed) and run one spec via `pnpm exec playwright test <path>`
metadata:
  type: feedback
---

Run `pnpm run build` before any browser verification of Blade/Tailwind work.

**Why:** `pnpm run gate` skips the vite build when no file under the asset
globs changed, but new Tailwind classes written only in Blade views
(`no-underline!`, `indent-0`) then never reach `public/build`. On
2026-09-27 the e2e run showed underlined, indented choice buttons purely from
a stale build — a false defect.

**How to apply:** rebuild first; if a style assertion fails, grep
`public/build/assets/app-*.css` for the class before reporting a defect.

Also: `pnpm run e2e -- <filter>` ran the whole suite here; use
`pnpm exec playwright test e2e/tests/features/<slug>.spec.ts` for one file.
Screenshots for the checklist: after `pnpm run e2e`, the e2e server on :8080
keeps the post-run data, so the run-app driver can be pointed at it with
`APP_BASE_URL=http://localhost:8080` and seeded accounts `<role>@e2e.test` /
`password` (CGU already accepted by auth.setup).
