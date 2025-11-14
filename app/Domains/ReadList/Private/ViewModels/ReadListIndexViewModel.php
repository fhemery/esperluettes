<?php

namespace App\Domains\ReadList\Private\ViewModels;

use App\Domains\Story\Public\Contracts\StoriesPaginationDto;
use App\Domains\Story\Public\Contracts\PaginatedStoryDto;
use Illuminate\Support\Collection;

class ReadListIndexViewModel
{
    /** @var StoriesPaginationDto|null */
    public $pagination = null;
    /** @var Collection<int, ReadListStoryViewModel> */
    public Collection $stories;
    /** @var Collection<int, array{id:int,name:string,description:?string}> */
    public Collection $genres;

    public function __construct()
    {
        $this->stories = collect();
        $this->genres = collect();
    }

    public static function fromPaginated(PaginatedStoryDto $paginated): self
    {
        $vm = new self();
        $vm->pagination = $paginated->pagination;
        $vm->stories = collect($paginated->data)->map(function ($dto) {
            return ReadListStoryViewModel::fromDto($dto);
        })->values();
        return $vm;
    }

    public static function empty(): self
    {
        $vm = new self();
        $vm->pagination = new StoriesPaginationDto(
            current_page: 1,
            per_page: 10,
            total: 0,
            last_page: 1
        );
        return $vm;
    }
}
