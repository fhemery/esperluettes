Read `.agents/skills/next-task/SKILL.md` and follow it exactly. It is the
orchestrator of the loop defined in `.agents/loop/README.md`; read that too.

Honour the row's **mode**:

- `interactive` — do **one step per chat**: finish the step, update the status
  column in `docs/Feature_Planning/BACKLOG.md`, then stop and tell me to open a
  new chat and run `/continue-task`. All the state you need is on disk.
- `auto` — keep going through the remaining steps in this chat until the task
  is `DONE` or a stop condition needs me. Do not pause between steps when there
  is no question. Do not start the next backlog `TODO` unless I ask.
