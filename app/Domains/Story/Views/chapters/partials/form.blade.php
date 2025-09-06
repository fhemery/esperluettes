{{-- Shared form partial for Chapter create/edit --}}
<div class="space-y-6">
    <!-- Title -->
    <div>
        <div class="flex items-center gap-2">
            <x-input-label for="title" :value="__('story::chapters.form.title.label')" />
            <span class="text-red-600" aria-hidden="true">*</span>
        </div>
        <x-text-input id="title" name="title" type="text" class="mt-1 block w-full"
                      placeholder="{{ __('story::chapters.form.title.placeholder') }}"
                      value="{{ old('title', $chapter->title ?? '') }}" />
        <x-input-error :messages="$errors->get('title')" class="mt-2" />
    </div>

    <!-- Author Note -->
    <div>
        <div class="flex items-center gap-2">
            <x-input-label for="author_note" :value="__('story::chapters.form.author_note.label')" />
            <x-shared::tooltip type="help" :title="__('story::chapters.form.author_note.label')" placement="right">
                {{ __('story::chapters.form.author_note.help') }}
            </x-shared::tooltip>
        </div>
        <x-shared::editor id="chapter-author-note-editor" name="author_note" 
            :nbLines="5" max="1000" class="mt-1 block w-full" 
            defaultValue="{{ old('author_note', $chapter->author_note ?? '') }}" 
            placeholder="{{ __('story::chapters.form.author_note.placeholder') }}"/>
        <x-input-error :messages="$errors->get('author_note')" class="mt-2" />
    </div>

    <!-- Content -->
    <div>
        <div class="flex items-center gap-2">
            <x-input-label for="content" :value="__('story::chapters.form.content.label')" />
            <span class="text-red-600" aria-hidden="true">*</span>
        </div>
        <x-shared::editor id="chapter-content-editor" name="content" :nbLines="20" class="mt-1 block w-full" defaultValue="{{ old('content', $chapter->content ?? '') }}" />
        <x-input-error :messages="$errors->get('content')" class="mt-2" />
    </div>

    <!-- Published toggle -->
    <div>
        <div class="flex items-center gap-2">
            @php($checked = old('published', isset($chapter) ? ($chapter->status === \App\Domains\Story\Models\Chapter::STATUS_PUBLISHED ? '1' : '') : '1'))
            <div class="mt-2">
                <x-shared::toggle id="published" :label="__('story::chapters.form.published.label')" name="published" :checked="$checked ? true : false" value="1" />
            </div>
            <x-shared::tooltip type="help" :title="__('story::chapters.form.published.help.label')" placement="right">
                {{ __('story::chapters.form.published.help.text') }}
            </x-shared::tooltip>
        </div>
    </div>
</div>
