#!/usr/bin/env node
/*
  Definition-of-done gate.

  One command that mechanically decides whether a change is shippable.
  Used by the loop-engineering skills (.agents/loop) as the exit criterion of
  every BUILD phase, and usable by hand before opening a PR.

  Steps are scoped to what actually changed on the branch (committed work since
  `main`, plus staged/unstaged/untracked files):
  - PHP tests run only for the impacted domains and their deptrac dependents;
    the full suite still runs when the change reaches outside app/Domains.
  - JS tests and the asset build are skipped when no front-end asset changed.
  Use --all to force every step over the whole codebase.

  Usage:
    npm run gate                 # docs + deptrac + php tests + js tests + asset build
    npm run gate -- --quick      # skip the asset build
    npm run gate -- --all        # ignore change detection, run everything
    npm run gate -- --only=php   # run a single step (docs|deptrac|php|js|build)

  Honours LOCAL_RUNNER (php|sail) exactly like the husky hooks.
*/

import path from 'path';
import { fileURLToPath } from 'url';
import { spawnSync } from 'child_process';
import { makeLog, runCmd, determineRunner, isSailRunning } from './utils.js';
import { resolvePhpTestPlan, getModifiedFiles } from './launch_staged_tests.js';

const log = makeLog('gate');
const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');

// A front-end change is anything vitest or vite could possibly care about.
const ASSET_FILE = /\.(m?js|cjs|ts|tsx|jsx|vue|css|scss)$/i;
const ASSET_CONFIG = /^(package(-lock)?\.json|vite\.config\.[cm]?[jt]s|vitest\.config\.[cm]?[jt]s|tailwind\.config\.[cm]?js|postcss\.config\.[cm]?js)$/i;

function parseArgs(argv) {
  const args = { quick: false, only: null, all: false };
  for (const a of argv) {
    if (a === '--quick') args.quick = true;
    else if (a === '--all') args.all = true;
    else if (a.startsWith('--only=')) args.only = a.slice('--only='.length).trim();
  }
  return args;
}

// Everything this branch changed: commits since it forked off main, plus the
// working tree. A gate that only looked at uncommitted files would happily pass
// a branch whose committed work is broken.
function branchChangedFiles() {
  const files = new Set(getModifiedFiles());
  for (const base of ['main', 'origin/main']) {
    const mb = spawnSync('git', ['merge-base', 'HEAD', base], { encoding: 'utf8' });
    if (mb.status !== 0) continue;
    const res = spawnSync('git', ['diff', '--name-only', `${mb.stdout.trim()}...HEAD`], { encoding: 'utf8' });
    if (res.status !== 0) continue;
    for (const f of (res.stdout || '').split(/\r?\n/).map(s => s.trim()).filter(Boolean)) files.add(f);
    break;
  }
  return [...files];
}

// Playwright specs are neither bundled by vite nor run by vitest.
const NON_ASSET = /^(e2e\/|playwright\.config)/;

function touchesAssets(files) {
  return files.some(f => {
    const rel = f.replace(/\\/g, '/');
    if (NON_ASSET.test(rel)) return false;
    return ASSET_FILE.test(rel) || ASSET_CONFIG.test(rel);
  });
}

function artisan(runner, extra) {
  return runner === 'sail'
    ? { cmd: path.join('vendor', 'bin', 'sail'), args: ['artisan', ...extra] }
    : { cmd: 'php', args: ['artisan', ...extra] };
}

function main() {
  const args = parseArgs(process.argv.slice(2));
  const runner = determineRunner();

  if (runner === 'sail' && !isSailRunning()) {
    log.warn('Sail is not running. Start it with `./vendor/bin/sail up -d` first.');
    process.exit(1);
  }

  const changed = args.all ? [] : branchChangedFiles();
  // test:parallel takes directories, so scoped runs stay parallel and there is
  // no reason to cap how many domains they cover.
  const phpPlan = args.all
    ? { mode: 'all', reason: '--all' }
    : resolvePhpTestPlan(changed, log, { maxDirs: Infinity });
  const phpStep = phpPlan.mode === 'dirs'
    ? { ...artisan(runner, ['test:parallel', ...phpPlan.dirs]), label: `PHP tests (${phpPlan.dirs.length} domains)` }
    : { ...artisan(runner, ['test:parallel']), label: 'PHP test suite' };

  const assetsTouched = args.all || touchesAssets(changed);
  const noAssetChange = 'no JS/CSS change on this branch';

  const steps = [
    { id: 'docs', label: 'Documentation consistency', cmd: 'node', args: [path.join('scripts', 'check-docs.js')] },
    { id: 'deptrac', label: 'Deptrac (architecture boundaries)', cmd: 'node', args: [path.join('scripts', 'launch_deptrac.js')] },
    { id: 'php', label: phpStep.label, cmd: phpStep.cmd, args: phpStep.args, skipReason: phpPlan.mode === 'none' ? phpPlan.reason : null },
    { id: 'js', label: 'JS test suite (vitest)', cmd: 'npx', args: ['vitest', 'run'], skipReason: assetsTouched ? null : noAssetChange },
    { id: 'build', label: 'Asset build (vite)', cmd: 'npx', args: ['vite', 'build'], skip: args.quick, skipReason: assetsTouched ? null : noAssetChange },
  ];

  const selected = steps.filter(s => (args.only ? s.id === args.only : !s.skip));

  if (selected.length === 0) {
    console.error(`[gate] Nothing to run. Unknown --only value: ${args.only}`);
    process.exit(1);
  }

  const failed = [];
  const skipped = [];
  const ran = [];
  for (const step of selected) {
    if (step.skipReason) {
      log(`SKIP: ${step.label} (${step.skipReason})`);
      skipped.push(step.id);
      continue;
    }
    log(`--- ${step.label}`);
    const ok = runCmd(step.cmd, step.args, { cwd: root });
    log(`${ok ? 'PASS' : 'FAIL'}: ${step.label}`);
    ran.push(step.id);
    if (!ok) failed.push(step.id);
  }

  console.log('');
  if (failed.length > 0) {
    console.error(`[gate] FAILED: ${failed.join(', ')}`);
    process.exit(1);
  }
  console.log(`[gate] PASSED: ${ran.join(', ') || 'nothing to run'}${skipped.length ? ` (skipped: ${skipped.join(', ')})` : ''}`);
}

main();
