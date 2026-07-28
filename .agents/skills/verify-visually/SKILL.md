---
name: verify-visually
description: Drive the real app in a browser to verify a finished feature against its visual QA checklist. Use at the VERIFY step of the loop, or when asked to check that a change actually works in the UI rather than only in tests. Writes a Playwright flow, runs it per role, saves screenshots into the task folder and fills the checklist.
---

# Verify visually

Tests render Blade; they do not run Alpine. Anything client-side, any layout,
any "the tab disappeared for the wrong user" bug is only provable in a browser.
That is what this step is for.

The browser tooling is the [`run-app`](../run-app/SKILL.md) skill — **read it
before doing anything**, especially its Gotchas table. Do not write a new
driver.

## 1. Prepare

- Checklist: the "Visual QA checklist" table at the bottom of `03-plan.md`.
- Add any state named in §5 of `01-functional.md` that the checklist missed
  (deleted parent, deactivated user, stale data, empty state).
- `npm run browser:setup`, and `./vendor/bin/sail up -d` if the app is not up.
- Credentials: ask the user for a local account if `APP_USER` / `APP_PASSWORD`
  are not already set. Never commit them.
- If a checklist row needs data that does not exist locally (a story with 20
  quotes, a soft-deleted chapter), create it with an artisan command or tinker
  before driving — and say what you created.

## 2. Write a flow

One flow file per task, saved as
`docs/Feature_Planning/<slug>/verify.mjs`. Copy
`.agents/skills/run-app/flows/profile-tabs.mjs` as the starting point — it
already demonstrates login, a second guest context, and role-dependent
assertions.

The flow must cover every role that sees a different thing: guest, `user`,
`user-confirmed`, owner, author, moderator where relevant. Role-dependent
visibility is the failure mode this step exists to catch.

Assert on `data-test-id` or `aria-selected`, not on translated strings — a label
in a tab strip is present whatever tab is open, so text assertions pass
vacuously.

## 3. Run and look

```bash
APP_USER=… APP_PASSWORD=… npm run browser:drive -- docs/Feature_Planning/<slug>/verify.mjs
```

**Open every screenshot you take.** A flow that passes against a blank page has
asserted nothing. Read the driver's trailing report of `>= 400` responses and
page errors — a silent 500 inside a component is the classic miss here.

Copy the screenshots from `.agents/skills/run-app/shots/` into
`docs/Feature_Planning/<slug>/shots/`, named after the checklist row.

## 4. Report

- Fill the checklist's `OK?` column: ✅, ❌ with what happened, or `n/a` with
  the reason.
- Run `npm run gate` one last time.
- Report: rows passed, rows failed with the evidence, rows you could not
  exercise and why.

A visual defect goes back to BUILD as a fix, not into the summary as a known
issue — unless the user decides otherwise. Do not fix it yourself beyond the
obvious one-liner; report it so the orchestrator can route it.
