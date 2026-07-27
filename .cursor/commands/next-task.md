Read `.agents/skills/next-task/SKILL.md` and follow it exactly. It is the
orchestrator of the loop defined in `.agents/loop/README.md`; read that too.

Cursor has no subagents, so run every step in this chat. To keep the context
clean, do **one step per chat**: finish the step, update the status column in
`docs/Feature_Planning/BACKLOG.md`, then stop and tell me to open a new chat and
run `/continue-task`. All the state you need is on disk.
