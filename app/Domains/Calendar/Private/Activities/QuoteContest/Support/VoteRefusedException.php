<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\QuoteContest\Support;

use RuntimeException;

/**
 * A ballot the service refuses.
 *
 * Like the submission refusals, every one of these is a forged request: the
 * ballot is only rendered during the vote phase, and it only ever offers the
 * live entries of the category it sits in. The controller turns them all into a
 * 403, so the messages below are for the log, not for a screen.
 */
final class VoteRefusedException extends RuntimeException
{
    public static function outsideVotePhase(): self
    {
        return new self('Votes are not open for this contest.');
    }

    public static function unknownCategory(): self
    {
        return new self('No such category.');
    }

    /** Not in that category, or withdrawn — the ballot never offered it. */
    public static function unknownEntry(): self
    {
        return new self('No such entry in this category.');
    }
}
