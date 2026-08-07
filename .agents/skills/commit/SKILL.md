---
name: commit
description: Write and make a git commit that matches this project's conventions. Use whenever committing — at the end of a build phase, after a fix, or when the user says "commit this". Covers the conventional-commit format, the scope vocabulary, what belongs in a body, and the pre-commit hooks.
---

# Commit

## Before you commit

- **Only commit when asked**, or at the end of a loop step — BUILD commits at
  the end of each phase; REFINE, DESIGN, PLAN and WRAP each commit their own
  artifact before handing back (see `.agents/loop/README.md`'s "Gate" section).
  Never commit speculatively mid-work.
- Never commit on `main`. Branch first: `git checkout -b <type>/<short-slug>`.
- `git status` and `git diff` first — know exactly what is going in. Do not
  `git add -A` without looking; unrelated stray files are how a clean phase
  commit turns into a mess.
- `npm run gate` should already be green. The pre-commit hook runs deptrac and
  vitest anyway, and the full PHP suite when you are on `main`.

## Format

Conventional commits — enforced by commitlint via the `commit-msg` hook, so a
malformed subject is rejected outright.

```
<type>(<scope>): <subject>

<body>

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>
```

**Types:** `feat` · `fix` · `refactor` · `chore` · `docs` · `test`.

**Scope** is the domain in lowercase — `quote`, `profile`, `story`, `media`,
`settings`, `statistics`, `comment`, `news`, `faq`, `discord`, `config`,
`shared` — or `dev` for tooling, workflow and developer-experience changes.
Omit the scope only when the change genuinely spans the app.

**Subject:** imperative mood, lowercase, no trailing period, under ~70 chars.
"own the comments tab setting and privacy rule", not "Owned…" or "Changes to…".

## The body is for *why*

The diff already says what changed. The body says why it needed to, and it is
the part that will still be worth reading in a year. Look at
`git log --no-merges` for the house style — the good ones explain the problem
first, then the reasoning, then the consequence.

Include, when they apply:

- the problem or the wrong state that motivated the change;
- the reasoning behind a non-obvious choice, and the option not taken;
- anything a reviewer would otherwise have to ask about — a data migration that
  is *not* needed, a rule that moved domains, a deliberate omission;
- how you verified something that tests do not cover.

Skip the body only for a genuinely self-evident one-liner.

Wrap at 80 columns. Use `-` bullets when the commit does several distinct
things — but prefer splitting the commit.

Always end with the `Co-Authored-By` trailer above.

## One commit, one thing

In the loop, **one BUILD phase = one commit**: it ships independently, keeps the
gate green, and is revertable on its own. If you are reaching for "and" in the
subject, it is two commits.

## Writing the message

Use a heredoc so the body keeps its newlines:

```bash
git commit -F - <<'EOF'
type(scope): subject

body

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>
EOF
```

## If the hook fails

Read the output and fix the cause. Do not pass `--no-verify`, do not weaken a
test, do not add a deptrac exception to get a commit through — a red hook means
the phase is not finished. On a deptrac violation, use the `fix-deptrac` skill.
