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

    public function __construct()
    {
        $this->stories = collect();
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
}
