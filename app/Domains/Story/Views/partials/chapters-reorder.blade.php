@php($chapters = $chapters ?? ($viewModel->chapters ?? []))
@if (!empty($chapters))
    <ul class="divide-y divide-gray-200 rounded-md border border-gray-200 bg-white"
        x-ref="list"
    >
        @foreach($chapters as $ch)
            <li class="p-3 flex items-center justify-between gap-3"
                data-id="{{ $ch->id }}"
                draggable="true"
                @dragstart="onDragStartId($event, {{ $ch->id }})"
                @dragover.prevent="onDragOverId($event, {{ $ch->id }})"
                @drop.prevent="onDropId($event, {{ $ch->id }})"
                class="bg-white"
            >
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-[18px] leading-none text-gray-400 cursor-grab" title="Drag">
                        drag_indicator
                    </span>
                    <span class="font-medium text-gray-700">
                        {{ $ch->title }}
                    </span>
                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-800 ring-1 ring-inset ring-gray-300" aria-label="{{ __('story::chapters.list.draft') }}">{{ __('story::chapters.list.draft') }}</span>
                </div>
                <div class="flex items-center gap-1">
                    <button type="button"
                            class="inline-flex items-center justify-center h-7 w-7 rounded border text-gray-600 hover:bg-gray-50 disabled:opacity-40"
                            @click="moveUpId({{ $ch->id }})"
                            :disabled="indexById({{ $ch->id }}) === 0"
                            title="{{ __('story::chapters.actions.move_up') }}"
                            aria-label="{{ __('story::chapters.actions.move_up') }}">
                            <span class="material-symbols-outlined text-[18px] leading-none">arrow_upward</span>
                    </button>
                    <button type="button"
                            class="inline-flex items-center justify-center h-7 w-7 rounded border text-gray-600 hover:bg-gray-50 disabled:opacity-40"
                            @click="moveDownId({{ $ch->id }})"
                            :disabled="indexById({{ $ch->id }}) === items.length - 1"
                            title="{{ __('story::chapters.actions.move_down') }}"
                            aria-label="{{ __('story::chapters.actions.move_down') }}">
                        <span class="material-symbols-outlined text-[18px] leading-none">arrow_downward</span>
                    </button>
                </div>
            </li>
            @endforeach
        </ul>
@endif
