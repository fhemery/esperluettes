@php($chapters = $chapters ?? ($viewModel->chapters ?? []))
@if(($isAuthor ?? false))
    <ul class="divide-y divide-gray-200 rounded-md border border-gray-200 bg-white" x-ref="readonlyList">
        <template x-for="ch in items" :key="ch.id">
            <li class="p-3 flex items-center justify-between gap-3" :data-id="ch.id">
                <div class="flex items-center gap-3">
                    <a :href="ch.url" class="text-indigo-700 hover:text-indigo-900 font-medium" x-text="ch.title"></a>
                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-800 ring-1 ring-inset ring-gray-300"
                          x-show="ch.isDraft" aria-label="{{ __('story::chapters.list.draft') }}">{{ __('story::chapters.list.draft') }}</span>
                </div>
                <a :href="ch.editUrl"
                   class="inline-flex items-center gap-1 text-gray-500 hover:text-gray-700"
                   title="{{ __('story::chapters.actions.edit') }}"
                   aria-label="{{ __('story::chapters.actions.edit') }}">
                    <span class="material-symbols-outlined text-[18px] leading-none">edit</span>
                </a>
            </li>
        </template>
    </ul>
@elseif (!empty($chapters))
    <ul class="divide-y divide-gray-200 rounded-md border border-gray-200 bg-white">
        @foreach($chapters as $ch)
            <li class="p-3 flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <a href="{{ $ch->url }}" class="text-indigo-700 hover:text-indigo-900 font-medium">
                        {{ $ch->title }}
                    </a>
                </div>
            </li>
        @endforeach
    </ul>
@endif
