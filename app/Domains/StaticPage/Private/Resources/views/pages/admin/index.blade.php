<x-admin::layout>
    <div class="flex flex-col gap-6">
        <div class="flex justify-between items-center">
            <x-shared::title>{{ __('static::admin.pages.title') }}</x-shared::title>
            <a href="{{ route('static.admin.create') }}">
                <x-shared::button color="primary" icon="add">
                    {{ __('static::admin.pages.create_button') }}
                </x-shared::button>
            </a>
        </div>

        <!-- Pages table -->
        <div class="surface-read text-on-surface p-4 py-12 overflow-x-auto">
            <table class="w-full admin">
                <thead>
                    <tr class="border-b border-border text-left">
                        <th class="p-3">{{ __('static::admin.table.title') }}</th>
                        <th class="p-3">{{ __('static::admin.table.slug') }}</th>
                        <th class="p-3">{{ __('static::admin.table.status') }}</th>
                        <th class="p-3">{{ __('static::admin.table.published_at') }}</th>
                        <th class="p-3">{{ __('static::admin.table.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pages as $page)
                        <tr class="border-b border-border/50 hover:bg-surface-read/50">
                            <td class="p-3 font-medium">
                                <a href="{{ url('/' . $page->slug) }}" target="_blank" class="hover:underline">
                                    {{ $page->title }}
                                </a>
                            </td>
                            <td class="p-3 text-fg/60 font-mono text-sm">
                                /{{ $page->slug }}
                            </td>
                            <td class="p-3">
                                @if ($page->status === 'published')
                                    <span class="inline-flex items-center px-2 py-1 bg-success/20 text-success rounded">
                                        {{ __('static::admin.status.published') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 bg-warning/20 text-warning rounded">
                                        {{ __('static::admin.status.draft') }}
                                    </span>
                                @endif
                            </td>
                            <td class="p-3 text-fg/60">
                                {{ $page->published_at?->format('d/m/Y H:i') ?? '-' }}
                            </td>
                            <td class="p-3">
                                <div class="flex gap-2 items-center">
                                    {{-- View --}}
                                    <a href="{{ url('/' . $page->slug) }}" 
                                       target="_blank"
                                       class="text-fg/60 hover:text-primary" 
                                       title="{{ __('static::admin.actions.view') }}">
                                        <span class="material-symbols-outlined text-[20px]">open_in_new</span>
                                    </a>
                                    
                                    {{-- Edit --}}
                                    <a href="{{ route('static.admin.edit', $page) }}" 
                                       class="text-primary hover:text-primary/80" 
                                       title="{{ __('static::admin.actions.edit') }}">
                                        <span class="material-symbols-outlined text-[20px]">edit</span>
                                    </a>
                                    
                                    {{-- Publish/Unpublish --}}
                                    @if ($page->status === 'published')
                                        <form action="{{ route('static.admin.unpublish', $page) }}" method="POST" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" 
                                                    class="text-warning hover:text-warning/80"
                                                    title="{{ __('static::admin.actions.unpublish') }}"
                                                    onclick="return confirm('{{ __('static::admin.confirm.unpublish') }}')">
                                                <span class="material-symbols-outlined text-[20px]">unpublished</span>
                                            </button>
                                        </form>
                                    @else
                                        <form action="{{ route('static.admin.publish', $page) }}" method="POST" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" 
                                                    class="text-success hover:text-success/80"
                                                    title="{{ __('static::admin.actions.publish') }}"
                                                    onclick="return confirm('{{ __('static::admin.confirm.publish') }}')">
                                                <span class="material-symbols-outlined text-[20px]">publish</span>
                                            </button>
                                        </form>
                                    @endif
                                    
                                    {{-- Delete --}}
                                    <form action="{{ route('static.admin.destroy', $page) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                class="text-error hover:text-error/80"
                                                title="{{ __('static::admin.actions.delete') }}"
                                                onclick="return confirm('{{ __('static::admin.confirm.delete') }}')">
                                            <span class="material-symbols-outlined text-[20px]">delete</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-6 text-center text-fg/50">
                                {{ __('static::admin.pages.empty') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <x-shared::pagination :paginator="$pages" />
    </div>
</x-admin::layout>
