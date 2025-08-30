@php($chapters = $chapters ?? ($viewModel->chapters ?? []))
<ul class="divide-y divide-gray-200 rounded-md border border-gray-200 bg-white" x-ref="readonlyList">
    <template x-for="ch in items" :key="ch.id">
        <li class="p-3 flex items-center justify-between gap-3" :data-id="ch.id">
            <div class="flex items-center gap-3">
                <a :href="ch.url" class="text-indigo-700 hover:text-indigo-900 font-medium" x-text="ch.title"></a>
                <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-800 ring-1 ring-inset ring-gray-300"
                      x-show="ch.isDraft" aria-label="{{ __('story::chapters.list.draft') }}">{{ __('story::chapters.list.draft') }}</span>
            </div>
            <div class="flex items-center gap-3">
                <x-shared::popover placement="top" width="16rem">
                    <x-slot name="trigger">
                        <span class="inline-flex items-center gap-1 rounded-full bg-white px-2 py-0.5 text-xs font-medium text-gray-900 ring-1 ring-inset ring-gray-300">
                            <span class="material-symbols-outlined text-[16px] leading-none">visibility</span>
                            <span x-text="new Intl.NumberFormat('fr-FR').format(ch.readsLogged)"></span>
                        </span>
                    </x-slot>
                    <div class="font-semibold text-gray-900">{{ __('story::chapters.reads.label') }}</div>
                    <div class="text-gray-700">{{ __('story::chapters.reads.tooltip') }}</div>
                </x-shared::popover>

                <a :href="ch.editUrl"
                   class="inline-flex items-center gap-1 text-gray-500 hover:text-gray-700"
                   title="{{ __('story::chapters.actions.edit') }}"
                   aria-label="{{ __('story::chapters.actions.edit') }}">
                    <span class="material-symbols-outlined text-[18px] leading-none">edit</span>
                </a>
            </div>
        </li>
    </template>
</ul>
