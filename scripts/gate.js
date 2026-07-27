#!/usr/bin/env node
/*
  Definition-of-done gate.

  One command that mechanically decides whether a change is shippable.
  Used by the loop-engineering skills (.agents/loop) as the exit criterion of
  every BUILD phase, and usable by hand before opening a PR.

  Usage:
    npm run gate                 # deptrac + php tests + js tests + asset build
    npm run gate -- --quick      # skip the asset build
    npm run gate -- --only=php   # run a single step (deptrac|php|js|build)

  Honours LOCAL_RUNNER (php|sail) exactly like the husky hooks.
*/

import path from 'path';
import { fileURLToPath } from 'url';
import { makeLog, runCmd, determineRunner, isSailRunning } from './utils.js';

const log = makeLog('gate');
const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');

function parseArgs(argv) {
  const args = { quick: false, only: null };
  for (const a of argv) {
    if (a === '--quick') args.quick = true;
    else if (a.startsWith('--only=')) args.only = a.slice('--only='.length).trim();
  }
  return args;
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

  const tests = artisan(runner, ['test:parallel']);

  const steps = [
    { id: 'deptrac', label: 'Deptrac (architecture boundaries)', cmd: 'node', args: [path.join('scripts', 'launch_deptrac.js')] },
    { id: 'php', label: 'PHP test suite', cmd: tests.cmd, args: tests.args },
    { id: 'js', label: 'JS test suite (vitest)', cmd: 'npx', args: ['vitest', 'run'] },
    { id: 'build', label: 'Asset build (vite)', cmd: 'npx', args: ['vite', 'build'], skip: args.quick },
  ];

  const selected = steps.filter(s => (args.only ? s.id === args.only : !s.skip));

  if (selected.length === 0) {
    console.error(`[gate] Nothing to run. Unknown --only value: ${args.only}`);
    process.exit(1);
  }

  const failed = [];
  for (const step of selected) {
    log(`--- ${step.label}`);
    const ok = runCmd(step.cmd, step.args, { cwd: root });
    log(`${ok ? 'PASS' : 'FAIL'}: ${step.label}`);
    if (!ok) failed.push(step.id);
  }

  console.log('');
  if (failed.length > 0) {
    console.error(`[gate] FAILED: ${failed.join(', ')}`);
    process.exit(1);
  }
  console.log(`[gate] PASSED: ${selected.map(s => s.id).join(', ')}`);
}

main();
