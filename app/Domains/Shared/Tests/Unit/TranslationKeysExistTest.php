<?php

use Illuminate\Support\Facades\Lang;
use Tests\TestCase;

uses(TestCase::class);

/**
 * Guards against namespaced translation keys that no lang file defines.
 *
 * `.env.testing` sets `APP_LOCALE=zz` so that tests assert on raw keys, which
 * means asserting on a translated string compares a key to a key and passes
 * even when the key does not exist — or when its namespace is not registered
 * at all. This test is the counterweight: it pins the locale to `fr` and checks
 * every statically-written key resolves.
 *
 * Limits, deliberately: only *static* keys are covered. Where a key is built by
 * concatenation at runtime, the literal part is only a fragment, so the call is
 * skipped rather than half-checked. Nothing here validates that a translation
 * says the right thing, only that it exists.
 */

/** Directories scanned for translation calls. */
const SCAN_DIRS = ['app/Domains', 'resources/views'];

/**
 * Captures the key argument of the four translation helpers.
 *
 * Group 2 is the key. Group 3 is a trailing `.` operator, present only when the
 * literal is concatenated with something — that is the signal the key is
 * dynamic, and it beats guessing from the key's shape (a fragment can end in a
 * dot, an underscore, or nothing at all).
 *
 * The `ns::` prefix is required: unnamespaced keys resolve against `lang/` at
 * the project root and are out of scope.
 */
const KEY_PATTERN = '/(?:__|trans|trans_choice|@lang)\(\s*([\'"])([a-z0-9_-]+::[A-Za-z0-9_.\-]+)\1(\s*\.)?/';

/**
 * @return array<string, list<string>> key => relative paths that use it
 */
function collectStaticTranslationKeys(): array
{
    $keys = [];

    foreach (SCAN_DIRS as $dir) {
        $root = base_path($dir);

        if (! is_dir($root)) {
            continue;
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $contents = file_get_contents($file->getPathname());

            if (! preg_match_all(KEY_PATTERN, $contents, $matches, PREG_SET_ORDER)) {
                continue;
            }

            $relative = str_replace(base_path() . '/', '', $file->getPathname());

            foreach ($matches as $match) {
                // Concatenated: the captured literal is a prefix, not a key.
                if (($match[3] ?? '') !== '') {
                    continue;
                }

                $keys[$match[2]][] = $relative;
            }
        }
    }

    return $keys;
}

it('resolves every statically-written namespaced translation key in fr', function () {
    app()->setLocale('fr');

    $keys = collectStaticTranslationKeys();

    // Guards the scanner itself: a broken regex or a bad path would otherwise
    // let this test pass by finding nothing at all.
    expect(count($keys))->toBeGreaterThan(1000);

    $missing = [];

    foreach ($keys as $key => $files) {
        if (! Lang::has($key, 'fr')) {
            $missing[] = sprintf('  %s — used in %s', $key, implode(', ', array_unique($files)));
        }
    }

    expect($missing)->toBe([], sprintf(
        "%d translation key(s) have no definition in fr:\n%s",
        count($missing),
        implode("\n", $missing)
    ));
});
