@props([
    'story',
    'currentChapterSlug'
])

<div class="flex flex-col items-center gap-2">
    <!-- Cover -->
    <div>
        <x-story::cover :coverType="$story->coverType" :coverUrl="$story->coverUrl" :coverHdUrl="$story->coverHdUrl" :hd="true" :width="230" />
    </div>
    <!-- Title -->
    <div>
        <div class="font-semibold text-xl text-fg truncate-2 hover:underline hover:text-fg/80">
            <a href="{{ url('/stories/'.$story->slug) }}">{{ $story->title }}</a>
        </div>
    </div>
    <!-- Chapter list -->
     <div class="w-full">
         <x-shared::select
             :options="collect($story->chapters)->map(fn($c) => ['id' => $c->slug, 'name' => $c->title])->all()"
             :selected="$currentChapterSlug"
             x-on:select-change="window.location.href='{{ url('/stories/'.$story->slug.'/chapters') }}/' + $event.detail.value"
             placeholder="{{ __('Select a chapter') }}"
         />
     </div>
</div>