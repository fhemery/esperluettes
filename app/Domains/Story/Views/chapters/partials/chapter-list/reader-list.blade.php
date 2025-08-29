@php($chapters = $chapters ?? ($viewModel->chapters ?? []))
@if (!empty($chapters))
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
@else
    <p class="text-sm text-gray-600">{{ __('story::chapters.list.empty') }}</p>
@endif
