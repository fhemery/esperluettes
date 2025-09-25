@php($chapters = $chapters ?? ($viewModel->chapters ?? []))
<div class="grid grid-cols-[1fr_auto_auto_auto_auto] items-center gap-2" x-ref="readonlyList">
    @foreach($chapters as $ch)
    <div class="flex items-center gap-2 flex-1 min-w-0 surface-read p-2 text-on-surface">
        <a href="{{ route('chapters.show', ['storySlug' => $story->slug, 'chapterSlug' => $ch->slug]) }}" class="flex-1 truncate text-indigo-700 hover:text-indigo-900 font-medium">{{ $ch->title }}</a>
        @if($ch->isDraft)
        <x-shared::popover placement="top">
            <x-slot name="trigger">
                <span class="material-symbols-outlined text-[18px] leading-none shrink-0">draft</span>
            </x-slot>
            <p>{{ __('story::chapters.list.draft') }}</p>
        </x-shared::popover>
        @endif
    </div>
    <div class="col-span-1 surface-read text-on-surface p-2" x-data="{ updated: new Date('{{ $ch->updatedAt }}') }">
        <span x-text="DateUtils.formatDate(updated)"></span>
    </div>
    <div class="col-span-1 surface-read text-on-surface p-2">
        <x-shared::metric-badge
            icon="visibility"
            :value="$ch->readsLogged"
            size="sm"
            :label="__('story::chapters.reads.label')"
            :tooltip="__('story::chapters.reads.tooltip')" />
    </div>
    <div class="col-span-1 surface-read text-on-surface p-2 flex justify-center">
        <x-story::words-metric-badge
            size="sm"
            :nb-words="$ch->wordCount"
            :nb-characters="$ch->characterCount" />
    </div>
    <div class="col-span-1 surface-read text-on-surface p-2 flex justify-center gap-2">
        <a href="{{ route('chapters.edit', ['storySlug' => $story->slug, 'chapterSlug' => $ch->slug]) }}"
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
            x-on:click="$dispatch('open-modal', 'confirm-delete-chapter-{{ $ch->id }}')">
            <span class="material-symbols-outlined text-[18px] leading-none">delete</span>
        </button>
    </div>
    
    <x-shared::confirm-modal
        name="confirm-delete-chapter-{{ $ch->id }}"
        :title="__('story::chapters.actions.delete')"
        :body="__('story::show.chapter.confirm_delete_warning')"
        :cancel="__('story::show.cancel')"
        :confirm="__('story::show.chapter.confirm_delete')"
        :action="route('chapters.destroy', ['storySlug' => $story->slug, 'chapterSlug' => $ch->slug])"
        method="DELETE"
        maxWidth="md" />
    @endforeach
</div>