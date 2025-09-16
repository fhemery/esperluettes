#!/usr/bin/env node
/*
  Husky pre-commit hook implemented in Node.js (ESM)
  - Reads LOCAL_RUNNER from environment or .env (php|sail). Defaults to 'sail'.
  - Blocks adding new files under forbidden prefixes unless bypassed.
  - Optionally runs Deptrac (skip with SKIP_DEPTRAC=1).
  - Runs test suite (skip with SKIP_TESTS=1).
*/

import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import { spawnSync } from 'child_process';
import { parse as dotenvParse } from 'dotenv';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

function log(msg) {
    process.stdout.write(`[husky] ${msg}\n`);
}

function fileExists(p) {
    try { return fs.existsSync(p); } catch { return false; }
}

function runCmd(cmd, args, opts = {}) {
    log(`Running ${cmd} ${args.join(' ')}`);
    const res = spawnSync(cmd, args, { stdio: 'inherit', shell: process.platform === 'win32', ...opts });
    log(`Result of command ${cmd} ${args.join(' ')}: ${res.status}`);
    return res.status === 0;
}

function getAddedFiles() {
    const res = spawnSync('git', ['diff', '--cached', '--name-only', '--diff-filter=A'], { encoding: 'utf8' });
    if (res.status !== 0) {
        return [];
    }
    const out = res.stdout || '';
    return out.split(/\r?\n/).map(s => s.trim()).filter(Boolean);
}

function main() {
    // Determine runner
    let localRunner = process.env.LOCAL_RUNNER;
    if (!localRunner) {
        // Read only from .env without polluting process.env, to avoid breaking Laravel's .env.testing behavior
        const envFile = path.resolve(__dirname, '../.env');
        if (fileExists(envFile)) {
          try {
            const raw = fs.readFileSync(envFile, 'utf8');
            const parsed = dotenvParse(raw);
            localRunner = parsed.LOCAL_RUNNER;
          } catch (_) {
            // ignore
          }
        }
    }
    let runner = (localRunner || '').trim().toLowerCase();
    if (!runner) {
        log("LOCAL_RUNNER is not set; defaulting to 'sail'");
        runner = 'sail';
    }
    if (runner !== 'php' && runner !== 'sail') {
        log(`Invalid LOCAL_RUNNER value '${runner}', defaulting to 'sail'`);
        runner = 'sail';
    }

    // Forbidden paths check (unless bypassed)
    const bypassForbidden = process.env.SKIP_FORBIDDEN === '1' || fileExists(path.join('.git', 'allow-new-in-restricted'));
    if (bypassForbidden) {
        log('Skipping forbidden-paths check (bypass enabled)');
    } else {
        const added = getAddedFiles();
        const forbiddenPrefixes = ['database/', 'tests/', 'resources/', 'routes/', 'vendor/'];
        const forbiddenAdded = added.filter(f => forbiddenPrefixes.some(p => f.startsWith(p)));
        if (forbiddenAdded.length > 0) {
            log("Error: New files cannot be added under 'database/', 'tests/', 'resources/', or 'routes/'");
            for (const f of forbiddenAdded) {
                process.stdout.write(` - ${f}\n`);
            }
            log("If legitimate, relocate per architecture rules (e.g., app/Domains/<domain>/Database/Migrations)");
            log('To bypass: use --no-verify, set SKIP_FORBIDDEN=1, or create .git/allow-new-in-restricted');
            process.exit(1);
        }
    }

    // Deptrac (optional)
    if (process.env.SKIP_DEPTRAC === '1') {
        log('Skipping Deptrac (SKIP_DEPTRAC=1)');
    } else {
        if (runner === 'php') {
            if (fileExists(path.join('vendor', 'bin', 'deptrac'))) {
                if (!runCmd(path.join('vendor', 'bin', 'deptrac'), [])) process.exit(1);
            } else {
                log('deptrac not found; skipping');
            }
        } else {
            // sail
            if (fileExists(path.join('vendor', 'bin', 'sail'))) {
                if (!runCmd(path.join('vendor', 'bin', 'sail'), ['composer', 'deptrac'])) process.exit(1);
            } else {
                log('sail not found; skipping deptrac');
            }
        }
    }

    // Tests (optional)
    if (process.env.SKIP_TESTS === '1') {
        log('Skipping tests (SKIP_TESTS=1)');
        process.exit(0);
    }

    log('Running test suite');
    if (runner === 'php') {
        if (!runCmd('php', ['artisan', 'test', '--stop-on-failure'])) process.exit(1);
    } else {
        if (fileExists(path.join('vendor', 'bin', 'sail'))) {
            if (!runCmd(path.join('vendor', 'bin', 'sail'), ['artisan', 'test', '--stop-on-failure'])) process.exit(1);
        } else {
            log('sail not found; cannot run tests');
            process.exit(1);
        }
    }

    log('Tests passed');
    process.exit(0);
}

main();
