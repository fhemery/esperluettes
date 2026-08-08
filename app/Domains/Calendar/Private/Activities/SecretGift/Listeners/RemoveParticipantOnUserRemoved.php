<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\SecretGift\Listeners;

use App\Domains\Auth\Public\Events\UserDeactivated;
use App\Domains\Auth\Public\Events\UserDeleted;
use App\Domains\Calendar\Private\Activities\SecretGift\Services\SecretGiftService;

/**
 * A user who goes away before the shuffle is un-enrolled automatically
 * (functional spec §5). Deactivation and deletion are treated identically:
 * either way the person will not be there to give or receive a gift, and the
 * enrolment is still undoable at that point.
 */
final class RemoveParticipantOnUserRemoved
{
    public function __construct(
        private readonly SecretGiftService $participants,
    ) {}

    public function handle(UserDeleted|UserDeactivated $event): void
    {
        $this->participants->removeParticipantsForUser($event->userId);
    }
}
