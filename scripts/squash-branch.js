#!/usr/bin/env node
/*
  Squash a task branch so that it keeps one commit per BUILD commit.

  Commits that are not build commits are folded into their neighbour: the ones
  before the first build commit into the first, the ones after the last into the
  last (e.g. VERIFY's e2e specs). Build commits in between are left alone, so a
  26-phase feature stays 26 commits. A single-phase task ends as one commit.

  The last commit takes the content of the working tree (staged, unstaged and
  new files), so the WRAP paperwork can be left uncommitted and ends up inside
  the last build commit. Run `pnpm run gate` BEFORE this: the rebuilt commits
  skip the git hooks.

  No `git rebase`: the chain is rebuilt with `git commit-tree`, reusing each
  build commit's message and, for the last one, the final tree.

  Usage:
    node scripts/squash-branch.js --builds=<sha>,<sha>,... [--base=origin/main]

  --builds  the build commits, oldest first (any unambiguous sha prefix).
  --base    the branch point (default origin/main); the branch must contain it.

  Prints the old tip and the command that restores it.
*/

import { spawnSync } from 'child_process';
import { makeLog } from './utils.js';

const log = makeLog('squash-branch');

function git(args, { input } = {}) {
  const r = spawnSync('git', args, { encoding: 'utf8', input });
  if (r.status !== 0) throw new Error(`git ${args.join(' ')}\n${r.stderr}`);
  return r.stdout.trim();
}

function parseArgs(argv) {
  const args = { base: 'origin/main' };
  for (const a of argv) {
    if (a === '--') continue; // `pnpm run squash-branch -- --builds=...` forwards it
    const m = a.match(/^--([^=]+)=(.*)$/s);
    if (!m) throw new Error(`Unrecognized argument: ${a}`);
    args[m[1]] = m[2];
  }
  if (!args.builds) throw new Error('--builds=<sha>,<sha>,... is required (oldest first)');
  return args;
}

function main() {
  const args = parseArgs(process.argv.slice(2));
  const branch = git(['rev-parse', '--abbrev-ref', 'HEAD']);
  if (branch === 'main' || branch === 'HEAD') throw new Error(`Refusing to run on ${branch}`);

  const oldTip = git(['rev-parse', 'HEAD']);
  const base = git(['merge-base', args.base, 'HEAD']);
  const commits = git(['rev-list', '--reverse', `${base}..HEAD`]).split('\n').filter(Boolean);

  const builds = args.builds.split(',').map((p) => git(['rev-parse', '--verify', `${p}^{commit}`]));
  let at = -1;
  for (const b of builds) {
    const i = commits.indexOf(b);
    if (i <= at) throw new Error(`${b} is not on ${branch} after ${args.base}, or builds are out of order`);
    at = i;
  }

  // The working tree (paperwork included) is the content of the last commit.
  git(['add', '-A']);
  const finalTree = git(['write-tree']);

  let parent = base;
  builds.forEach((b, i) => {
    const tree = i === builds.length - 1 ? finalTree : git(['rev-parse', `${b}^{tree}`]);
    parent = git(['commit-tree', tree, '-p', parent], { input: git(['log', '-1', '--format=%B', b]) + '\n' });
  });

  git(['update-ref', `refs/squash-backup/${branch}`, oldTip]);
  git(['reset', '--hard', parent]);

  if (git(['rev-parse', 'HEAD^{tree}']) !== finalTree) throw new Error('Final tree differs from the working tree');
  log.ok(`${commits.length} commit(s) -> ${builds.length}`);
  log.ok(`old tip: ${oldTip}  (restore: git reset --hard ${oldTip})`);
  git(['log', '--oneline', `${base}..HEAD`]).split('\n').forEach((l) => log.ok(l));
}

try {
  main();
} catch (e) {
  log.warn(e.message);
  process.exit(1);
}
