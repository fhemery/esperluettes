<section class="mt-10"
         @if($isAuthor)
         x-data="chapterReorder({
            initial: @js(array_map(fn($c) => ['id'=>$c->id,'title'=>$c->title,'slug'=>$c->slug,'url'=>$c->url,'isDraft'=>$c->isDraft], $chapters ?? ($viewModel->chapters ?? []))),
            reorderUrl: @js(route('chapters.reorder', ['storySlug' => $story->slug])),
         })"
         data-success-msg="{{ __('story::chapters.reorder_success') }}"
         @endif
>
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-semibold">{{ __('story::chapters.sections.chapters') }}</h2>
        <div class="flex items-center gap-2">
            @if($isAuthor)
                <template x-if="!editing">
                <div class="flex items-center gap-2">
                    <button type="button" @click="start()"
                            class="inline-flex items-center gap-1 px-3 py-2 rounded-md border text-gray-700 hover:bg-gray-50"
                            title="{{ __('story::chapters.actions.reorder') }}">
                        <span class="material-symbols-outlined text-[18px] leading-none">swap_vert</span>
                        {{ __('story::chapters.actions.reorder') }}
                    </button>
                    <a href="{{ route('chapters.create', ['storySlug' => $story->slug]) }}"
                class="inline-flex items-center gap-1 px-3 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700">
                        <span class="material-symbols-outlined text-[18px] leading-none">add</span>
                        {{ __('story::chapters.sections.add_chapter') }}
                    </a>
                </div>
                </template>
                <template x-if="editing">
                    <div class="flex items-center gap-2">
                        <button type="button" @click="save()" :disabled="saving"
                                class="inline-flex items-center gap-1 px-3 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700 disabled:opacity-50">
                            <span class="material-symbols-outlined text-[18px] leading-none" x-show="!saving">save</span>
                            <span class="material-symbols-outlined text-[18px] leading-none animate-spin" x-show="saving">progress_activity</span>
                            {{ __('story::chapters.actions.save_order') }}
                        </button>
                        <button type="button" @click="cancel()"
                                class="inline-flex items-center gap-1 px-3 py-2 rounded-md border text-gray-700 hover:bg-gray-50">
                            <span class="material-symbols-outlined text-[18px] leading-none">close</span>
                            {{ __('story::chapters.actions.cancel') }}
                        </button>
                    </div>
                </template>
               
            @endif
        </div>
    </div>
    @php($chapters = $chapters ?? ($viewModel->chapters ?? []))
    @if (empty($chapters))
        <p class="text-sm text-gray-600">{{ __('story::chapters.list.empty') }}</p>
    @else
        <ul class="divide-y divide-gray-200 rounded-md border border-gray-200 bg-white"
            @if($isAuthor)
            x-ref="list"
            @endif
        >
            @foreach($chapters as $ch)
                <li class="p-3 flex items-center justify-between gap-3"
                    @if($isAuthor)
                    data-id="{{ $ch->id }}"
                    draggable="true"
                    :draggable="editing"
                    @dragstart="onDragStartId($event, {{ $ch->id }})"
                    @dragover.prevent="onDragOverId($event, {{ $ch->id }})"
                    @drop.prevent="onDropId($event, {{ $ch->id }})"
                    :class="editing ? 'bg-white' : ''"
                    @endif
                >
                    <div class="flex items-center gap-3">
                        @if($isAuthor)
                            <span class="material-symbols-outlined text-[18px] leading-none text-gray-400"
                                  :class="editing ? 'cursor-grab' : 'opacity-0'" title="Drag">
                                drag_indicator
                            </span>
                        @endif
                        <a href="{{ $ch->url }}" class="text-indigo-700 hover:text-indigo-900 font-medium"
                           :class="editing ? 'pointer-events-none text-gray-500' : ''">
                            {{ $ch->title }}
                        </a>
                        @if($isAuthor && $ch->isDraft)
                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-800 ring-1 ring-inset ring-gray-300" aria-label="{{ __('story::chapters.list.draft') }}">{{ __('story::chapters.list.draft') }}</span>
                        @endif
                    </div>
                    @if($isAuthor)
                        <template x-if="editing">
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
                        </template>
                        <template x-if="!editing">
                            <a href="{{ route('chapters.edit', ['storySlug' => $story->slug, 'chapterSlug' => $ch->slug]) }}"
                               class="inline-flex items-center gap-1 text-gray-500 hover:text-gray-700"
                               title="{{ __('story::chapters.actions.edit') }}"
                               aria-label="{{ __('story::chapters.actions.edit') }}">
                                <span class="material-symbols-outlined text-[18px] leading-none">edit</span>
                            </a>
                        </template>
                    @endif
                </li>
            @endforeach
        </ul>
        @if($isAuthor)
            <div class="mt-2 text-sm rounded-md px-3 py-2 border"
                 x-show="status"
                 x-text="status"
                 :class="statusType === 'success' ? 'bg-green-50 text-green-700 border-green-200' : (statusType === 'error' ? 'bg-red-50 text-red-700 border-red-200' : 'bg-gray-50 text-gray-700 border-gray-200')"
            ></div>
        @endif
    @endif
</section>

@if($isAuthor)
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('chapterReorder', ({initial, reorderUrl}) => ({
                editing: false,
                saving: false,
                status: '',
                statusType: '',
                successMsg: '',
                items: initial,
                dragIndex: null,
                init() {
                    // Alpine lifecycle: set success message from data attribute
                    this.successMsg = this.$root?.dataset?.successMsg || 'Saved';
                },
                start() {
                    this.editing = true;
                    this.status = '';
                    this.statusType = '';
                },
                cancel() {
                    // reset to initial order
                    this.items = initial.slice();
                    this.syncDom();
                    this.editing = false;
                    this.status = '';
                    this.statusType = '';
                },
                // Index helpers based on stable id
                indexById(id) {
                    return this.items.findIndex(i => i.id === id);
                },
                onDragStartId(e, id) {
                    if (!this.editing) return;
                    this.dragIndex = this.indexById(id);
                    e.dataTransfer.effectAllowed = 'move';
                },
                onDragOverId(e, id) {
                    if (!this.editing) return;
                    e.preventDefault();
                },
                onDropId(e, id) {
                    if (!this.editing) return;
                    const from = this.dragIndex;
                    const to = this.indexById(id);
                    if (from === null || to === null || from === to) return;
                    const moved = this.items.splice(from, 1)[0];
                    this.items.splice(to, 0, moved);
                    this.dragIndex = null;
                    this.syncDom();
                },
                syncDom() {
                    // Reorder DOM <li> to match this.items order via data-id
                    const ul = this.$refs.list;
                    if (!ul) return;
                    const byId = new Map(Array.from(ul.children).map(li => [parseInt(li.getAttribute('data-id')), li]));
                    this.items.forEach(it => {
                        const li = byId.get(it.id);
                        if (li) ul.appendChild(li);
                    });
                },
                // Button-based moves
                moveById(id, delta) {
                    if (!this.editing) return;
                    const i = this.indexById(id);
                    const j = i + delta;
                    if (i < 0 || j < 0 || j >= this.items.length) return;
                    const [moved] = this.items.splice(i, 1);
                    this.items.splice(j, 0, moved);
                    this.syncDom();
                },
                moveUpId(id) { this.moveById(id, -1); },
                moveDownId(id) { this.moveById(id, 1); },
                async save() {
                    this.saving = true;
                    this.status = '';
                    try {
                        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                        const res = await fetch(reorderUrl, {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': token,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ ordered_ids: this.items.map(i => i.id) }),
                        });
                        if (!res.ok) {
                            const data = await res.json().catch(() => ({}));
                            throw new Error(data.message || 'Failed to reorder');
                        }
                        const data = await res.json();
                        this.status = this.successMsg;
                        this.statusType = 'success';
                        this.editing = false;
                        // Persist current order as new baseline for future sessions
                        initial = this.items.slice();
                    } catch (e) {
                        this.status = (e && e.message) ? e.message : 'Error';
                        this.statusType = 'error';
                    } finally {
                        this.saving = false;
                    }
                },
            }));
        });
    </script>
@endif
