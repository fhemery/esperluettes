<x-admin::layout>
    <div class="flex flex-col gap-6" x-data="{ reordering: false }"
         @reorder-cancel.window="reordering = false">

        <div class="flex justify-between items-center">
            <x-shared::title>{{ __('faq::admin.categories.title') }}</x-shared::title>
            <div class="flex items-center gap-2">
                @if($categories->count() > 1)
                    <x-shared::button color="neutral" :outline="true" x-show="!reordering" x-on:click="reordering = true">
                        <span class="material-symbols-outlined text-[18px] leading-none">swap_vert</span>
                        {{ __('administration::reorder.button') }}
                    </x-shared::button>
                @endif
                <a href="{{ route('faq.admin.faq-categories.create') }}" x-show="!reordering">
                    <x-shared::button color="primary" icon="add">
                        {{ __('faq::admin.categories.create_button') }}
                    </x-shared::button>
                </a>
            </div>
        </div>

        <x-shared::flash-block />

        <div x-show="reordering" x-cloak>
            <x-administration::reorderable-table
                :items="$categories"
                :reorderUrl="route('faq.admin.faq-categories.reorder')"
                eventName="reorder"
            />
        </div>

        <div x-show="!reordering" class="surface-read text-on-surface p-4 overflow-x-auto">
            <table class="w-full text-sm admin">
                <thead>
                    <tr class="border-b border-border text-left">
                        <th class="p-3">{{ __('faq::admin.categories.table.order') }}</th>
                        <th class="p-3">{{ __('faq::admin.categories.table.name') }}</th>
                        <th class="p-3">{{ __('faq::admin.categories.table.slug') }}</th>
                        <th class="p-3">{{ __('faq::admin.categories.table.description') }}</th>
                        <th class="p-3 text-right">{{ __('faq::admin.categories.table.questions') }}</th>
                        <th class="p-3">{{ __('faq::admin.categories.table.active') }}</th>
                        <th class="p-3">{{ __('faq::admin.categories.table.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($categories as $category)
                        <tr class="border-b border-border/50 hover:bg-surface-read/50">
                            <td class="p-3 text-fg/50">{{ $category->sort_order }}</td>
                            <td class="p-3 font-medium">{{ $category->name }}</td>
                            <td class="p-3 font-mono text-xs">{{ $category->slug }}</td>
                            <td class="p-3 text-fg/70 max-w-xs truncate">{{ $category->description ?? '-' }}</td>
                            <td class="p-3 text-right">{{ $category->questions_count }}</td>
                            <td class="p-3">
                                <form method="POST" action="{{ route('faq.admin.faq-categories.toggle-active', $category) }}">
                                    @csrf
                                    <button type="submit" title="{{ $category->is_active ? __('Désactiver') : __('Activer') }}">
                                        <x-administration::active-badge :active="$category->is_active" />
                                    </button>
                                </form>
                            </td>
                            <td class="p-3">
                                <div class="flex gap-2">
                                    <a href="{{ route('faq.admin.faq-categories.edit', $category) }}"
                                       class="text-primary hover:text-primary/80"
                                       title="{{ __('Modifier') }}">
                                        <span class="material-symbols-outlined">edit</span>
                                    </a>
                                    <x-administration::delete-button
                                        :action="route('faq.admin.faq-categories.destroy', $category)"
                                        :confirm="__('faq::admin.categories.confirm_delete')"
                                        :title="__('Supprimer')" />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-6 text-center text-fg/50">{{ __('faq::admin.categories.no_items') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-admin::layout>
