# <Task title>

> WRAP output — `CONTEXT.md` compacted into an index of the finished feature.
> It becomes `_done/<slug>.md`; the working files are deleted, so link to
> nothing in the task folder. Git keeps the rest.
>
> Target: under ~120 lines. If it grows past that, cut prose, not facts.

**Status:** DONE — <date> · **Domain(s):** `<Domain>`

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

The three to five decisions that would otherwise be re-litigated, from
`CONTEXT.md` §4 and §2.7.

## Not done

- Deliberate non-goals of this version.
- Anything cut mid-build, with the reason.
- Rows pushed back to `docs/Feature_Planning/BACKLOG.md` — link them.
