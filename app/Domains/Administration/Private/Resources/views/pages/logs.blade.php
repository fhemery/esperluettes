<x-admin::layout>
    <x-shared::title>{{ __('administration::logs.title') }}</x-shared::title>
    <x-shared::title tag="h2">{{ __('administration::logs.title') }}</x-shared::title>
    
    <div class="flex flex-col gap-8 surface-read text-on-surface">
        <!-- File Selection -->
        <div class="p-6">
            <h3 class="text-lg font-medium mb-4">{{ __('administration::logs.select_file') }}</h3>
            
            <div class="flex gap-4 items-center">
                <div>
                    <form method="GET" action="{{ route('administration.logs') }}" class="flex gap-4 items-center">
                        <x-shared::select
                            name="file"
                            :options="collect($availableFiles)->map(fn ($file) => [
                                'id' => $file['file'],
                                'name' => $file['file'].' ('.number_format($file['size'] / 1024, 2).' KB, '.\Carbon\Carbon::createFromTimestamp($file['mtime'])->format('Y-m-d H:i:s').')',
                            ])->all()"
                            :selected="$selectedFile"
                            chevron
                        />
                        <x-shared::button type="submit" color="primary" icon="visibility">{{ __('administration::logs.view_button') }}</x-shared::button>
                    </form>
                </div>
                
                @if($selectedFile)
                    <form method="GET" action="{{ route('administration.logs.download', ['file' => $selectedFile]) }}">
                        <x-shared::button type="submit" color="tertiary" icon="download">
                            {{ __('administration::logs.download_button') }}
                        </x-shared::button>
                    </form>
                @endif
            </div>
        </div>
        
        <!-- Log Content -->
        @if($selectedFile && !empty($lines))
            <div class="surface-bg text-on-surface p-6">
                <h3 class="text-lg font-medium mb-4">
                    {{ __('administration::logs.content_for', ['file' => $selectedFile]) }}
                    <span class="text-sm text-gray-500 ml-2">{{ __('administration::logs.showing_lines', ['count' => count($lines)]) }}</span>
                </h3>
                
                <div class="p-4 max-h-[600px] overflow-x-auto overflow-y-scroll border border-primary">
                    <pre class="text-sm font-mono whitespace-pre-wrap">{{ implode("\n", $lines) }}</pre>
                </div>
            </div>
        @elseif($selectedFile)
            <div class="surface-read text-on-surface p-6">
                <div class="text-gray-500">{{ __('administration::logs.file_empty_or_unreadable') }}</div>
            </div>
        @else
            <div class="surface-read text-on-surface p-6">
                <div class="text-gray-500">{{ __('administration::logs.no_files_available') }}</div>
            </div>
        @endif
    </div>
</x-admin::layout>
