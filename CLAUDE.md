# Project instructions

The project instructions live in `AGENTS.md`, shared by every tool. This file
adds nothing to them except the Claude-Code-specific rule below.

@AGENTS.md

# Claude Code specifics

## Editing files

- **Do not use Python (or `sed`, `awk`, heredocs) to modify file content.** Use
  the Edit and Write tools. They are safer, reviewable, and leave a clean diff.
- Scripting a bulk edit is acceptable only when the change is genuinely
  mechanical across many files, and say so before doing it.
- Python remains fine as a *language of the project* when a task calls for it —
  the rule is about editing files, not about the language.
