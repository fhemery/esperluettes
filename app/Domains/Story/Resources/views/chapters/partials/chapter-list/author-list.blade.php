@php($chapters = $chapters ?? ($viewModel->chapters ?? []))
<ul class="divide-y divide-gray-200 rounded-md border border-gray-200 bg-white" x-ref="readonlyList">
    @foreach($chapters as $c)
        <li class="p-3 flex items-center justify-between gap-3"
            data-id="{{ $c->id }}"
            data-title="{{ $c->title }}"
            data-slug="{{ $c->slug }}"
            data-url="{{ $c->url }}"
            data-is-draft="{{ $c->isDraft ? '1' : '0' }}"
            data-reads-logged="{{ $c->readsLogged }}"
            data-edit-url="{{ route('chapters.edit', ['storySlug' => $story->slug, 'chapterSlug' => $c->slug]) }}"
            data-delete-url="{{ route('chapters.destroy', ['storySlug' => $story->slug, 'chapterSlug' => $c->slug]) }}">
            <div class="flex items-center gap-3">
                <a href="{{ $c->url }}" class="text-indigo-700 hover:text-indigo-900 font-medium">{{ $c->title }}</a>
                @if($c->isDraft)
                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-800 ring-1 ring-inset ring-gray-300"
                          aria-label="{{ __('story::chapters.list.draft') }}">{{ __('story::chapters.list.draft') }}</span>
                @endif
            </div>
            <div class="flex items-center gap-3">
                <x-shared::popover placement="top" width="16rem">
                    <x-slot name="trigger">
                        <span class="inline-flex items-center gap-1 rounded-full bg-white px-2 py-0.5 text-xs font-medium text-gray-900 ring-1 ring-inset ring-gray-300">
                            <span class="material-symbols-outlined text-[16px] leading-none">visibility</span>
                            <span>{{ number_format($c->readsLogged, 0, ',', ' ') }}</span>
                        </span>
                    </x-slot>
                    <div class="font-semibold text-gray-900">{{ __('story::chapters.reads.label') }}</div>
                    <div class="text-gray-700">{{ __('story::chapters.reads.tooltip') }}</div>
                </x-shared::popover>

                <a href="{{ route('chapters.edit', ['storySlug' => $story->slug, 'chapterSlug' => $c->slug]) }}"
                   class="inline-flex items-center gap-1 text-gray-500 hover:text-gray-700"
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
