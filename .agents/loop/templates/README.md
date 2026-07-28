# <Task title>

> WRAP output — the compact record of the finished feature. **This is the only
> file in the folder an agent should load by default.** The phase documents
> (`01`–`03`) remain as history; link to them from here when detail is needed.
>
> Target: under ~120 lines. If it grows past that, cut prose, not facts.

**Status:** DONE — <date> · **Domain(s):** `<Domain>` · **Spec:**
[functional](./01-functional.md) · [architecture](./02-architecture.md) ·
[plan](./03-plan.md) · [decisions](./DECISIONS.md)

## What it does

Three to five sentences. What a developer needs to know before touching this
area of the app.

## Key behaviour

- Visibility rule in one line.
- The role distinction that matters.
- The lifecycle rule (what happens on delete/deactivate).
- Anything counter-intuitive that a reader would otherwise get wrong.

## Where the code lives

| Concern | Path |
|---------|------|
| Public API | |
| Service / policy | |
| Controllers / routes | |
| Views / components | |
| JS | |
| Tests | |
| Migrations | |

## Extension points used

Which registries the feature plugged into (profile tab, settings, notification,
moderation, statistics, media usage), one line each.

## Decisions worth remembering

The three to five decisions that would otherwise be re-litigated. Full list in
`DECISIONS.md`.

## Not done

- Deliberate non-goals of this version.
- Anything cut mid-build, with the reason.
- Rows pushed back to `docs/Feature_Planning/BACKLOG.md` — link them.
