<?php

namespace App\Domains\StoryRef\Public\Api;

use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\StoryRef\Private\Models\StoryRefGenre;
use App\Domains\StoryRef\Private\Models\StoryRefAudience;
use App\Domains\StoryRef\Private\Services\GenreRefService;
use App\Domains\StoryRef\Private\Services\AudienceRefService;
use App\Domains\StoryRef\Public\Contracts\GenreDto;
use App\Domains\StoryRef\Public\Contracts\GenreWriteDto;
use App\Domains\StoryRef\Public\Contracts\AudienceDto;
use App\Domains\StoryRef\Public\Contracts\AudienceWriteDto;
use App\Domains\StoryRef\Public\Contracts\StoryRefFilterDto;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;

class StoryRefPublicApi
{
    public function __construct(
        private readonly AuthPublicApi $auth,
        private readonly GenreRefService $genreRefService,
        private readonly AudienceRefService $audienceRefService,
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
     * @throws AuthorizationException
     */
    private function assertAdminOrTechAdmin(): void
    {
        if (! $this->auth->hasAnyRole([Roles::ADMIN, Roles::TECH_ADMIN])) {
            throw new AuthorizationException('You are not authorized to manage story references.');
        }
    }
}
