Add a task to `docs/Feature_Planning/BACKLOG.md`. Do **not** start working on it.

1. Take the request I gave with this command. If I gave none, ask me what the
   task is.
2. Pick a kebab-case slug and create `docs/Feature_Planning/<slug>/`.
3. Write `00-request.md` from `.agents/loop/templates/00-request.md`, filled
   with my own words — do not embellish, do not invent requirements. Ask at most
   two clarifying questions if the request is unusable as written.
4. Copy `.agents/loop/templates/DECISIONS.md` into the folder.
5. Add a row to the backlog table: title, folder, proposed mode
   (`auto` for a bugfix or chore, `interactive` otherwise), status `TODO`.
   Append it at the bottom unless the task blocks or is blocked by another row —
   then insert it where the dependency requires. Rows carry no numbers, so
   inserting one changes nothing else.
6. Report the row and where it landed in the order.
