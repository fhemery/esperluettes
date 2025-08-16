@props(['name', 'id', 'defaultValue', 'max' => null])
<div {{ $attributes->merge(['class' => '']) }}>
    <div class="mb-2" id="{{ $id }}"></div>
    <input type="hidden" name="{{ $name }}" id="quill-editor-area-{{ $name }}" value="{!! $defaultValue !!}" />
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

                const editor = new Quill(container, {
                    theme: 'snow',
                    modules: {
                        toolbar: [
                            ['bold', 'italic', 'underline', 'strike'],
                            [{ 'align': [] }],
                            [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                            ['clean']
                        ]
                    },
                    placeholder: ''
                });

                // Ensure a decent editing area height
                editor.root.style.minHeight = '180px';

                const quillEditor = document.getElementById('quill-editor-area-{{ $name }}');
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