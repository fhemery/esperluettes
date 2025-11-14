import Module from 'quill/core/module';
import { searchEmojis } from './data.js';

export default class ToolbarEmoji extends Module {
  constructor(quill, options = {}) {
    super(quill, options);
    this.options = Object.assign({
      buttonLabel: '😀',
      maxResults: 48,
    }, options);

    this.root = quill.container;
    this.toolbar = quill.getModule('toolbar');
    if (!this.toolbar || !this.toolbar.container) return;

    // Insert button at end of toolbar
    const formats = this.toolbar.container.querySelector('.ql-formats:last-child') || this.toolbar.container;
    this.button = document.createElement('button');
    this.button.className = 'ql-emoji';
    this.button.type = 'button';
    this.button.setAttribute('aria-label', 'Insert emoji');
    this.button.textContent = this.options.buttonLabel;
    Object.assign(this.button.style, {
      display: 'inline-flex',
      alignItems: 'center',
      justifyContent: 'center',
      lineHeight: '1',
      padding: '0 6px',
      marginBottom: '2px',
    });
    formats.appendChild(this.button);

    // Popover
    this.popover = document.createElement('div');
    this.popover.className = 'qe-popover';
    Object.assign(this.popover.style, {
      position: 'absolute',
      zIndex: '60',
      minWidth: '220px',
      maxWidth: '320px',
      maxHeight: '240px',
      overflow: 'auto',
      background: 'white',
      border: '1px solid #e5e7eb',
      borderRadius: '0.5rem',
      boxShadow: '0 4px 12px rgba(0,0,0,0.1)',
      padding: '6px',
      display: 'none',
    });

    const grid = document.createElement('div');
    Object.assign(grid.style, {
      display: 'grid',
      gridTemplateColumns: 'repeat(8, 1fr)',
      gap: '4px',
    });
    this.popover.appendChild(grid);

    const render = () => {
      grid.innerHTML = '';
      const items = searchEmojis('', this.options.maxResults);
      items.forEach(item => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'qe-emoji-item';
        btn.textContent = item.unicode;
        Object.assign(btn.style, {
          fontSize: '18px',
          lineHeight: '24px',
          width: '28px',
          height: '28px',
        });
        btn.addEventListener('click', (e) => {
          e.preventDefault();
          e.stopPropagation();
          const range = this.quill.getSelection(true);
          if (!range) return;
          this.quill.insertText(range.index, item.unicode, 'user');
          this.quill.setSelection(range.index + item.unicode.length, 0, 'user');
          // Close popover after inserting emoji
          render();
          this.hide();
        });
        grid.appendChild(btn);
      });
    };
    render();

    // Toggle open/close
    this.button.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      if (this.popover.style.display === 'none') this.show(); else this.hide();
    });

    document.addEventListener('click', (e) => {
      if (!this.popover.contains(e.target) && e.target !== this.button) this.hide();
    });

    // Insert into DOM near toolbar
    this.toolbar.container.style.position = this.toolbar.container.style.position || 'relative';
    this.toolbar.container.appendChild(this.popover);
  }

  show() {
    // Position under the emoji button
    const btnRect = this.button.getBoundingClientRect();
    const tbRect = this.toolbar.container.getBoundingClientRect();
    const top = btnRect.bottom - tbRect.top + 6;
    const left = btnRect.left - tbRect.left;
    this.popover.style.top = `${top}px`;
    this.popover.style.left = `${left}px`;
    this.popover.style.display = 'block';
  }

  hide() {
    this.popover.style.display = 'none';
  }
}
