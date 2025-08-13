# Contributing

Thanks for your interest in contributing to Esperluettes!

This document explains our workflow, commit conventions, and local quality gates.

## Language used

The project is managed mostly by a French community, therefore :

- The whole code and documentation of the repository is in English, for better consistency.
- The issues are mostly written in French, so that anyone can contribute if need be.

## Repository and workflow

- We use a single GitHub repository with Pull Requests.
- Create a feature branch from `main` for each change.
- Open a PR early (drafts welcome). Keep PRs focused and small.
- We have no CI and automated checks for now, only commit hooks.
- Ask for at least one review.
- We favor integration tests, see [Testing](./docs/Testing.md) for more details

## Conventional Commits

We enforce Conventional Commits via commitlint. Use one of the following types:

- feat: a new feature
- fix: a bug fix
- docs: documentation only changes
- style: formatting, missing semicolons, etc. (no code change)
- refactor: code change that neither fixes a bug nor adds a feature
- perf: performance improvement
- test: adding or fixing tests
- build: build system or external dependencies
- ci: CI configuration or scripts
- chore: other changes that don’t modify src or tests
- revert: reverts a previous commit

Examples:

- feat(auth): add activation codes to registration
- fix(profile): prevent NPE when avatar is missing
- chore: bump deps

Scope is optional but recommended (e.g., `auth`, `profile`, `shared`, `admin`).

## Local hooks (installed automatically)

We use Husky to install Git hooks on `npm install`.

- commit-msg: validates your message with commitlint (Conventional Commits)
- pre-commit: runs architectural checks with Deptrac, and launch unit / integration tests (with Pest)

If needed, you can bypass the Deptrac check temporarily:

```
SKIP_DEPTRAC=1 git commit -m "chore: emergency commit"
```

For details about what Deptrac does, check [Architecture Doc](./docs/Architecture.md)

## Code style & tests

- Follow Laravel best practices; keep controllers thin; put business logic in services.
- We have a (very) shallow set of tests for now. The aim is to improve it little by little. The existing tests use [Pest](https://pestphp.com/), and are run during commit checks

## Security

- Don’t include secrets in commits. Use environment variables.
- Report vulnerabilities privately to the maintainers.
