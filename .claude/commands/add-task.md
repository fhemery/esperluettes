---
description: Add a new task to the loop-engineering backlog
---

Add a task to `docs/Feature_Planning/BACKLOG.md`. Do **not** start working on it.

1. Take the request below. If it is empty, ask the user what the task is.
2. Pick a kebab-case slug and create `docs/Feature_Planning/<slug>/`.
3. Write `00-request.md` from `.agents/loop/templates/00-request.md`, filled
   with the user's own words — do not embellish, do not invent requirements. Ask
   at most two clarifying questions if the request is unusable as written.
4. Copy `.agents/loop/templates/DECISIONS.md` into the folder.
5. Add an entry to the backlog list: title, folder, proposed mode
   (`auto` for a bugfix or chore, `interactive` otherwise), status `TODO`.
   Append it at the bottom unless the task blocks or is blocked by another
   entry — then insert it where the dependency requires. Entries carry no
   numbers, so inserting one changes nothing else.
6. Report the entry and where it landed in the order.

Request:

$ARGUMENTS
