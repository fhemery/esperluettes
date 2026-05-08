<x-admin::layout>
    <div class="flex flex-col gap-6" x-data="{ reordering: false }"
         @reorder-cancel.window="reordering = false">

        <div class="flex justify-between items-center">
            <x-shared::title>{{ __('faq::admin.questions.title') }}</x-shared::title>
            <div class="flex items-center gap-2">
                @if($questions->count() > 1)
                    <x-shared::button color="neutral" :outline="true" x-show="!reordering" x-on:click="reordering = true">
                        <span class="material-symbols-outlined text-[18px] leading-none">swap_vert</span>
                        {{ __('administration::reorder.button') }}
                    </x-shared::button>
                @endif
                <a href="{{ route('faq.admin.faq-questions.create') }}" x-show="!reordering">
                    <x-shared::button color="primary" icon="add">
                        {{ __('faq::admin.questions.create_button') }}
                    </x-shared::button>
                </a>
            </div>
        </div>

        <x-shared::flash-block />

        {{-- Category filter --}}
        <form method="GET" action="{{ route('faq.admin.faq-questions.index') }}" class="flex items-center gap-3" x-show="!reordering">
            <select name="category_id"
                    class="rounded-md border-border bg-surface-read text-on-surface text-sm"
                    onchange="this.form.submit()">
                <option value="">{{ __('faq::admin.questions.all_categories') }}</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" {{ $categoryId == $category->id ? 'selected' : '' }}>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
        </form>

        <div x-show="reordering" x-cloak>
            <x-administration::reorderable-table
                :items="$questions"
                :reorderUrl="route('faq.admin.faq-questions.reorder')"
                nameField="question"
                eventName="reorder"
            />
        </div>

        <div x-show="!reordering" class="surface-read text-on-surface p-4 overflow-x-auto">
            <table class="w-full text-sm admin">
                <thead>
                    <tr class="border-b border-border text-left">
                        <th class="p-3">{{ __('faq::admin.questions.table.sort_order') }}</th>
                        <th class="p-3">{{ __('faq::admin.questions.table.category') }}</th>
                        <th class="p-3">{{ __('faq::admin.questions.table.question') }}</th>
                        <th class="p-3">{{ __('faq::admin.questions.table.image') }}</th>
                        <th class="p-3">{{ __('faq::admin.questions.table.active') }}</th>
                        <th class="p-3">{{ __('faq::admin.questions.table.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($questions as $question)
                        <tr class="border-b border-border/50 hover:bg-surface-read/50">
                            <td class="p-3 text-fg/50">{{ $question->sort_order }}</td>
                            <td class="p-3 text-fg/70">{{ $question->category?->name ?? '-' }}</td>
                            <td class="p-3 font-medium max-w-sm truncate">{{ $question->question }}</td>
                            <td class="p-3">
                                @if ($question->image_path)
                                    <span class="material-symbols-outlined text-success text-[18px]">photo</span>
                                @else
                                    <span class="material-symbols-outlined text-fg/30 text-[18px]">hide_image</span>
                                @endif
                            </td>
                            <td class="p-3">
                                <form method="POST" action="{{ route('faq.admin.faq-questions.toggle-active', $question) }}">
                                    @csrf
                                    <button type="submit" title="{{ $question->is_active ? __('Désactiver') : __('Activer') }}">
                                        <x-administration::active-badge :active="$question->is_active" />
                                    </button>
                                </form>
                            </td>
                            <td class="p-3">
                                <div class="flex gap-2">
                                    <a href="{{ route('faq.admin.faq-questions.edit', $question) }}"
                                       class="text-primary hover:text-primary/80"
                                       title="{{ __('Modifier') }}">
                                        <span class="material-symbols-outlined">edit</span>
                                    </a>
                                    <x-administration::delete-button
                                        :action="route('faq.admin.faq-questions.destroy', $question)"
                                        :confirm="__('faq::admin.questions.confirm_delete')"
                                        :title="__('Supprimer')" />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-6 text-center text-fg/50">{{ __('faq::admin.questions.no_items') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-admin::layout>
