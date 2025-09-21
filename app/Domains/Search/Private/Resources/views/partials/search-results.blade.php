<div class="search-results" 
  x-data="{storyPage: {{$storiesPage}}, profilesPage: {{$profilesPage}}, perPage: {{$perPage}}, storyTotal: {{$stories['total'] ?? 0}}, profileTotal: {{$profiles['total'] ?? 0}}}"
  >
  @if(($stories['total'] ?? 0) === 0 && ($profiles['total'] ?? 0) === 0)
    <div class="py-4 text-md text-neutral text-center">{{ __('search::results.empty.label') }}</div>

    <div class="py-4 text-xs text-neutral text-center">{{ __('search::results.empty.help') }}</div>

  @else
  <x-shared::tabs :tabs="[
      ['key' => 'stories', 'label' => __('search::results.stories.label') . ' (' . ($stories['total'] ?? 0) . ')'],
      ['key' => 'profiles', 'label' => __('search::results.profiles.label') . ' (' . ($profiles['total'] ?? 0) . ')'],
    ]" color="primary" initial="stories">

    <div x-show="tab==='stories'" x-cloak data-total-stories="{{$stories['total']}}">
      @if(($stories['total'] ?? 0) === 0)
        <div class="py-3 text-sm text-neutral-600">{{ __('search::results.stories.empty') }}</div>
      @else
        <ul role="listbox" class="mt-2 space-y-2">
          @foreach($stories['items'] as $idx => $s)
            <li role="option" x-show="Math.floor({{ $idx }} / perPage) + 1 === storyPage" class="flex items-center gap-3 p-2 rounded hover:bg-neutral-100 cursor-pointer" onclick="window.location='{{ $s->url }}'">
              @if($s->cover_url)
                <img src="{{ $s->cover_url }}" alt="" class="w-10 h-10 object-cover rounded" />
              @else
                <img src="{{ asset('images/story/default-cover.svg') }}" alt="" class="w-10 h-10 object-cover rounded" />
              @endif
              <div class="min-w-0">
                <div class="text-sm font-medium">{!! $s->title !!}</div>
                <div class="text-xs text-neutral-600 truncate">{{ implode(', ', $s->authors) }}</div>
              </div>
            </li>
          @endforeach
        </ul>
        <div class="mt-2 flex items-center justify-between text-xs">
          <x-shared::button
            type="button"
            x-on:click="storyPage = Math.max(1, storyPage - 1)"
            x-bind:disabled="storyPage <= 1"
            class="px-2 py-1 border rounded"
          >
            <span class="material-symbols-outlined">chevron_left</span>
          </x-shared::button>
          <div class="text-xs">{{ __('search::results.page.label') }}</div>
          <x-shared::button
            type="button"
            x-on:click="storyPage = Math.min(5, storyPage + 1)"
            x-bind:disabled="storyPage >= 5 || storyTotal < perPage * storyPage"
            class="px-2 py-1 border rounded"
          >
            <span class="material-symbols-outlined">chevron_right</span>
          </x-shared::button>
        </div>
      @endif
    </div>

    <div x-show="tab==='profiles'" x-cloak data-total-profiles="{{$profiles['total']}}">
      @if(($profiles['total'] ?? 0) === 0)
        <div class="py-3 text-sm text-neutral-600">{{ __('search::results.profiles.empty') }}</div>
      @else
        <ul role="listbox" class="mt-2 space-y-2">
          @foreach($profiles['items'] as $idx => $p)
            <li role="option" x-show="Math.floor({{ $idx }} / perPage) + 1 === profilesPage" class="flex items-center gap-3 p-2 rounded hover:bg-neutral-100 cursor-pointer" onclick="window.location='{{ $p->url }}'">
              @if($p->avatar_url)
                <img src="{{ $p->avatar_url }}" alt="" class="w-8 h-8 object-cover rounded-full" />
              @else
                <div class="w-8 h-8 bg-neutral-200 rounded-full"></div>
              @endif
              <div class="min-w-0">
                <div class="text-sm font-medium">{!! $p->display_name !!}</div>
              </div>
            </li>
          @endforeach
        </ul>
        <div class="mt-2 flex items-center justify-between text-xs">
        <x-shared::button
            type="button"
            x-on:click="profilesPage = Math.max(1, profilesPage - 1)"
            x-bind:disabled="profilesPage <= 1"
            class="px-2 py-1 border rounded"
          >
            <span class="material-symbols-outlined">chevron_left</span>
          </x-shared::button>
          <div class="text-xs">{{ __('search::results.page.label') }}</div>
          <x-shared::button
            type="button"
            x-on:click="profilesPage = Math.min(5, profilesPage + 1)"
            x-bind:disabled="profilesPage >= 5 || profileTotal < perPage * profilesPage"
            class="px-2 py-1 border rounded"
          >
            <span class="material-symbols-outlined">chevron_right</span>
          </x-shared::button>
        </div>
      @endif
    </div>

  </x-shared::tabs>
  @endif
</div>
