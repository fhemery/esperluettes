---
description: Add a new task to the loop-engineering backlog
---

Add a task to `docs/Feature_Planning/BACKLOG.md`. Do **not** start working on it.

1. Take the request below. If it is empty, ask the user what the task is.
2. Pick a kebab-case slug and a title. Pick the mode: `auto` for a bugfix or
   chore, `interactive` otherwise. Default position is the bottom of the
   TODO list — only look at `BACKLOG.md` if the request explicitly says this
   task blocks or is blocked by another one, in which case grep for that
   entry's slug and pass `after:<slug>` or `before:<slug>`.
3. Run `npm run add-task -- --slug=<slug> --title="<title>" --mode=<mode> [--position=after:<slug>|before:<slug>]`
   (position defaults to `bottom`). It creates the task folder, copies
   `DECISIONS.md`, and inserts the backlog line.
4. Write `docs/Feature_Planning/<slug>/00-request.md`, filled with the user's
   own words in this shape — do not embellish, do not invent requirements.
   Ask at most two clarifying questions first if the request is unusable as
   written.

   ```
   # <Task title> — request

   *Written by the user. Free form, may be three lines. Everything below is
   optional prompting, not a form to fill.*

   ## What I want

   <the ask, in your own words>

   ## Why

   <the problem it solves, or the user it serves>

   ## Constraints or ideas I already have

   <anything that should not be re-litigated during REFINE>

   ## Explicitly out of scope

   <what you already know you do not want>
   ```

5. Report the backlog entry and where it landed.

Request:

$ARGUMENTS
