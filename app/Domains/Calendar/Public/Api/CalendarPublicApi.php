<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Public\Api;

use App\Domains\Calendar\Public\Contracts\ActivityToCreateDto;
use App\Domains\Calendar\Public\Contracts\ActivityDto;
use App\Domains\Calendar\Private\Services\ActivityService;
use App\Domains\Calendar\Private\Models\Activity;
use App\Domains\Calendar\Private\Requests\ActivityCreateRequest;
use App\Domains\Calendar\Private\Requests\ActivityUpdateRequest;
use App\Domains\Calendar\Public\Contracts\ActivityState;
use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Auth\Public\Api\Roles;
use Illuminate\Validation\UnauthorizedException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CalendarPublicApi
{
    public function __construct(
        private CalendarRegistry $registry,
        private ActivityService $activities,
        private ActivityCreateRequest $createRequest,
        private ActivityUpdateRequest $updateRequest,
        private AuthPublicApi $auth,
    ) {}

    /**
     * Create an activity. Returns the new activity id.
     *
     * @throws UnauthorizedException
     * @throws ValidationException
     */
    public function create(ActivityToCreateDto $dto, int $actorUserId): int
    {
        // Authorization: admin or tech-admin only
        if (! $this->auth->hasAnyRole([Roles::ADMIN, Roles::TECH_ADMIN])) {
            throw new UnauthorizedException('Not allowed');
        }

        // Validation via request
        $payload = $this->createRequest->validate($dto, $this->registry);

        // Fill defaults & actor
        $payload['role_restrictions'] = $payload['role_restrictions'] ?? [Roles::USER, Roles::USER_CONFIRMED];
        $payload['created_by_user_id'] = $actorUserId;

        $activity = $this->activities->create($payload);

        return $activity->id;
    }

    /**
     * Get a single activity by id, enforcing visibility rules.
     *
     * @throws NotFoundHttpException when not visible
     */
    public function getOne(int $id, ?int $actorUserId): ActivityDto
    {
        /** @var Activity|null $a */
        $a = $this->activities->findById($id);
        if (!$a) {
            throw new NotFoundHttpException();
        }

        $state = (string) $a->state;
        if ($state === ActivityState::DRAFT) {
            if (! $this->auth->hasAnyRole([Roles::ADMIN, Roles::TECH_ADMIN])) {
                throw new NotFoundHttpException();
            }
        } else {
            if (! $this->auth->hasAnyRole([Roles::USER, Roles::USER_CONFIRMED, Roles::ADMIN, Roles::TECH_ADMIN])) {
                throw new NotFoundHttpException();
            }
        }

        return ActivityDto::fromModel($a);
    }

    /**
     * Full replace update. Returns nothing.
     *
     * @throws UnauthorizedException|ValidationException|NotFoundHttpException
     */
    public function update(int $id, \App\Domains\Calendar\Public\Contracts\ActivityToUpdateDto $dto, int $actorUserId): void
    {
        // Auth: admin or tech-admin only
        if (! $this->auth->hasAnyRole([Roles::ADMIN, Roles::TECH_ADMIN])) {
            throw new UnauthorizedException('Not allowed');
        }

        $activity = $this->activities->findById($id);
        if (! $activity) {
            throw new NotFoundHttpException();
        }

        // Enforce immutability of activity_type
        if ($dto->activity_type !== $activity->activity_type) {
            throw ValidationException::withMessages(['activity_type' => ['Activity type cannot be changed after creation.']]);
        }

        // Validate payload
        $payload = $this->updateRequest->validate($dto, $this->registry);

        // Preserve immutable fields
        $payload['activity_type'] = $activity->activity_type;

        $this->activities->update($activity, $payload);
    }

    /**
     * Hard delete the activity. Returns nothing.
     *
     * @throws UnauthorizedException|NotFoundHttpException
     */
    public function delete(int $id, int $actorUserId): void
    {
        // Auth
        if (! $this->auth->hasAnyRole([Roles::ADMIN, Roles::TECH_ADMIN])) {
            throw new UnauthorizedException('Not allowed');
        }

        $activity = $this->activities->findById($id);
        if (! $activity) {
            throw new NotFoundHttpException();
        }

        $this->activities->delete($activity);
    }
}
