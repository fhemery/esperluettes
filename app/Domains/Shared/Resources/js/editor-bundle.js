import Quill from 'quill';
import Delta from 'quill-delta';
import '@windmillcode/quill-emoji/quill-emoji.css';
import 'quill/dist/quill.snow.css';

// Expose globally for existing initializer which expects window.Quill
window.Quill = Quill;
window.Delta = Delta;

export function initQuillEditor(id) {
  const run = () => {
    const container = document.getElementById(id);
    if (!container || typeof window.Quill === 'undefined') return;
    // Idempotency: prevent double initialization
    if (container.dataset.quillInited === '1') return;

    let placeholder = container ? container.dataset.placeholder : '';
    // Fix: The single quote character is encoded as &#039; in HTML attributes
    placeholder = placeholder.replace(/&#039;/g, "'");
    const allowedFormats = ['bold', 'italic', 'underline', 'strike', 'blockquote', 'list', 'align'];
    const editor = new window.Quill(container, {
      theme: 'snow',
      modules: {
        toolbar: [
          ['bold', 'italic', 'underline', 'strike'],
          ['blockquote'],
          [{ align: [] }],
          [{ list: 'ordered' }, { list: 'bullet' }],
          ['clean'],
        ],
        // No image module registered, so user cannot insert images via toolbar
      },
      // Whitelist formats so unsupported formatting is dropped on paste
      formats: allowedFormats,
      placeholder,
    });
    // Mark as initialized
    container.dataset.quillInited = '1';

    // Enhance accessibility and clarity: add tooltip to the "Remove formatting" button
    try {
      const toolbar = editor.getModule('toolbar');
      const label = container.getAttribute('data-clean-label') || 'Remove formatting';
      if (toolbar && toolbar.container) {
        const btn = toolbar.container.querySelector('button.ql-clean');
        if (btn && !btn.getAttribute('data-labeled')) {
          btn.setAttribute('title', label);
          btn.setAttribute('aria-label', label);
          btn.setAttribute('data-labeled', '1');
        }

        // Ensure toolbar buttons do not submit parent forms
        const allButtons = toolbar.container.querySelectorAll('button');
        allButtons.forEach((b) => {
          if (!b.getAttribute('type')) b.setAttribute('type', 'button');
        });
      }
    } catch (e) {
      // no-op
    }

    // Height handling: enforce min and max lines; support optional vertical resize via data-resizable
    // Defaults: min 5 lines, max nbLines (from data-nb-lines or 5)
    const nbLines = container.getAttribute('data-nb-lines') || 5;
    const minLines = 5;
    const maxLines = nbLines || 15;
    const isResizable = container.getAttribute('data-resizable') === 'true';
    const computed = window.getComputedStyle(editor.root);
    const lineHeight = parseFloat(computed.lineHeight) || 24;
    const minPx = minLines * lineHeight;
    const maxPx = maxLines * lineHeight;

    // Constrain Quill container (.ql-container)
    const qlContainer = editor.container; // .ql-container element
    if (qlContainer) {
      qlContainer.style.boxSizing = 'border-box';
      qlContainer.style.minHeight = minPx + 'px';
      if (isResizable) {
        // Allow the user to resize vertically like a textarea
        qlContainer.style.height = maxPx + 'px'; // sensible initial height
        qlContainer.style.maxHeight = '';
        qlContainer.style.resize = 'vertical';
        qlContainer.style.overflow = 'auto';
      } else {
        // Fixed container height; editor scrolls within
        qlContainer.style.height = maxPx + 'px';
        qlContainer.style.overflow = 'hidden';
      }
    }

    // Make the editor (.ql-editor) scroll inside the container
    editor.root.style.boxSizing = 'border-box';
    editor.root.style.height = '100%';
    editor.root.style.overflowY = 'auto';

    const quillEditor = document.getElementById('quill-editor-area-' + id);
    const counterEl = document.getElementById('quill-counter-' + id);
    const counterWrap = document.getElementById('quill-counter-wrap-' + id);
    const unitEl = document.getElementById('quill-unit-' + id);
    const max = container.dataset.max ? parseInt(container.dataset.max, 10) : null;
    const min = container.dataset.min ? parseInt(container.dataset.min, 10) : null;

    // Set default value if it's not empty
    const defaultValue = (quillEditor?.value || '').trim();
    if (defaultValue) {
      editor.clipboard.dangerouslyPasteHTML(defaultValue);
    }

    // Disallow pasted <img> elements (including base64 embeds)
    try {
      const Delta = window.Delta; // Provided by editor-bundle for Quill v2
      if (Delta) {
        editor.clipboard.addMatcher('IMG', function () {
          return new Delta(); // drop images entirely
        });
      }
    } catch (e) {
      // no-op if Delta not available
    }

    // Intercept paste events that include image files and block them
    const blockImageFiles = (evt) => {
      const cd = evt.clipboardData || evt.originalEvent?.clipboardData;
      if (!cd) return false;
      const items = cd.items ? Array.from(cd.items) : [];
      const hasImage = items.some(i => i.kind === 'file' && i.type && i.type.startsWith('image/'));
      if (hasImage) {
        evt.preventDefault();
        return true;
      }
      return false;
    };
    container.addEventListener('paste', blockImageFiles);

    // Intercept dropping image files
    const blockImageDrop = (evt) => {
      const dt = evt.dataTransfer;
      if (!dt) return;
      const files = dt.files ? Array.from(dt.files) : [];
      if (files.some(f => f.type && f.type.startsWith('image/'))) {
        evt.preventDefault();
      }
    };
    container.addEventListener('drop', blockImageDrop);

    // Sync Quill with the hidden input
    const updateCount = () => {
      let text = editor.getText() || '';
      if (text.endsWith('\n')) text = text.slice(0, -1); // Quill ends with a trailing newline
      const count = text.length;
      if (counterEl) {
        counterEl.textContent = max ? `${count}` : `${count}`;
      }
      // Update unit label based on pluralization
      if (unitEl) {
        const singular = unitEl.getAttribute('data-singular') || 'character';
        const plural = unitEl.getAttribute('data-plural') || 'characters';
        unitEl.textContent = (count <= 1) ? singular : plural;
      }
      const overMax = max !== null && count > max;
      const underMin = min !== null && count < min;
      const isMandatory = container.getAttribute('data-is-mandatory') === 'true';
      let valid = !overMax && !underMin;
      if (isMandatory) {
        valid = valid && count > 0;
      }
      if (counterWrap) {
        counterWrap.classList.toggle('text-red-600', !valid);
        counterWrap.classList.toggle('text-gray-500', valid);
      }
      // Emit a validity event that bubbles up
      const evt = new CustomEvent('editor-valid', {
        detail: { id, valid, count, min, max, isMandatory },
        bubbles: true,
      });
      container.dispatchEvent(evt);
    };

    editor.on('text-change', function () {
      if (quillEditor) quillEditor.value = editor.root.innerHTML;
      updateCount();
    });

    // If hidden input is changed externally, reflect it back
    quillEditor?.addEventListener('input', function () {
      editor.root.innerHTML = quillEditor.value;
      updateCount();
    });

    // Initial count and validity emit
    updateCount();
    // Fire once more on next tick to catch late-bound listeners (e.g., Alpine after DOM insertion)
    setTimeout(updateCount, 0);
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', run);
  } else {
    run();
  }
}

window.initQuillEditor = initQuillEditor;