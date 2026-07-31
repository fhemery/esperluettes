import { spawnSync } from 'node:child_process';
import path from 'node:path';

export const ROOT = path.resolve(path.dirname(new URL(import.meta.url).pathname), '..', '..');

/** Honours LOCAL_RUNNER (php|sail) exactly like scripts/gate.js. */
export function runner(): 'php' | 'sail' {
  return process.env.LOCAL_RUNNER === 'php' ? 'php' : 'sail';
}

/** Run an artisan command against the e2e environment. Returns its output. */
export function artisan(args: string[]): { ok: boolean; output: string } {
  const [cmd, prefix] = runner() === 'sail'
    ? [path.join('vendor', 'bin', 'sail'), ['artisan']]
    : ['php', ['artisan']];

  const res = spawnSync(cmd, [...prefix, ...args, '--env=e2e'], {
    cwd: ROOT,
    encoding: 'utf8',
    stdio: ['inherit', 'pipe', 'pipe'],
  });

  return { ok: res.status === 0, output: `${res.stdout ?? ''}${res.stderr ?? ''}`.trim() };
}

/**
 * Stop the PHP server running *inside* the Sail container.
 *
 * Playwright's webServer only kills the local `sail` wrapper it spawned; the
 * php process in the container survives it and keeps holding the port.
 */
export function stopContainerServer(): void {
  if (runner() !== 'sail') return;
  spawnSync(path.join('vendor', 'bin', 'sail'),
    ['exec', '-T', 'laravel.test', 'pkill', '-f', 'artisan serve'],
    { cwd: ROOT, stdio: 'ignore' });
}
