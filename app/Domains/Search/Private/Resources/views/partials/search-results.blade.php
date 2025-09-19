<div class="search-results" x-data="{storiesPage: {{$storiesPage}}, profilesPage: {{$profilesPage}}, perPage: {{$perPage}}}">
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    <section aria-labelledby="stories-label" data-total-stories="{{$stories['total']}}">
      <header class="flex items-center gap-2">
        <h3 id="stories-label" class="font-semibold">{{ __('search::results.stories.label') }} ({{ $stories['total'] }})</h3>
      </header>
      <ul role="listbox" class="mt-2 space-y-2">
        @foreach($stories['items'] as $idx => $s)
          <li role="option" x-show="Math.floor({{ $idx }} / perPage) + 1 === storiesPage" class="flex items-center gap-3 p-2 rounded hover:bg-neutral-100 cursor-pointer" onclick="window.location='{{ $s->url }}'">
            @if($s->cover_url)
              <img src="{{ $s->cover_url }}" alt="" class="w-10 h-10 object-cover rounded" />
            @else
              <div class="w-10 h-10 bg-neutral-200 rounded"></div>
            @endif
            <div class="min-w-0">
              <div class="text-sm font-medium">{!! $s->title !!}</div>
              <div class="text-xs text-neutral-600 truncate">{{ implode(', ', $s->authors) }}</div>
            </div>
          </li>
        @endforeach
      </ul>
      <div class="mt-2 flex items-center justify-between text-xs">
        <button type="button" @click="storiesPage=Math.max(1, storiesPage-1)" :disabled="storiesPage<=1" class="px-2 py-1 border rounded">{{ __('search::results.page.prev') }}</button>
        <span>{{ __('search::results.page.label') }} <span x-text="storiesPage"></span></span>
        <button type="button" @click="storiesPage=Math.min(5, storiesPage+1)" :disabled="storiesPage>=5" class="px-2 py-1 border rounded">{{ __('search::results.page.next') }}</button>
      </div>
    </section>

    <section aria-labelledby="profiles-label" data-total-profiles="{{$profiles['total']}}">
      <header class="flex items-center gap-2">
        <h3 id="profiles-label" class="font-semibold">{{ __('search::results.profiles.label') }} ({{ $profiles['total'] }})</h3>
      </header>
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
        <button type="button" @click="profilesPage=Math.max(1, profilesPage-1)" :disabled="profilesPage<=1" class="px-2 py-1 border rounded">{{ __('search::results.page.prev') }}</button>
        <span>{{ __('search::results.page.label') }} <span x-text="profilesPage"></span></span>
        <button type="button" @click="profilesPage=Math.min(5, profilesPage+1)" :disabled="profilesPage>=5" class="px-2 py-1 border rounded">{{ __('search::results.page.next') }}</button>
      </div>
    </section>
  </div>
</div>
