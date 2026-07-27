Read `.agents/skills/continue-task/SKILL.md` and follow it exactly. It is the
orchestrator of the loop defined in `.agents/loop/README.md`; read that too.

Reconcile the real state from the files on disk and from `git status` before
doing anything — the status column in `docs/Feature_Planning/BACKLOG.md` may be
stale.

Cursor has no subagents, so run in this chat. Do **one step per chat** — or one
BUILD phase per chat — then update the status column, stop, and tell me to open
a new chat and run `/continue-task` again.
