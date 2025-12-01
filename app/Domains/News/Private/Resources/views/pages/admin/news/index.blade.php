<x-admin::layout>
    <div class="flex flex-col gap-6">
        <div class="flex justify-between items-center">
            <x-shared::title>{{ __('news::admin.news.title') }}</x-shared::title>
            <a href="{{ route('news.admin.create') }}">
                <x-shared::button color="primary" icon="add">
                    {{ __('news::admin.news.create_button') }}
                </x-shared::button>
            </a>
        </div>

        <!-- News table -->
        <div class="surface-read text-on-surface p-4 py-12 overflow-x-auto">
            <table class="w-full admin">
                <thead>
                    <tr class="border-b border-border text-left">
                        <th class="p-3">{{ __('news::admin.table.title') }}</th>
                        <th class="p-3">{{ __('news::admin.table.status') }}</th>
                        <th class="p-3">{{ __('news::admin.table.pinned') }}</th>
                        <th class="p-3">{{ __('news::admin.table.published_at') }}</th>
                        <th class="p-3">{{ __('news::admin.table.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($news as $item)
                        <tr class="border-b border-border/50 hover:bg-surface-read/50">
                            <td class="p-3 font-medium">
                                <a href="{{ route('news.show', $item->slug) }}" target="_blank" class="hover:underline">
                                    {{ $item->title }}
                                </a>
                            </td>
                            <td class="p-3">
                                @if ($item->status === 'published')
                                    <span class="inline-flex items-center px-2 py-1 bg-success/20 text-success rounded">
                                        {{ __('news::admin.status.published') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 bg-warning/20 text-warning rounded">
                                        {{ __('news::admin.status.draft') }}
                                    </span>
                                @endif
                            </td>
                            <td class="p-3">
                                @if ($item->is_pinned)
                                    <span class="inline-flex items-center text-primary">
                                        <span class="material-symbols-outlined text-[18px]">push_pin</span>
                                    </span>
                                @else
                                    <span class="text-fg/30">-</span>
                                @endif
                            </td>
                            <td class="p-3 text-fg/60">
                                {{ $item->published_at?->format('d/m/Y H:i') ?? '-' }}
                            </td>
                            <td class="p-3">
                                <div class="flex gap-2 items-center">
                                    {{-- View --}}
                                    <a href="{{ route('news.show', $item->slug) }}" 
                                       target="_blank"
                                       class="text-fg/60 hover:text-primary" 
                                       title="{{ __('news::admin.actions.view') }}">
                                        <span class="material-symbols-outlined text-[20px]">open_in_new</span>
                                    </a>
                                    
                                    {{-- Edit --}}
                                    <a href="{{ route('news.admin.edit', $item) }}" 
                                       class="text-primary hover:text-primary/80" 
                                       title="{{ __('news::admin.actions.edit') }}">
                                        <span class="material-symbols-outlined text-[20px]">edit</span>
                                    </a>
                                    
                                    {{-- Publish/Unpublish --}}
                                    @if ($item->status === 'published')
                                        <form action="{{ route('news.admin.unpublish', $item) }}" method="POST" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" 
                                                    class="text-warning hover:text-warning/80"
                                                    title="{{ __('news::admin.actions.unpublish') }}"
                                                    onclick="return confirm('{{ __('news::admin.confirm.unpublish') }}')">
                                                <span class="material-symbols-outlined text-[20px]">unpublished</span>
                                            </button>
                                        </form>
                                    @else
                                        <form action="{{ route('news.admin.publish', $item) }}" method="POST" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" 
                                                    class="text-success hover:text-success/80"
                                                    title="{{ __('news::admin.actions.publish') }}"
                                                    onclick="return confirm('{{ __('news::admin.confirm.publish') }}')">
                                                <span class="material-symbols-outlined text-[20px]">publish</span>
                                            </button>
                                        </form>
                                    @endif
                                    
                                    {{-- Delete --}}
                                    <form action="{{ route('news.admin.destroy', $item) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                class="text-error hover:text-error/80"
                                                title="{{ __('news::admin.actions.delete') }}"
                                                onclick="return confirm('{{ __('news::admin.confirm.delete') }}')">
                                            <span class="material-symbols-outlined text-[20px]">delete</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-6 text-center text-fg/50">
                                {{ __('news::admin.news.empty') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <x-shared::pagination :paginator="$news" />
    </div>
</x-admin::layout>
