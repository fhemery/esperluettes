# Migrate npm to pnpm — request

*Written by the user. Free form, may be three lines. Everything below is
optional prompting, not a form to fill.*

## What I want

Migrate the project's package manager from npm to pnpm.

## Why

NPM is unsafe actually and sensible to a lot of attacks. Pnpm prevents supply-chain attacks by delaying download versions that have been published less than 24 hours ago by default. It is also faster, less verbose, easier to use.

## Constraints or ideas I already have

- Document how to setup PNPM in the installation. Also add an ADR explaining the choice of NPM and the consequences (supply-chain protection VS mostly an additional installation instruction). 
- Update all the documentation mentionning NPM, replacing with PNPM.
- Check there is no conflict with the script commands (package?)
Should you find a better alternative or have a better suggestion, feel free to challenge me.


## Explicitly out of scope

(none given)
