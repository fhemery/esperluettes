# Fix 500 on 404 page when a model-bound route misses — request

*Written by the user. Free form, may be three lines. Everything below is
optional prompting, not a form to fill.*

## What I want

Fix the 500 "Undefined variable $theme" raised by `errors/404.blade.php:38` when a model-bound route misses (e.g. `/fanarts/9999`, `/profile/nobody-xx`). `$theme` is not shared in that context. `/stories/zzz-9999` returns a proper 404.

## Why

Pre-existing bug, found while verifying fanart-artwork-module. A mistyped or deleted URL shows users a 500 instead of the 404 page.

## Constraints or ideas I already have

None.

## Explicitly out of scope

None.
