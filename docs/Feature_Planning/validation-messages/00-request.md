# Validation messages — file rules print raw keys — request

## What I want

Validation errors show real French messages app-wide. The app publishes no
`lang/*/validation.php` (there is no `lang/` folder at all), so the `image`
rule prints the raw key `validation.image` and a file's `max:2048` prints
nothing. Hits `ChapterRequest` (`blocks.*.file`) and `NewsRequest` alike.

## Why

An author uploading a too-large or non-image file gets no usable feedback.
Surfaced as defect D4 of `chapters-multi-edit/` (decision #11).

## Constraints or ideas I already have

Fix it once, app-wide — not per request.

## Explicitly out of scope

Reworking the messages domains already define in their own form requests.
