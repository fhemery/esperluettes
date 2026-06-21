{{-- Shared form partial for Chapter create/edit --}}
<div class="flex flex-col gap-4">
    <!-- Title -->
    <div class="flex flex-col">
        <x-input-label for="title" size="md" required="true" color="secondary" :value="__('story::chapters.form.title.label')" />
        <x-text-input id="title" name="title" type="text" class="mt-1 block w-full"
            placeholder="{{ __('story::chapters.form.title.placeholder') }}"
            value="{{ old('title', $chapter->title ?? '') }}" />
        <x-input-error :messages="$errors->get('title')" class="mt-2" />
    </div>

    <!-- Author Note -->
    <div x-data="{ showAuthorNote: {{ old('author_note', $chapter->author_note ?? '') ? 'true' : 'false' }} }">
        <div x-show="showAuthorNote" class="flex flex-col gap-1">
            <x-input-label for="author_note" size="md" color="secondary" :value="__('story::chapters.form.author_note.label')" />
            <x-shared::editor id="chapter-author-note-editor" name="author_note"
                :nbLines="5" max="1000"
                :toolbar="['bold','italic','underline','strike','blockquote','align','list','custom-emoji','link','spoiler']"
                class="mt-1 block w-full"
                defaultValue="{{ old('author_note', $chapter->author_note ?? '') }}"
                placeholder="{{ __('story::chapters.form.author_note.placeholder') }}"/>
            <x-input-error :messages="$errors->get('author_note')" class="mt-2" />
        </div>
        <div x-show="!showAuthorNote">
            <x-shared::button color="neutral" :outline="true" icon="add" type="button" @click="showAuthorNote = true">{{ __('story::chapters.form.author_note.add') }}</x-shared::button>
        </div>
    </div>

    <!-- Content -->
    <div>
        <x-input-label for="content" required="true" size="md" color="secondary" :value="__('story::chapters.form.content.label')" />
        <x-shared::editor id="chapter-content-editor" name="content"
            :nbLines="15"
            :toolbar="['bold','italic','underline','strike','blockquote','align','list','custom-emoji','link']"
            class="mt-1 block w-full" defaultValue="{{ old('content', $chapter->content ?? '') }}"
            :indentParagraphs="true"  />
        <x-input-error :messages="$errors->get('content')" class="mt-2" />
    </div>

    <!-- Published toggle + scheduled publication -->
    @php
        $checked = old('published', isset($chapter) ? ($chapter->status === \App\Domains\Story\Private\Models\Chapter::STATUS_PUBLISHED ? '1' : '') : '1');
        $existingPublishAt = old('publish_at', isset($chapter) && $chapter->publish_at ? $chapter->publish_at->format('Y-m-d\TH:i') : '');
    @endphp
    <div
        x-data="{
            published: {{ $checked ? 'true' : 'false' }},
            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
            publishAtLocal: '{{ $existingPublishAt }}' ? DateUtils.utcToLocalInput('{{ $existingPublishAt }}') : '',
        }"
        x-on:change="if ($event.target.name === 'published') published = $event.target.checked"
    >
        <!-- Single row: toggle + inline date picker on sm+ -->
        <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4">

            <!-- Toggle + help tooltip -->
            <div class="flex items-center gap-2 shrink-0">
                <x-shared::toggle id="published"
                    :label="__('story::chapters.form.published.label')"
                    name="published"
                    :checked="$checked ? true : false"
                    value="1"
                    btnColor="accent"
                    textColor="secondary" />
                <x-shared::tooltip type="help" :title="__('story::chapters.form.published.help.label')" placement="right">
                    {{ __('story::chapters.form.published.help.text') }}
                </x-shared::tooltip>
            </div>

            <!-- Inline date picker — appears when toggle is OFF -->
            <div x-show="!published" x-cloak class="flex items-center gap-2 sm:flex-1">
                <span class="material-symbols-outlined text-secondary text-[18px] leading-none shrink-0">schedule</span>
                <input
                    type="datetime-local"
                    id="publish_at"
                    name="publish_at"
                    class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-accent focus:ring-accent text-sm"
                    x-model="publishAtLocal"
                    :min="new Date(Date.now() + 60000).toISOString().slice(0, 16)"
                />
                <input type="hidden" name="timezone" :value="timezone" />
            </div>
        </div>

        <!-- Validation error + help below -->
        <div x-show="!published" x-cloak class="mt-1 pl-0 sm:pl-0">
            <x-input-error :messages="$errors->get('publish_at')" class="mt-1" />
            <p class="text-xs text-secondary mt-1">{{ __('story::chapters.form.publish_at.help') }}</p>
        </div>
    </div>
</div>