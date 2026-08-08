Read `.agents/skills/continue-task/SKILL.md` and follow it exactly. It is the
orchestrator of the loop defined in `.agents/loop/README.md`; read that too.

Reconcile the real state from the files on disk and from `git status` before
doing anything — the status column in `docs/Feature_Planning/BACKLOG.md` may be
stale.

Honour the row's **mode**:

- `interactive` — do **one step per chat** (or one BUILD phase), update the
  status column, stop, and tell me to open a new chat and run `/continue-task`.
- `auto` — keep going through the remaining steps in this chat until the task
  is `DONE` or a stop condition needs me. Do not pause between steps when there
  is no question. All state stays on disk either way.

## Cursor: spawn fallback

Same rule as `/next-task`: PLAN / BUILD / VERIFY / WRAP stay in subagents.
Premium credit exhaustion is **not** "cannot spawn" — retry with a Cursor
model (`composer-2.5-fast`, or another listed Cursor/`composer`/`cursor-*`
model) and keep going. Only run those steps in this thread if the Task /
agent-spawn capability is actually missing, and say so explicitly first.
