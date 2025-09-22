@php($chapters = $chapters ?? ($viewModel->chapters ?? []))
<ul class="divide-y divide-gray-200 rounded-md border border-gray-200 bg-white" x-ref="readonlyList">
    @foreach($chapters as $c)
        <li class="p-3 flex items-center justify-between gap-2"
            data-id="{{ $c->id }}"
            data-title="{{ $c->title }}"
            data-slug="{{ $c->slug }}"
            data-url="{{ $c->url }}"
            data-is-draft="{{ $c->isDraft ? '1' : '0' }}"
            data-reads-logged="{{ $c->readsLogged }}"
            data-edit-url="{{ route('chapters.edit', ['storySlug' => $story->slug, 'chapterSlug' => $c->slug]) }}"
            data-delete-url="{{ route('chapters.destroy', ['storySlug' => $story->slug, 'chapterSlug' => $c->slug]) }}">
            <div class="flex items-center gap-2 flex-1 min-w-0">
                <a href="{{ $c->url }}" class="flex-1 truncate text-indigo-700 hover:text-indigo-900 font-medium">{{ $c->title }}</a>
                @if($c->isDraft)
                    <x-shared::popover placement="top">
                        <x-slot name="trigger">
                            <span class="material-symbols-outlined text-[18px] leading-none shrink-0">draft</span>
                        </x-slot>
                        <p>{{ __('story::chapters.list.draft') }}</p>
                    </x-shared::popover>
                @endif
            </div>
            <div class="flex items-center gap-1 sm:gap-2 shrink-0">
                <div class="min-w-[60px] flex flex-start">
                    <x-shared::metric-badge
                        icon="visibility"
                        :value="$c->readsLogged"
                        :label="__('story::chapters.reads.label')"
                        :tooltip="__('story::chapters.reads.tooltip')"
                    />
                </div>

                <div class="min-w-[60px] flex flex-start">
                <x-story::words-metric-badge
                    :nb-words="$c->wordCount"
                    :nb-characters="$c->characterCount"
                />
                </div>

                <a href="{{ route('chapters.edit', ['storySlug' => $story->slug, 'chapterSlug' => $c->slug]) }}"
                   class="inline-flex items-center text-gray-500 hover:text-gray-700"
                   title="{{ __('story::chapters.actions.edit') }}"
                   aria-label="{{ __('story::chapters.actions.edit') }}">
                   <span class="material-symbols-outlined text-[18px] leading-none">edit</span>
                </a>
                <button type="button"
                        class="inline-flex items-center gap-1 text-red-600 hover:text-red-800"
                        title="{{ __('story::chapters.actions.delete') }}"
                        aria-label="{{ __('story::chapters.actions.delete') }}"
                        x-data
                        x-on:click="$dispatch('open-modal', 'confirm-delete-chapter-{{ $c->id }}')">
                    <span class="material-symbols-outlined text-[18px] leading-none">delete</span>
                </button>
            </div>
        </li>
        <x-shared::confirm-modal
            name="confirm-delete-chapter-{{ $c->id }}"
            :title="__('story::chapters.actions.delete')"
            :body="__('story::show.chapter.confirm_delete_warning')"
            :cancel="__('story::show.cancel')"
            :confirm="__('story::show.chapter.confirm_delete')"
            :action="route('chapters.destroy', ['storySlug' => $story->slug, 'chapterSlug' => $c->slug])"
            method="DELETE"
            maxWidth="md"
        />
    @endforeach
</ul>
