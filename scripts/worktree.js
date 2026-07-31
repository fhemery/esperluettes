#!/usr/bin/env node
/*
  Create a sibling git worktree that can run its own Sail stack.

  Two agent sessions working the loop in parallel need two checkouts. A worktree
  is enough — it shares the object store with the main clone, and `git` refuses
  to check the same branch out twice, which is a useful accident.

  What a worktree does NOT get for free is a runnable app:

    - docker-compose.yml declares no `name:`, so Compose derives the project
      name from the directory basename. Different directory, different
      containers/network/volume — that part is automatic.
    - The host port bindings are not. `.env` is gitignored, so a fresh worktree
      has none at all, and copying the main one verbatim would make `sail up`
      fight for :80, :5173, :8080 and :3306.
    - Session cookies are not port-scoped. Two instances on localhost sharing
      SESSION_COOKIE log each other out — the same trap .env.e2e calls out.

  This script copies .env and shifts exactly those values.

  Usage:
    npm run worktree -- <name> [--offset=N]

    <name>     worktree directory + branch name, e.g. `b`, `annotations`
    --offset   port offset, default: first free slot starting at 1
*/

import fs from 'fs';
import path from 'path';
import { execFileSync } from 'child_process';
import { makeLog } from './utils.js';

const log = makeLog('worktree');

// Ports that must differ per instance, and their docker-compose defaults.
const PORTS = {
  APP_PORT: 80,
  VITE_PORT: 5173,
  E2E_PORT: 8080,
  FORWARD_DB_PORT: 3306,
};

function git(args, cwd) {
  return execFileSync('git', args, { cwd, encoding: 'utf8' }).trim();
}

/** The main checkout, whichever worktree we were invoked from. */
function mainWorktree() {
  const first = git(['worktree', 'list', '--porcelain']).split('\n')[0];
  return first.replace(/^worktree /, '');
}

function parseArgs(argv) {
  const rest = [];
  let offset = null;
  for (const a of argv) {
    if (a.startsWith('--offset=')) offset = Number(a.slice('--offset='.length));
    else rest.push(a);
  }
  return { name: rest[0], offset };
}

/**
 * Rewrite a KEY=value line, or append it when the key is absent.
 * .env is line-oriented and hand-maintained; a regex keeps comments and order.
 */
function setEnv(text, key, value) {
  const line = `${key}=${value}`;
  const re = new RegExp(`^${key}=.*$`, 'm');
  return re.test(text) ? text.replace(re, line) : `${text.replace(/\n*$/, '')}\n${line}\n`;
}

function main() {
  const { name, offset: explicitOffset } = parseArgs(process.argv.slice(2));

  if (!name || !/^[a-z0-9][a-z0-9-]*$/.test(name)) {
    console.error('[worktree] Usage: npm run worktree -- <name> [--offset=N]');
    console.error('[worktree] <name> must be lowercase letters, digits and dashes.');
    process.exit(1);
  }

  const root = mainWorktree();
  const base = path.basename(root);
  const dest = path.resolve(root, '..', `${base}-${name}`);

  if (fs.existsSync(dest)) {
    console.error(`[worktree] ${dest} already exists.`);
    process.exit(1);
  }

  // One offset per existing sibling worktree, so ports never overlap.
  const existing = git(['worktree', 'list', '--porcelain'], root)
    .split('\n')
    .filter(l => l.startsWith('worktree ')).length - 1;
  const offset = explicitOffset ?? Math.max(existing, 1);

  const envSource = path.join(root, '.env');
  if (!fs.existsSync(envSource)) {
    console.error(`[worktree] No .env in ${root} — nothing to derive the instance from.`);
    process.exit(1);
  }

  log(`creating ${dest} on branch ${name} (port offset ${offset})`);
  git(['worktree', 'add', dest, '-b', name], root);

  let env = fs.readFileSync(envSource, 'utf8');
  for (const [key, defaultPort] of Object.entries(PORTS)) {
    env = setEnv(env, key, defaultPort + offset);
  }
  env = setEnv(env, 'APP_URL', `http://localhost:${PORTS.APP_PORT + offset}`);
  env = setEnv(env, 'SESSION_COOKIE', `${base}_${name}_session`);
  fs.writeFileSync(path.join(dest, '.env'), env);

  // .env.e2e is committed, so it arrives with the checkout already.
  const e2e = path.join(dest, '.env.e2e');
  if (fs.existsSync(e2e)) {
    let text = fs.readFileSync(e2e, 'utf8');
    text = setEnv(text, 'APP_URL', `http://localhost:${PORTS.E2E_PORT + offset}`);
    text = setEnv(text, 'SESSION_COOKIE', `${base}_${name}_e2e_session`);
    fs.writeFileSync(e2e, text);
  }

  log('.env written:');
  for (const [key, defaultPort] of Object.entries(PORTS)) {
    console.log(`         ${key}=${defaultPort + offset}`);
  }

  console.log('');
  log('vendor/ and node_modules/ are gitignored — install them before the gate:');
  log('Initialize following the docs/ whether you are using sail or php directly');
  log('Then:')
  console.log(`         cd ${dest}`);
  console.log('         composer install && npm install && npm run build');
  console.log('         ./vendor/bin/sail up -d');
}

main();
