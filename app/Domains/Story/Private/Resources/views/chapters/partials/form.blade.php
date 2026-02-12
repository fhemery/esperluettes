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
                :nbLines="5" max="1000" :withLinks="true" class="mt-1 block w-full"
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
            :nbLines="15" :withLinks="true" class="mt-1 block w-full" defaultValue="{{ old('content', $chapter->content ?? '') }}"
            :indentParagraphs="true"  />
        <x-input-error :messages="$errors->get('content')" class="mt-2" />
    </div>

    <!-- Published toggle -->
    <div>
        <div class="flex items-center gap-2">
            @php($checked = old('published', isset($chapter) ? ($chapter->status === \App\Domains\Story\Private\Models\Chapter::STATUS_PUBLISHED ? '1' : '') : '1'))
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
    </div>
</div>