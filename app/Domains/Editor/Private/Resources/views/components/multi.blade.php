{{--
    <x-editor::multi> — opt-in advanced block editor.

    Simple mode (default) renders the usual single <x-editor::rich-text> bound to
    `contentName`. Advanced mode renders an ordered stack of typed blocks (text /
    image) that serialize as `name[uid][...]` plus `mode` and `name_order` (the
    visual order of uids). The server branches on `mode` (see NewsService).

    Props:
      name         base field for blocks (e.g. "blocks")
      contentName  simple-mode field (e.g. "content")
      contentValue current simple HTML
      blocks       initial advanced blocks (array) — non-empty ⇒ start advanced
      mode         'simple' | 'advanced' (initial)
      blockTypes   per-consumer opt-in list of registered block types, default
                   ['text','image']. Drives the palette, the "+" insert menu,
                   the hidden templates and which stored blocks are rendered
                   (in EditorBlockRegistry order; unregistered keys ignored,
                   stored blocks of a non-enabled type skipped).
      blockContext array passed untouched to plugin block partials as $context;
                   Editor never reads it. Default [].
      scope       Media scope for image uploads/picker (required)
      toolbar      preset name or explicit token array (passed to each text block)
      min / max    summed-text constraints
      placeholder  editor placeholder
      nbLines      height in lines of every writing surface (simple + each block)
      indentParagraphs  adds `ql-indent` to every writing surface
      needsPropertyConfirm  forward to each image block's media field (default false)
--}}
@props([
    'name' => 'blocks',
    'contentName' => 'content',
    'contentValue' => '',
    'blocks' => [],
    'mode' => 'simple',
    'blockTypes' => ['text', 'image'],
    'blockContext' => [],
    'scope',
    'toolbar' => 'default',
    'min' => null,
    'max' => null,
    'placeholder' => '',
    'nbLines' => 5,
    'indentParagraphs' => false,
    'needsPropertyConfirm' => false,
])

@use(App\Domains\Editor\Private\Support\ToolbarPresets)
@use(App\Domains\Editor\Public\Blocks\EditorBlockRegistry)

@include('editor::components._assets')

@php
    // Registered types this consumer opted into, in registry order. Inherited by
    // the text/image partials' insert affordance through include scope.
    $enabledTypes = array_values(array_filter(
        app(EditorBlockRegistry::class)->all(),
        fn ($t) => in_array($t->key(), $blockTypes, true),
    ));
    $enabledByKey = collect($enabledTypes)->keyBy(fn ($t) => $t->key());
    // Resolved once here, so both panes and every text block share one list.
    $toolbar = ToolbarPresets::resolve($toolbar);
    $simpleId = 'me-simple-' . Str::random(6);
    $blocks = is_array($blocks) ? $blocks : [];
    // A document with stored blocks opens in advanced mode.
    $initialMode = !empty($blocks) ? 'advanced' : $mode;
@endphp

<div
    x-data="multiEditor({
        mode: @js($initialMode),
        scope: @js($scope),
        simpleId: @js($simpleId),
        labels: {
            up: @js(__('editor::multi.move_up')),
            down: @js(__('editor::multi.move_down')),
            delete: @js(__('editor::multi.delete')),
            insert: @js(__('editor::multi.insert')),
            imgWarning: @js(__('editor::multi.img_warning')),
            toSimpleDisabled: @js(__('editor::multi.to_simple_disabled')),
        },
    })"
    {{ $attributes->merge(['class' => 'multi-editor']) }}
>
    <input type="hidden" name="mode" :value="mode">
    <input type="hidden" name="{{ $name }}_order" :value="orderCsv">

    {{-- Mode toggle --}}
    <div class="flex gap-2 mb-3" role="group">
        <button type="button" x-on:click="goSimple()"
            :disabled="mode === 'advanced' && !canGoSimple"
            :title="(mode === 'advanced' && !canGoSimple) ? labels.toSimpleDisabled : ''"
            class="px-3 py-1 text-sm rounded-md border border-border disabled:opacity-40"
            :class="mode === 'simple' ? 'bg-primary text-white' : 'text-fg'">
            {{ __('editor::multi.simple') }}
        </button>
        <button type="button" x-on:click="goAdvanced()"
            class="px-3 py-1 text-sm rounded-md border border-border"
            :class="mode === 'advanced' ? 'bg-primary text-white' : 'text-fg'">
            {{ __('editor::multi.advanced') }}
        </button>
    </div>

    {{-- Simple pane --}}
    <div x-show="mode === 'simple'">
        <x-editor::rich-text
            :name="$contentName"
            :id="$simpleId"
            :defaultValue="$contentValue"
            :toolbar="$toolbar"
            :min="$min"
            :max="$max"
            :nbLines="$nbLines"
            :indentParagraphs="$indentParagraphs"
            :placeholder="$placeholder" />
    </div>

    {{-- Advanced pane --}}
    <div x-show="mode === 'advanced'" x-cloak>
        <div x-ref="container" class="multi-editor__blocks">
            @foreach ($blocks as $i => $block)
                @php $blockKey = $block['type'] ?? 'text'; @endphp
                @if (!$enabledByKey->has($blockKey))
                    {{-- Type not enabled/registered for this consumer: skipped. --}}
                @elseif ($blockKey === 'image')
                    @include('editor::components.multi._image-block', [
                        'name' => $name, 'uid' => 'b' . $i, 'scope' => $scope,
                        'path' => $block['path'] ?? null, 'alt' => $block['alt'] ?? '', 'caption' => $block['caption'] ?? '',
                        'keepOriginal' => $block['keep_original'] ?? false,
                        'needsPropertyConfirm' => $needsPropertyConfirm,
                    ])
                @elseif ($blockKey === 'text')
                    @include('editor::components.multi._text-block', [
                        'name' => $name, 'uid' => 'b' . $i, 'toolbar' => $toolbar,
                        'min' => $min, 'max' => $max, 'html' => $block['html'] ?? '', 'placeholder' => $placeholder,
                        'nbLines' => $nbLines, 'indentParagraphs' => $indentParagraphs,
                    ])
                @else
                    @include($enabledByKey[$blockKey]->editorView(), [
                        'name' => $name, 'uid' => 'b' . $i, 'block' => $block, 'context' => $blockContext,
                    ])
                @endif
            @endforeach
        </div>

        {{-- Palette --}}
        <div class="flex gap-2 mt-2 justify-center">
            @foreach ($enabledTypes as $t)
                <button type="button" x-on:click="appendBlock('{{ $t->key() }}')"
                    class="px-3 py-1.5 text-sm rounded-md border border-border text-primary hover:bg-primary/5 flex items-center gap-1">
                    <span class="material-symbols-outlined text-[18px]">{{ $t->icon() }}</span>{{ __($t->labelKey()) }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- Hidden templates for dynamically added blocks, one per enabled type --}}
    @foreach ($enabledTypes as $t)
        <template data-block-template="{{ $t->key() }}">
            @if ($t->key() === 'text')
                @include('editor::components.multi._text-block', [
                    'name' => $name, 'uid' => '__UID__', 'toolbar' => $toolbar,
                    'min' => $min, 'max' => $max, 'html' => '', 'placeholder' => $placeholder,
                    'nbLines' => $nbLines, 'indentParagraphs' => $indentParagraphs,
                ])
            @elseif ($t->key() === 'image')
                @include('editor::components.multi._image-block', [
                    'name' => $name, 'uid' => '__UID__', 'scope' => $scope, 'path' => null, 'alt' => '', 'caption' => '',
                    'keepOriginal' => false,
                    'needsPropertyConfirm' => $needsPropertyConfirm,
                ])
            @else
                @include($t->editorView(), [
                    'name' => $name, 'uid' => '__UID__', 'block' => null, 'context' => $blockContext,
                ])
            @endif
        </template>
    @endforeach

    @once
    @push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('multiEditor', (cfg) => {
                // Captured once at init, from the component root. Block controls
                // call these methods from inside their own block, and Alpine
                // resolves $refs by walking up from the calling element: as soon
                // as a block is detached (removeBlock), $refs.container would be
                // undefined and the state would stop syncing.
                let containerEl = null;
                // Same reason: templates are looked up from the component root,
                // captured at init ($root would be the insert menu's own x-data).
                let rootEl = null;

            return {
                mode: cfg.mode,
                scope: cfg.scope,
                labels: cfg.labels,
                seq: 0,
                blockCount: 0,
                nonTextCount: 0,
                canGoSimple: false,
                orderCsv: '',

                init() {
                    containerEl = this.$refs.container;
                    rootEl = this.$el;
                    // Initialize Quill on the server-rendered text blocks. Done here
                    // (not via inline scripts) so it can't run before the editor
                    // bundle has loaded — the cause of blank editors on the edit page.
                    this.$nextTick(() => {
                        this._blocks().forEach((b) => {
                            if (b.dataset.type === 'text') {
                                const ed = b.querySelector('[data-toolbar]');
                                if (ed) this._ensureEditor(ed.id);
                            }
                        });
                        this.syncState();
                    });
                },
                _ensureEditor(id, attempt = 0) {
                    if (window.initQuillEditor) { window.initQuillEditor(id); return; }
                    if (attempt > 60) return; // give up after ~3s
                    setTimeout(() => this._ensureEditor(id, attempt + 1), 50);
                },
                _container() { return containerEl; },
                _blocks() { return Array.from(this._container().querySelectorAll('[data-block]')); },
                _newUid() { return 'n' + (this.seq++); },
                _make(type, uid) {
                    const tpl = rootEl.querySelector(':scope > template[data-block-template="' + type + '"]');
                    if (!tpl) return null; // type not enabled for this editor
                    const holder = document.createElement('div');
                    holder.appendChild(tpl.content.cloneNode(true));
                    holder.innerHTML = holder.innerHTML.split('__UID__').join(uid);
                    return holder.firstElementChild;
                },
                _afterInsert(node, type) {
                    if (type === 'text') {
                        const ed = node.querySelector('[data-toolbar]');
                        if (ed) this._ensureEditor(ed.id);
                    }
                    this.syncState();
                },
                appendBlock(type) {
                    const node = this._make(type, this._newUid());
                    if (!node) return;
                    this._container().appendChild(node);
                    this._afterInsert(node, type);
                },
                insertAfter(el, type) {
                    const block = el.closest('[data-block]');
                    const node = this._make(type, this._newUid());
                    if (!node) return;
                    block.after(node);
                    this._afterInsert(node, type);
                },
                moveUp(el) {
                    const b = el.closest('[data-block]');
                    const prev = b.previousElementSibling;
                    if (prev && prev.hasAttribute('data-block')) b.parentNode.insertBefore(b, prev);
                    this.syncState();
                },
                moveDown(el) {
                    const b = el.closest('[data-block]');
                    const next = b.nextElementSibling;
                    if (next && next.hasAttribute('data-block')) b.parentNode.insertBefore(next, b);
                    this.syncState();
                },
                removeBlock(el) {
                    el.closest('[data-block]').remove();
                    this.syncState();
                },
                syncState() {
                    const blocks = this._blocks();
                    this.blockCount = blocks.length;
                    this.nonTextCount = blocks.filter(b => b.dataset.type !== 'text').length;
                    this.orderCsv = blocks.map(b => b.dataset.uid).join(',');
                    this.canGoSimple = this.blockCount === 1 && this.nonTextCount === 0;
                },
                goAdvanced() {
                    if (this.mode === 'advanced') return;
                    if (this._blocks().length === 0) {
                        const src = document.getElementById('quill-editor-area-' + cfg.simpleId);
                        const html = src ? src.value : '';
                        if (/<img/i.test(html)) window.alert(this.labels.imgWarning);
                        const node = this._make('text', this._newUid());
                        const ta = node.querySelector('textarea');
                        if (ta) ta.value = html; // seed before init so Quill picks it up
                        this._container().appendChild(node);
                        this._afterInsert(node, 'text');
                    }
                    this.mode = 'advanced';
                },
                goSimple() {
                    if (this.mode === 'simple') return;
                    if (!this.canGoSimple) return;
                    const b = this._blocks()[0];
                    if (b) {
                        const from = b.querySelector('textarea');
                        const to = document.getElementById('quill-editor-area-' + cfg.simpleId);
                        if (from && to) {
                            to.value = from.value;
                            to.dispatchEvent(new Event('input')); // reflect into the simple Quill
                        }
                        b.remove();
                    }
                    this.mode = 'simple';
                    this.syncState();
                },
            };
            });
        });
    </script>
    @endpush
    @endonce
</div>
