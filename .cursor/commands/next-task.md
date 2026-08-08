Read `.agents/skills/next-task/SKILL.md` and follow it exactly. It is the
orchestrator of the loop defined in `.agents/loop/README.md`; read that too.

Honour the row's **mode**:

- `interactive` — do **one step per chat**: finish the step, update the status
  column in `docs/Feature_Planning/BACKLOG.md`, then stop and tell me to open a
  new chat and run `/continue-task`. All the state you need is on disk.
- `auto` — keep going through the remaining steps in this chat until the task
  is `DONE` or a stop condition needs me. Do not pause between steps when there
  is no question. Do not start the next backlog `TODO` unless I ask.

## Cursor: spawn fallback

PLAN / BUILD / VERIFY / WRAP must run in subagents — never inline in this
thread. The skill's "only if the host genuinely cannot spawn agents" escape
hatch means the Task tool itself is unavailable, not that a model refused.

**Premium credit exhaustion is not that case.** When a spawn fails because
premium credits (or a premium model) are exhausted, retry the same agent with
a Cursor model (`composer-2.5-fast`, or another listed Cursor/`composer`/`cursor-*`
model). Keep spawning; do not switch to running the step's skill in this
thread. Say which model you fell back to in one line.

Only run PLAN / BUILD / VERIFY / WRAP in this thread if the Task / agent-spawn
capability is actually missing — and say so explicitly before you do.
