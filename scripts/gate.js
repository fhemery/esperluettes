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
    pnpm run gate                 # docs + deptrac + php tests + js tests + asset build
    pnpm run gate -- --quick      # skip the asset build
    pnpm run gate -- --all        # ignore change detection, run everything
    pnpm run gate -- --only=php   # run a single step (docs|deptrac|php|js|build)

  Honours LOCAL_RUNNER (php|sail) exactly like the husky hooks.
*/

import path from 'path';
import { fileURLToPath } from 'url';
import { spawnSync } from 'child_process';
import { makeLog, runCmdAsync, determineRunner, isSailRunning } from './utils.js';
import { resolvePhpTestPlan, getModifiedFiles } from './launch_staged_tests.js';

const log = makeLog('gate');
const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');

// A front-end change is anything vitest or vite could possibly care about.
const ASSET_FILE = /\.(m?js|cjs|ts|tsx|jsx|vue|css|scss)$/i;
const ASSET_CONFIG = /^(package(-lock)?\.json|pnpm-lock\.yaml|vite\.config\.[cm]?[jt]s|vitest\.config\.[cm]?[jt]s|tailwind\.config\.[cm]?js|postcss\.config\.[cm]?js)$/i;

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

async function main() {
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
    { id: 'js', label: 'JS test suite (vitest)', cmd: 'pnpm', args: ['exec', 'vitest', 'run'], skipReason: assetsTouched ? null : noAssetChange },
    { id: 'build', label: 'Asset build (vite)', cmd: 'pnpm', args: ['exec', 'vite', 'build'], skip: args.quick, skipReason: assetsTouched ? null : noAssetChange },
  ];

  const selected = steps.filter(s => (args.only ? s.id === args.only : !s.skip));

  if (selected.length === 0) {
    console.error(`[gate] Nothing to run. Unknown --only value: ${args.only}`);
    process.exit(1);
  }

  const failed = [];
  const skipped = [];
  const ran = [];

  // Start every non-skipped step's command right away (all launched before
  // any is awaited), then let each one print its own labelled block as soon
  // as it settles — order = completion order, not declaration order.
  // Exception: `build` (vite build, which empties public/build and rewrites
  // manifest.json only near the end of its run) waits for `php` to settle
  // when both are selected and php is not skipped — a concurrently-running
  // `php` step renders Blade views via @vite and can transiently hit a
  // missing manifest otherwise. No race is possible without a concurrently
  // running php, so build starts immediately when php is absent or skipped.
  const runStep = (step) => runCmdAsync(step.cmd, step.args, { cwd: root }).then(({ ok, output }) => {
    log(`--- ${step.label}`);
    if (output) process.stdout.write(output + '\n');
    log(`${ok ? 'PASS' : 'FAIL'}: ${step.label}`);
    ran.push(step.id);
    if (!ok) failed.push(step.id);
  });

  const pending = [];
  let phpPromise = null;
  for (const step of selected) {
    if (step.skipReason) {
      log(`SKIP: ${step.label} (${step.skipReason})`);
      skipped.push(step.id);
      continue;
    }
    if (step.id === 'build' && phpPromise) {
      pending.push(phpPromise.then(() => runStep(step)));
      continue;
    }
    const promise = runStep(step);
    if (step.id === 'php') phpPromise = promise;
    pending.push(promise);
  }

  await Promise.allSettled(pending);

  console.log('');
  if (failed.length > 0) {
    console.error(`[gate] FAILED: ${failed.join(', ')}`);
    process.exit(1);
  }
  console.log(`[gate] PASSED: ${ran.join(', ') || 'nothing to run'}${skipped.length ? ` (skipped: ${skipped.join(', ')})` : ''}`);
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
