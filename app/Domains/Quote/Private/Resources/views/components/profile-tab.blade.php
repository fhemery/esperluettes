@props(['quoteList', 'isOwn' => false, 'profileSlug' => ''])
@php
    $initial = [
        'items' => array_map(fn($q) => [
            'id' => $q->id,
            'highlighted_text' => $q->highlightedText,
            'note' => $q->note,
            'story_title' => $q->storyTitle,
            'story_url' => $q->storyUrl,
            'chapter_title' => $q->chapterTitle,
            'chapter_url' => $q->chapterUrl,
            'chapter_available' => $q->chapterAvailable,
            'anchor_missing' => $q->anchorMissing,
            'author_profiles' => array_map(fn($p) => [
                'display_name' => $p->display_name,
                'slug' => $p->slug,
            ], $q->authorProfiles),
            'created_at' => $q->createdAt->format('c'),
            'can_edit_note' => $q->canEditNote,
            'can_delete' => $q->canDelete,
        ], $quoteList->items),
        'total' => $quoteList->totalCount,
        'page' => $quoteList->page,
        'isOwn' => $isOwn,
        'slug' => $profileSlug,
        'i18n' => [
            'load_more_error' => __('quote::ui.errors.load_more'),
            'save_note_error' => __('quote::ui.errors.save_note'),
            'delete_quote_error' => __('quote::ui.errors.delete_quote'),
            'delete_confirm' => __('quote::ui.profile_tab.delete_confirm'),
        ],
    ];
@endphp

<div class="flex flex-col gap-6" x-data="quoteList({{ \Illuminate\Support\Js::from($initial) }})">
    <template x-if="items.length === 0">
        <p class="text-center text-gray-500 py-8">
            {{ $isOwn ? __('quote::ui.profile_tab.empty_own') : __('quote::ui.profile_tab.empty_other') }}
        </p>
    </template>

    <template x-for="item in items" :key="item.id">
        <article class="flex flex-col gap-2 border-b border-accent pb-4 last:border-0 last:pb-0">
            <blockquote class="border-l-4 border-tertiary/60 pl-3 italic text-fg/80 text-sm" x-text="item.highlighted_text"></blockquote>

            <template x-if="item.anchor_missing">
                <span class="self-start text-xs rounded bg-warning/15 text-warning px-2 py-0.5">
                    {{ __('quote::ui.profile_tab.passage_missing') }}
                </span>
            </template>

            {{-- Note (owner only). Not editing --}}
            <template x-if="item.note && editingId !== item.id">
                <div class="prose prose-sm text-fg/70" x-html="item.note"></div>
            </template>

            {{-- Note editor (owner only) --}}
            <template x-if="isOwn && editingId === item.id">
                <div class="flex flex-col gap-2">
                    <textarea
                        x-model="editValue"
                        rows="3"
                        maxlength="1000"
                        @keydown.enter="if ($event.ctrlKey || $event.metaKey) { $event.preventDefault(); saveEdit(item) }"
                        class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:ring-1 focus:ring-tertiary"
                        placeholder="{{ __('quote::ui.profile_tab.note_placeholder') }}"
                    ></textarea>
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="cancelEdit()"
                            class="px-3 py-1.5 text-sm rounded border border-gray-300 hover:bg-gray-50">
                            {{ __('quote::ui.profile_tab.cancel') }}
                        </button>
                        <button type="button" @click="saveEdit(item)" :disabled="savingId === item.id"
                            class="px-3 py-1.5 text-sm rounded bg-tertiary text-white hover:bg-tertiary/90 disabled:opacity-50">
                            {{ __('quote::ui.profile_tab.save') }}
                        </button>
                    </div>
                </div>
            </template>

            <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-fg/50">
                <template x-if="item.chapter_available && item.chapter_url">
                    <span class="inline-flex flex-wrap items-center gap-x-2">
                        <a :href="item.chapter_url" class="text-accent hover:underline" x-text="item.chapter_title"></a>
                        <span aria-hidden="true">·</span>
                        <a :href="item.story_url" class="hover:underline" x-text="item.story_title"></a>
                    </span>
                </template>
                <template x-if="!(item.chapter_available && item.chapter_url)">
                    <span class="inline-flex flex-wrap items-center gap-x-2">
                        <span x-text="item.story_title"></span>
                        <span class="text-xs rounded bg-neutral/15 px-2 py-0.5">
                            {{ __('quote::ui.profile_tab.chapter_unavailable') }}
                        </span>
                    </span>
                </template>

                <template x-if="item.author_profiles.length">
                    <span class="inline-flex flex-wrap items-center gap-x-1">
                        <span aria-hidden="true">·</span>
                        <template x-for="(author, i) in item.author_profiles" :key="author.slug">
                            <span>
                                <span x-show="i > 0">, </span>
                                <a :href="'/profile/' + author.slug" class="hover:underline" x-text="author.display_name"></a>
                            </span>
                        </template>
                    </span>
                </template>

                <span aria-hidden="true">·</span>
                <span x-text="formatDate(item.created_at)"></span>
            </div>

            {{-- Owner actions --}}
            <template x-if="isOwn && editingId !== item.id">
                <div class="flex gap-2">
                    <template x-if="item.can_edit_note">
                        <button type="button" @click="startEdit(item)"
                            class="text-xs text-accent hover:underline">
                            {{ __('quote::ui.profile_tab.edit_note') }}
                        </button>
                    </template>
                    <template x-if="item.can_delete">
                        <button type="button" @click="remove(item)"
                            class="text-xs text-red-600 hover:underline">
                            {{ __('quote::ui.profile_tab.delete') }}
                        </button>
                    </template>
                </div>
            </template>
        </article>
    </template>

    <p x-show="error" x-text="error" class="text-sm text-red-600"></p>

    <div class="text-center pt-2" x-show="hasMore">
        <button type="button" @click="loadMore()" :disabled="loading"
            class="px-4 py-2 text-sm rounded border border-accent text-accent hover:bg-accent/10 disabled:opacity-50">
            {{ __('quote::ui.profile_tab.load_more') }}
        </button>
    </div>
</div>

@once
    @push('head-scripts')
        @vite('app/Domains/Quote/Resources/js/quote/index.js')
    @endpush
@endonce
