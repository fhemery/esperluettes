@props(['name', 'id', 'defaultValue'])
<div {{ $attributes->merge(['class' => '']) }}>
    <div class="mb-10" id="{{ $id }}"></div>
    <input type="hidden" name="{{ $name }}" id="quill-editor-area-{{ $name }}" value="{!! $defaultValue !!}" />
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

                // Set default value if it's not empty
                const defaultValue = (quillEditor.value || '').trim();
                if (defaultValue) {
                    editor.clipboard.dangerouslyPasteHTML(defaultValue);
                }

                // Sync Quill with the hidden input
                editor.on('text-change', function () {
                    quillEditor.value = editor.root.innerHTML;
                });

                // If hidden input is changed externally, reflect it back
                quillEditor.addEventListener('input', function () {
                    editor.root.innerHTML = quillEditor.value;
                });
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