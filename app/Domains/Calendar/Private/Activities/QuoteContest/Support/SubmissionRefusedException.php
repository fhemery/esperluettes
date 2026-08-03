<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\QuoteContest\Support;

use RuntimeException;

/**
 * A submit or withdraw request the service refuses.
 *
 * Every one of these is a forged request: the reader page never offers the
 * action outside its phase, never offers somebody else's quote, and greys the
 * ineligible ones. The controller turns them all into a 403, so the messages
 * below are for the log, not for a screen — nothing here is user-visible.
 */
final class SubmissionRefusedException extends RuntimeException
{
    public static function outsideSubmissionPhase(): self
    {
        return new self('Submissions are not open for this contest.');
    }

    public static function unknownCategory(): self
    {
        return new self('No such category in this contest.');
    }

    public static function quoteNotOwned(): self
    {
        return new self('The quote does not belong to the submitter.');
    }

    public static function ineligibleQuote(string $reason): self
    {
        return new self('The quote is not eligible: ' . $reason . '.');
    }

    /** The chapter row is gone, so there is no title or slug to snapshot. */
    public static function unresolvableChapter(): self
    {
        return new self('The quoted chapter no longer resolves.');
    }

    public static function entryNotOwned(): self
    {
        return new self('The entry does not belong to the caller, or is already withdrawn.');
    }

    /** Moderation was pointed at an entry that does not exist. */
    public static function unknownEntry(): self
    {
        return new self('No such entry.');
    }
}
