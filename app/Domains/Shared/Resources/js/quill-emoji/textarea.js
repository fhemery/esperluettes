import Module from 'quill/core/module';
import { searchEmojis } from './data.js';

export default class TextAreaEmoji extends Module {
  constructor(quill, options = {}) {
    super(quill, options);
    this.options = Object.assign({
      minChars: 2,
      maxResults: 8,
      trigger: ':',
      debounceMs: 120,
    }, options);

    this.container = quill.container;
    this.root = quill.root;
    this.menu = this._createMenu();
    this.visible = false;
    this.activeIndex = 0;
    this.items = [];
    this.triggerStart = null; // absolute index where ':' begins
    this._debounceTimer = null; // pending render timer
    this._inserting = false; // guard to ignore our own edits

    this._attachHandlers();
  }

  _createMenu(){
    const m = document.createElement('div');
    m.className = 'qe-colon-menu';
    Object.assign(m.style, {
      position: 'absolute',
      zIndex: '60',
      minWidth: '160px',
      maxWidth: '260px',
      background: 'white',
      border: '1px solid #e5e7eb',
      borderRadius: '0.5rem',
      boxShadow: '0 4px 12px rgba(0,0,0,0.1)',
      display: 'none',
      overflow: 'hidden',
    });
    this.list = document.createElement('div');
    this.list.setAttribute('role', 'listbox');
    Object.assign(this.list.style, {
      maxHeight: '220px',
      overflowY: 'auto',
      padding: '4px',
    });
    m.appendChild(this.list);
    this.container.style.position = this.container.style.position || 'relative';
    this.container.appendChild(m);
    return m;
  }

  _attachHandlers(){
    const debounced = (q) => {
      if (this._debounceTimer) clearTimeout(this._debounceTimer);
      this._debounceTimer = setTimeout(() => {
        this._render(q);
      }, this.options.debounceMs);
    };
    this.quill.on('text-change', () => {
      if (this._inserting) {
        // ignore the change triggered by our own delete/insert
        return;
      }
     
      const { query, index, startIndex } = this._currentQuery();
      if (query && query.length >= this.options.minChars) {
        debounced(query);
        this.triggerStart = startIndex;
        this._positionAt(index);
      } else {
        this.hide();
        this.triggerStart = null;
      }
    });

    this.quill.root.addEventListener('keydown', (e) => {
      if (!this.visible) return;
      if (e.key === 'ArrowDown') { e.preventDefault(); this._move(1); }
      else if (e.key === 'ArrowUp') { e.preventDefault(); this._move(-1); }
      else if (e.key === 'Enter') { e.preventDefault(); this._choose(); }
      else if (e.key === 'Escape') { e.preventDefault(); this.hide(); }
    });

    document.addEventListener('click', (e) => {
      if (this.visible && !this.menu.contains(e.target)) this.hide();
    });
  }

  _currentQuery(){
    const range = this.quill.getSelection();
    if (!range) return { query: '', index: 0 };
    const [line, offset] = this.quill.getLine(range.index);
    const text = line.domNode.textContent || '';
    const upto = text.slice(0, offset);
    const idx = upto.lastIndexOf(this.options.trigger);
    if (idx === -1) return { query: '', index: range.index };
    const query = upto.slice(idx + 1);
    const deleteCount = offset - idx; // includes ':' and following query
    const startIndex = range.index - deleteCount;
    return { query, index: range.index, startIndex };
  }

  _positionAt(index){
    const bounds = this.quill.getBounds(index);
    const editorRect = this.container.getBoundingClientRect();
    const rowHeight = 32; // approximate height of a single emoji row
    const menuMaxWidth = 260; // matches maxWidth style

    // Prefer below caret if there is room for at least one row
    let top = bounds.bottom + 6;
    if (top + rowHeight > editorRect.height) {
      // Not enough space below: place above caret
      top = Math.max(0, bounds.top - 6 - rowHeight);
    }

    let left = bounds.left;
    if (left + menuMaxWidth > editorRect.width) {
      left = Math.max(0, editorRect.width - menuMaxWidth - 4);
    }

    this.menu.style.top = `${top}px`;
    this.menu.style.left = `${left}px`;
  }

  _render(q){
    this.items = searchEmojis(q, this.options.maxResults);
    this.list.innerHTML = '';
    this.items.forEach((it, i) => {
      const row = document.createElement('div');
      row.setAttribute('role', 'option');
      row.tabIndex = -1;
      row.className = 'qe-colon-item';
      Object.assign(row.style, {
        display: 'grid',
        gridTemplateColumns: '24px 1fr',
        gap: '6px',
        alignItems: 'center',
        padding: '4px 8px',
        borderRadius: '6px',
        cursor: 'pointer',
        background: i === this.activeIndex ? '#f3f4f6' : 'transparent',
      });
      const emoji = document.createElement('div');
      emoji.textContent = it.unicode;
      emoji.style.fontSize = '18px';
      const label = document.createElement('div');
      label.textContent = `:${it.shortname}:`;
      label.style.fontSize = '12px';
      row.appendChild(emoji);
      row.appendChild(label);
      row.addEventListener('mouseenter', () => { this._setActive(i); });
      row.addEventListener('click', (e) => { e.preventDefault(); e.stopPropagation(); this._insert(it); });
      this.list.appendChild(row);
    });
    if (this.items.length) this.show(); else this.hide();
  }

  _move(delta){
    if (!this.items.length) return;
    const n = this.items.length;
    this.activeIndex = (this.activeIndex + delta + n) % n;
    this._renderActive();
  }

  _setActive(i){
    this.activeIndex = i;
    this._renderActive();
  }

  _renderActive(){
    Array.from(this.list.children).forEach((el, i) => {
      el.style.background = i === this.activeIndex ? '#f3f4f6' : 'transparent';
    });
  }

  _choose(){
    if (!this.items.length) return;
    this._insert(this.items[this.activeIndex]);
  }

  _insert(item){
    const range = this.quill.getSelection(true);
    if (!range) return;
    // prevent pending renders from re-opening
    if (this._debounceTimer) { clearTimeout(this._debounceTimer); this._debounceTimer = null; }
    this._inserting = true;
    let startIndex = this.triggerStart != null ? this.triggerStart : range.index;
    let deleteLen = this.triggerStart != null ? (range.index - this.triggerStart) : 0;
    // If we cannot compute from cached trigger, recompute based on current context
    if (deleteLen <= 0) {
      const { query, index, startIndex: si } = this._currentQuery();
      if (si != null && query) {
        startIndex = si;
        deleteLen = index - si;
      }
    }
    if (deleteLen > 0) {
      this.quill.deleteText(startIndex, deleteLen, 'user');
    }
    this.quill.insertText(startIndex, item.unicode, 'user');
    this.quill.setSelection(startIndex + item.unicode.length, 0, 'user');
    this.hide();
    this.triggerStart = null;
    // release guard after this event loop so our text-change gets ignored
    setTimeout(() => { this._inserting = false; }, 0);
  }

  show(){
    this.menu.style.display = 'block';
    this.visible = true;
  }

  hide(){
    this.menu.style.display = 'none';
    this.visible = false;
    this.activeIndex = 0;
    this.items = [];
    this.list.innerHTML = '';
    if (this._debounceTimer) { clearTimeout(this._debounceTimer); this._debounceTimer = null; }
  }

  // no generic debounce helper; we use explicit timer per instance
}
