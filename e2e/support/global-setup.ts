import { artisan } from './sail';

/**
 * Rebuild the throwaway e2e database before the run.
 *
 * Cheap on SQLite, and it is the whole reason specs can treat the values in
 * fixtures.ts as constants: whatever the last run did to the data is gone.
 */
export default async function globalSetup(): Promise<void> {
  if (process.env.E2E_SKIP_RESET) {
    console.log('  e2e  reset skipped (E2E_SKIP_RESET)');
    return;
  }

  console.log('  e2e  rebuilding the e2e database…');
  const { ok, output } = artisan(['migrate:fresh', '--seed', '--force']);
  if (!ok) {
    throw new Error(`Could not reset the e2e database:\n${output}`);
  }

  // The e2e cache is a file store that outlives the database: values cached
  // by the last run (feature toggles, config parameters) would otherwise mask
  // what the seeders just wrote.
  const cleared = artisan(['cache:clear']);
  if (!cleared.ok) {
    throw new Error(`Could not clear the e2e cache:\n${cleared.output}`);
  }
}
