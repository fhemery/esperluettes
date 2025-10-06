@props([
    'story',
    'currentChapterSlug'
])

<div class="flex flex-col items-center gap-2">
    <!-- Cover -->
    <div>
        <img src="{{ $story->cover_url ?? asset('images/story/default-cover.svg') }}" alt="{{ $story->title }}" class="w-[230px] object-cover">
    </div>
    <!-- Title -->
    <div>
        <div class="font-semibold text-xl text-fg truncate-2 hover:underline hover:text-fg/80">
            <a href="{{ url('/stories/'.$story->slug) }}">{{ $story->title }}</a>
        </div>
    </div>
    <!-- Chapter list -->
     <div class="w-full">
        <select x-data="{}" class="w-full bg-transparent" @change="window.location.href='{{ url('/stories/'.$story->slug.'/chapters') }}/' + $event.target.value">
            @foreach($story->chapters as $chapter)
                <option class="truncate text-sm" value="{{ $chapter->slug }}" {{ $chapter->slug === $currentChapterSlug ? 'selected' : '' }}>{{ $chapter->title }}</option>
            @endforeach
        </select>
     </div>
</div>