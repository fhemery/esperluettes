<?php

namespace App\Domains\StoryRef\Public\Api;

use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\StoryRef\Private\Models\StoryRefGenre;
use App\Domains\StoryRef\Private\Models\StoryRefAudience;
use App\Domains\StoryRef\Private\Models\StoryRefStatus;
use App\Domains\StoryRef\Private\Models\StoryRefFeedback;
use App\Domains\StoryRef\Private\Services\GenreRefService;
use App\Domains\StoryRef\Private\Services\AudienceRefService;
use App\Domains\StoryRef\Private\Services\StatusRefService;
use App\Domains\StoryRef\Private\Services\FeedbackRefService;
use App\Domains\StoryRef\Public\Contracts\GenreDto;
use App\Domains\StoryRef\Public\Contracts\GenreWriteDto;
use App\Domains\StoryRef\Public\Contracts\AudienceDto;
use App\Domains\StoryRef\Public\Contracts\AudienceWriteDto;
use App\Domains\StoryRef\Public\Contracts\StatusDto;
use App\Domains\StoryRef\Public\Contracts\StatusWriteDto;
use App\Domains\StoryRef\Public\Contracts\FeedbackDto;
use App\Domains\StoryRef\Public\Contracts\FeedbackWriteDto;
use App\Domains\StoryRef\Public\Contracts\StoryRefFilterDto;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;

class StoryRefPublicApi
{
    public function __construct(
        private readonly AuthPublicApi $auth,
        private readonly GenreRefService $genreRefService,
        private readonly AudienceRefService $audienceRefService,
        private readonly StatusRefService $statusRefService,
        private readonly FeedbackRefService $feedbackRefService,
    ) {}

    /**
     * @return Collection<int, GenreDto>
     */
    public function getAllGenres(?StoryRefFilterDto $filter = null): Collection
    {
        $dtos = $this->genreRefService->getAll()
            ->map(fn (StoryRefGenre $model) => GenreDto::fromModel($model));

        $filter = $filter ?? new StoryRefFilterDto();

        if ($filter->activeOnly) {
            $dtos = $dtos->filter(fn (GenreDto $dto) => $dto->is_active);
        }

        return $dtos->values();
    }
    public function getGenreById(int $id): ?GenreDto
    {
        $model = $this->genreRefService->getOneById($id);

        return $model ? GenreDto::fromModel($model) : null;
    }

    public function getGenreBySlug(string $slug): ?GenreDto
    {
        $model = $this->genreRefService->getOneBySlug($slug);

        return $model ? GenreDto::fromModel($model) : null;
    }

    public function createGenre(GenreWriteDto $input): GenreDto
    {
        $this->assertAdminOrTechAdmin();
        $model = $this->genreRefService->create([
            'name' => $input->name,
            'slug' => $input->slug,
            'description' => $input->description,
            'is_active' => $input->is_active,
            'order' => $input->order,
        ]);

        return GenreDto::fromModel($model);
    }

    public function updateGenre(int $id, GenreWriteDto $input): ?GenreDto
    {
        $this->assertAdminOrTechAdmin();
        $model = $this->genreRefService->update($id, [
            'name' => $input->name,
            'slug' => $input->slug,
            'description' => $input->description,
            'is_active' => $input->is_active,
            'order' => $input->order,
        ]);

        return $model ? GenreDto::fromModel($model) : null;
    }

    public function deleteGenre(int $id): bool
    {
        $this->assertAdminOrTechAdmin();

        return $this->genreRefService->delete($id);
    }

    /**
     * @return Collection<int, AudienceDto>
     */
    public function getAllAudiences(?StoryRefFilterDto $filter = null): Collection
    {
        $dtos = $this->audienceRefService->getAll()
            ->map(fn (StoryRefAudience $model) => AudienceDto::fromModel($model));

        $filter = $filter ?? new StoryRefFilterDto();

        if ($filter->activeOnly) {
            $dtos = $dtos->filter(fn (AudienceDto $dto) => $dto->is_active);
        }

        return $dtos->values();
    }

    public function getAudienceById(int $id): ?AudienceDto
    {
        $model = $this->audienceRefService->getOneById($id);

        return $model ? AudienceDto::fromModel($model) : null;
    }

    public function getAudienceBySlug(string $slug): ?AudienceDto
    {
        $model = $this->audienceRefService->getOneBySlug($slug);

        return $model ? AudienceDto::fromModel($model) : null;
    }

    public function createAudience(AudienceWriteDto $input): AudienceDto
    {
        $this->assertAdminOrTechAdmin();
        $model = $this->audienceRefService->create([
            'name' => $input->name,
            'slug' => $input->slug,
            'is_active' => $input->is_active,
            'order' => $input->order,
        ]);

        return AudienceDto::fromModel($model);
    }

    public function updateAudience(int $id, AudienceWriteDto $input): ?AudienceDto
    {
        $this->assertAdminOrTechAdmin();
        $model = $this->audienceRefService->update($id, [
            'name' => $input->name,
            'slug' => $input->slug,
            'is_active' => $input->is_active,
            'order' => $input->order,
        ]);

        return $model ? AudienceDto::fromModel($model) : null;
    }

    public function deleteAudience(int $id): bool
    {
        $this->assertAdminOrTechAdmin();

        return $this->audienceRefService->delete($id);
    }

    /**
     * @return Collection<int, StatusDto>
     */
    public function getAllStatuses(?StoryRefFilterDto $filter = null): Collection
    {
        $dtos = $this->statusRefService->getAll()
            ->map(fn (StoryRefStatus $model) => StatusDto::fromModel($model));

        $filter = $filter ?? new StoryRefFilterDto();

        if ($filter->activeOnly) {
            $dtos = $dtos->filter(fn (StatusDto $dto) => $dto->is_active);
        }

        return $dtos->values();
    }

    public function getStatusById(int $id): ?StatusDto
    {
        $model = $this->statusRefService->getOneById($id);

        return $model ? StatusDto::fromModel($model) : null;
    }

    public function getStatusBySlug(string $slug): ?StatusDto
    {
        $model = $this->statusRefService->getOneBySlug($slug);

        return $model ? StatusDto::fromModel($model) : null;
    }

    public function createStatus(StatusWriteDto $input): StatusDto
    {
        $this->assertAdminOrTechAdmin();
        $model = $this->statusRefService->create([
            'name' => $input->name,
            'slug' => $input->slug,
            'description' => $input->description,
            'is_active' => $input->is_active,
            'order' => $input->order,
        ]);

        return StatusDto::fromModel($model);
    }

    public function updateStatus(int $id, StatusWriteDto $input): ?StatusDto
    {
        $this->assertAdminOrTechAdmin();
        $model = $this->statusRefService->update($id, [
            'name' => $input->name,
            'slug' => $input->slug,
            'description' => $input->description,
            'is_active' => $input->is_active,
            'order' => $input->order,
        ]);

        return $model ? StatusDto::fromModel($model) : null;
    }

    public function deleteStatus(int $id): bool
    {
        $this->assertAdminOrTechAdmin();

        return $this->statusRefService->delete($id);
    }

    /**
     * @return Collection<int, FeedbackDto>
     */
    public function getAllFeedbacks(?StoryRefFilterDto $filter = null): Collection
    {
        $dtos = $this->feedbackRefService->getAll()
            ->map(fn (StoryRefFeedback $model) => FeedbackDto::fromModel($model));

        $filter = $filter ?? new StoryRefFilterDto();

        if ($filter->activeOnly) {
            $dtos = $dtos->filter(fn (FeedbackDto $dto) => $dto->is_active);
        }

        return $dtos->values();
    }

    public function getFeedbackById(int $id): ?FeedbackDto
    {
        $model = $this->feedbackRefService->getOneById($id);

        return $model ? FeedbackDto::fromModel($model) : null;
    }

    public function getFeedbackBySlug(string $slug): ?FeedbackDto
    {
        $model = $this->feedbackRefService->getOneBySlug($slug);

        return $model ? FeedbackDto::fromModel($model) : null;
    }

    public function createFeedback(FeedbackWriteDto $input): FeedbackDto
    {
        $this->assertAdminOrTechAdmin();
        $model = $this->feedbackRefService->create([
            'name' => $input->name,
            'slug' => $input->slug,
            'description' => $input->description,
            'is_active' => $input->is_active,
            'order' => $input->order,
        ]);

        return FeedbackDto::fromModel($model);
    }

    public function updateFeedback(int $id, FeedbackWriteDto $input): ?FeedbackDto
    {
        $this->assertAdminOrTechAdmin();
        $model = $this->feedbackRefService->update($id, [
            'name' => $input->name,
            'slug' => $input->slug,
            'description' => $input->description,
            'is_active' => $input->is_active,
            'order' => $input->order,
        ]);

        return $model ? FeedbackDto::fromModel($model) : null;
    }

    public function deleteFeedback(int $id): bool
    {
        $this->assertAdminOrTechAdmin();

        return $this->feedbackRefService->delete($id);
    }

    /**
     * @throws AuthorizationException
     */
    private function assertAdminOrTechAdmin(): void
    {
        if (! $this->auth->hasAnyRole([Roles::ADMIN, Roles::TECH_ADMIN])) {
            throw new AuthorizationException('You are not authorized to manage story references.');
        }
    }
}
