# Domain CLAUDE.md shims — request

*Written by the user. Free form, may be three lines. Everything below is
optional prompting, not a form to fill.*

## What I want

Update the domain-documentor skill to add CLAUDE.md at each domain route, with
the simple `@AGENTS.md` instruction to include the AGENTS.md file. Do it for
root as well instead of using symlinks (if there is any symlink).

## Why

This will enable CLAUDE code to get the same level of information as others on
the domains without duplicating.

## Constraints or ideas I already have

- The CLAUDE.md content is just the `@AGENTS.md` include — no duplicated prose.

## Explicitly out of scope

<what you already know you do not want>

## Facts checked when filing (not requirements)

- The skill is `.agents/skills/document-domain/`; the agent shim is
  `.claude/agents/domain-documentor.md`. Its stated output today is
  `README.md` + `CLAUDE.md`, but the 26 domains on disk carry `AGENTS.md`,
  not `CLAUDE.md`. > The system should document an AGENTS.md and create the CLAUDE.md, which would require a skill update
- Root already has a real `CLAUDE.md` (not a symlink) containing `@AGENTS.md`
  plus a Claude-Code-specific editing rule. The only symlinks in the repo are
  `public/storage` and the `.claude/skills/*` shims. > No modification needed
