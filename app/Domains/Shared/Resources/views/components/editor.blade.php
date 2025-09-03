@props(['name', 'id', 'defaultValue' => '', 'max' => null, 'min' => null, 'nbLines' => null, 'placeholder' => ''])
<div {{ $attributes->merge(['class' => '']) }}>
    <div class="mb-2" id="{{ $id }}" data-placeholder="{{ e($placeholder) }}" @if($min) data-min="{{ (int) $min }}" @endif @if($max) data-max="{{ (int) $max }}" @endif></div>
    <input type="hidden" name="{{ $name }}" id="quill-editor-area-{{ $id }}" value="{!! $defaultValue !!}" />
    <div class="mt-2 text-xs text-right " id="quill-counter-wrap-{{ $id }}">
        <span id="quill-counter-{{ $id }}">0</span>
        @if($max)
            <span>/ {{ $max }}</span>
        @endif
        <span>{{ __('shared::editor.characters') }}</span>
        @if($min)
            <span>({{ __('shared::editor.min-characters', ['min' => $min]) }})</span>
        @endif
        
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
                const max = container.dataset.max ? parseInt(container.dataset.max, 10) : null;
                const min = container.dataset.min ? parseInt(container.dataset.min, 10) : null;

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
                    const overMax = (max !== null) && count > max;
                    const underMin = (min !== null) && count < min;
                    const valid = !overMax && !underMin;
                    if (counterWrap) {
                        counterWrap.classList.toggle('text-red-600', !valid);
                        counterWrap.classList.toggle('text-gray-500', valid);
                    }
                    // Emit a validity event that bubbles up
                    container.dispatchEvent(new CustomEvent('editor-valid', {
                        detail: { id: '{{ $id }}', valid, count, min, max },
                        bubbles: true
                    }));
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

                // Initial count and validity emit
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