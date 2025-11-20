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
                        <select name="file" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach($availableFiles as $file)
                                <option value="{{ $file['file'] }}" {{ $selectedFile === $file['file'] ? 'selected' : '' }}>
                                    {{ $file['file'] }} ({{ number_format($file['size'] / 1024, 2) }} KB, {{ \Carbon\Carbon::createFromTimestamp($file['mtime'])->format('Y-m-d H:i:s') }})
                                </option>
                            @endforeach
                        </select>
                        <x-shared::button type="submit" color="primary" icon="visibility">{{ __('administration::logs.view_button') }}</x-shared::button>
                    </form>
                </div>
                
                @if($selectedFile)
                    <div>
                        <a href="{{ route('administration.logs.download', ['file' => $selectedFile]) }}" 
                           class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            {{ __('administration::logs.download_button') }}
                        </a>
                    </div>
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
