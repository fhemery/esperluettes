@props(['name', 'id', 'defaultValue' => '', 'max' => null, 'nbLines' => null, 'placeholder' => ''])
<div {{ $attributes->merge(['class' => '']) }}>
    <div class="mb-2" id="{{ $id }}" data-placeholder="{{ e($placeholder) }}"></div>
    <input type="hidden" name="{{ $name }}" id="quill-editor-area-{{ $id }}" value="{!! $defaultValue !!}" />
    <div class="mt-2 text-xs text-right " id="quill-counter-wrap-{{ $id }}">
        <span id="quill-counter-{{ $id }}">0</span>
        @if($max)
            <span>/ {{ $max }}</span>
        @endif
        <span>{{ __('characters') }}</span>
    </div>
    @push('scripts')
    <script>
        (function initQuill() {
            const run = () => {
                const container = document.getElementById('{{ $id }}');
                if (!container || typeof Quill === 'undefined') return;

                const placeholder = container ? (container.dataset.placeholder || '') : '';
                const editor = new Quill(container, {
                    theme: 'snow',
                    modules: {
                        toolbar: [
                            ['bold', 'italic', 'underline', 'strike'],
                            ['blockquote'],
                            [{ 'align': [] }],
                            [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                            ['clean']
                        ]
                    },
                    placeholder
                });

                // Height handling: enforce min and max lines, scroll when exceeding max
                // Defaults: min 5 lines, max nbLines (or 5 if not provided)
                const minLines = 5;
                const maxLines = {{ $nbLines ? (int) $nbLines : 5 }};
                const computed = window.getComputedStyle(editor.root);
                const lineHeight = parseFloat(computed.lineHeight) || 24;
                const minPx = (minLines * lineHeight);
                const maxPx = (maxLines * lineHeight);

                // Constrain Quill container (.ql-container)
                const qlContainer = editor.container; // .ql-container element
                if (qlContainer) {
                    qlContainer.style.boxSizing = 'border-box';
                    qlContainer.style.minHeight = minPx + 'px';
                    qlContainer.style.height = maxPx + 'px'; // fix container height so inner editor can scroll
                    qlContainer.style.overflow = 'hidden';
                }

                // Make the editor (.ql-editor) scroll inside the container
                editor.root.style.boxSizing = 'border-box';
                editor.root.style.height = '100%';
                editor.root.style.overflowY = 'auto';

                const quillEditor = document.getElementById('quill-editor-area-{{ $id }}');
                const counterEl = document.getElementById('quill-counter-{{ $id }}');
                const counterWrap = document.getElementById('quill-counter-wrap-{{ $id }}');
                const max = {{ $max ? (int) $max : 'null' }};

                // Set default value if it's not empty
                const defaultValue = (quillEditor.value || '').trim();
                if (defaultValue) {
                    editor.clipboard.dangerouslyPasteHTML(defaultValue);
                }

                // Sync Quill with the hidden input
                const updateCount = () => {
                    let text = editor.getText() || '';
                    if (text.endsWith('\n')) text = text.slice(0, -1); // Quill ends with a trailing newline
                    const count = text.length;
                    if (counterEl) {
                        counterEl.textContent = max ? `${count}` : `${count}`;
                    }
                    if (max && counterWrap) {
                        // Toggle red when limit exceeded
                        const over = count > max;
                        counterWrap.classList.toggle('text-red-600', over);
                        counterWrap.classList.toggle('text-gray-500', !over);
                    }
                };

                editor.on('text-change', function () {
                    quillEditor.value = editor.root.innerHTML;
                    updateCount();
                });

                // If hidden input is changed externally, reflect it back
                quillEditor.addEventListener('input', function () {
                    editor.root.innerHTML = quillEditor.value;
                    updateCount();
                });

                // Initial count
                updateCount();
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', run);
            } else {
                run();
            }
        })();
    </script>
    @endpush
</div>