#!/usr/bin/env node
/*
  Mechanical half of /add-task: everything that does not need judgment.

  The agent still decides the slug, title, mode and (rarely) the insertion
  point, and still writes 00-request.md itself — that's the part worth an
  LLM. This script just does the file plumbing so the agent doesn't have to
  Read the backlog and both templates on every call: create the task folder,
  copy DECISIONS.md verbatim, and insert one line in BACKLOG.md.

  Usage:
    node scripts/add-task.js --slug=<slug> --title="<title>" --mode=auto|interactive [--position=bottom|top|after:<slug>|before:<slug>]

  --position defaults to "bottom". 00-request.md is NOT written by this
  script — write it yourself (with the Write tool) before or after calling
  this, at docs/Feature_Planning/<slug>/00-request.md.
*/

import fs from 'fs';
import path from 'path';
import { makeLog } from './utils.js';

const log = makeLog('add-task');

const PLANNING_DIR = path.resolve('docs/Feature_Planning');
const BACKLOG_PATH = path.join(PLANNING_DIR, 'BACKLOG.md');
const DECISIONS_TEMPLATE = path.resolve('.agents/loop/templates/DECISIONS.md');

function parseArgs(argv) {
  const args = { position: 'bottom' };
  for (const a of argv) {
    const m = a.match(/^--([^=]+)=(.*)$/s);
    if (!m) throw new Error(`Unrecognized argument: ${a}`);
    args[m[1]] = m[2];
  }
  return args;
}

function fail(msg) {
  log.warn(msg);
  process.exit(1);
}

function insertLine(backlog, line, position) {
  const lines = backlog.split('\n');
  const doneIdx = lines.findIndex((l) => l.trim() === '## Done');
  if (doneIdx === -1) fail('BACKLOG.md has no "## Done" section — cannot locate the TODO list.');

  if (position === 'bottom') {
    let insertAt = doneIdx;
    while (insertAt > 0 && lines[insertAt - 1].trim() === '') insertAt--;
    lines.splice(insertAt, 0, line);
    return { lines, landed: 'bottom of the TODO list' };
  }

  if (position === 'top') {
    const firstEntry = lines.findIndex((l, i) => i < doneIdx && l.startsWith('- **'));
    if (firstEntry === -1) fail('Could not find an existing entry to anchor "top" on.');
    lines.splice(firstEntry, 0, line);
    return { lines, landed: 'top of the TODO list' };
  }

  const m = position.match(/^(after|before):(.+)$/);
  if (!m) fail(`Unrecognized --position "${position}". Use bottom, top, after:<slug> or before:<slug>.`);
  const [, rel, slug] = m;
  const matchIdx = lines.findIndex((l, i) => i < doneIdx && l.includes(`\`${slug}/\``));
  if (matchIdx === -1) fail(`No TODO entry found for slug "${slug}" to insert ${rel}.`);
  const at = rel === 'after' ? matchIdx + 1 : matchIdx;
  lines.splice(at, 0, line);
  return { lines, landed: `${rel} \`${slug}/\`` };
}

function main() {
  const args = parseArgs(process.argv.slice(2));
  const { slug, title, mode } = args;

  if (!slug || !/^[a-z0-9]+(-[a-z0-9]+)*$/.test(slug)) fail('--slug is required and must be kebab-case.');
  if (!title) fail('--title is required.');
  if (!['auto', 'interactive'].includes(mode)) fail('--mode must be "auto" or "interactive".');

  const taskDir = path.join(PLANNING_DIR, slug);
  if (fs.existsSync(taskDir)) fail(`${taskDir} already exists.`);
  if (!fs.existsSync(BACKLOG_PATH)) fail(`${BACKLOG_PATH} not found.`);
  if (!fs.existsSync(DECISIONS_TEMPLATE)) fail(`${DECISIONS_TEMPLATE} not found.`);

  fs.mkdirSync(taskDir, { recursive: true });
  fs.copyFileSync(DECISIONS_TEMPLATE, path.join(taskDir, 'DECISIONS.md'));

  const line = `- **${title}** · \`${slug}/\` · ${mode} · TODO`;
  const backlog = fs.readFileSync(BACKLOG_PATH, 'utf8');
  const { lines, landed } = insertLine(backlog, line, args.position);
  fs.writeFileSync(BACKLOG_PATH, lines.join('\n'));

  log.ok(`Created docs/Feature_Planning/${slug}/ (DECISIONS.md copied; 00-request.md is yours to write).`);
  log.ok(`Backlog entry added at ${landed}:`);
  log.raw(`  ${line}`);
}

main();
