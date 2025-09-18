#!/usr/bin/env node
/*
  Husky pre-commit hook implemented in Node.js (ESM)
  - Reads LOCAL_RUNNER from environment or .env (php|sail). Defaults to 'sail'.
  - Blocks adding new files under forbidden prefixes unless bypassed.
  - Optionally runs Deptrac (skip with SKIP_DEPTRAC=1).
  - Runs test suite (skip with SKIP_TESTS=1).
*/

import path from 'path';
import { spawnSync } from 'child_process';
import { runStagedTests } from './launch_staged_tests.js';
import { makeLog, fileExists, runCmd as runCmdBase, determineRunner } from './utils.js';

const log = makeLog('husky');
function runCmd(cmd, args, opts = {}) {
    log(`Running ${cmd} ${(args||[]).join(' ')}`);
    const ok = runCmdBase(cmd, args, opts);
    log(`Result of command ${cmd} ${(args||[]).join(' ')}: ${ok ? 0 : 1}`);
    return ok;
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
    // Determine runner (shared logic)
    let runner = determineRunner();

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

    // Delegate test execution to the staged tests launcher (imported)
    try {
        log('Delegating tests to scripts/launch_staged_tests.js (import call)');
        const code = runStagedTests();
        if (code !== 0) process.exit(code);
    } catch (e) {
        // Fallback to previous behavior if launcher is missing
        log('Staged tests launcher not found; running full test suite');
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
    }

    log('Pre-commit checks passed');
    process.exit(0);
}

main();
