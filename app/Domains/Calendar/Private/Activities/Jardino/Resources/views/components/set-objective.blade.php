<form class="grid grid-cols-1 md:grid-cols-2 items-center gap-4" method="post" action="{{ route('jardino.goal.create', ['activity' => $activityId]) }}">
    <p class="col-span-2">{{ __('jardino::details.set_objective.description') }}</p>
    @csrf
    <div>
        <x-shared::input-label for="story_id">{{ __('jardino::details.set_objective.form.story.label') }}</x-shared::label>
        <select name="story_id" id="story_id" class="w-full border px-2 py-1">
            @foreach ($stories as $story)
                <option value="{{ $story->id }}">{{ $story->title }}</option>
            @endforeach
        </select>
        <x-shared::input-error :messages="$errors->get('story_id')" />
    </div>
    <div>
        <x-shared::input-label for="target_word_count">{{ __('jardino::details.set_objective.form.target_word_count.label') }}</x-shared::label>
        <x-shared::text-input type="number" name="target_word_count" min="1" step="1" required />
        <x-shared::input-error :messages="$errors->get('target_word_count')" />
    </div>
    <div>
        <x-shared::button type="submit" color="accent">{{ __('jardino::details.set_objective.form.submit') }}</x-shared::button>
    </div>
</form>
