/**
 * Tooltip / Popover Alpine component
 *
 * Usage in Blade:
 *   <span x-data="popover()">
 *     ... trigger & panel markup ...
 *   </span>
 *
 * Expected refs & wiring:
 *   - x-ref="trigger" on the trigger button
 *   - The panel can be teleported to body and should bind :style="styleObj"
 *   - Call x-init="init($refs.trigger, '<placement>', '<width>')"
 *   - Optionally call x-effect="(hoverOpen || pinned) && measureAndCompute()" to re-measure when shown
 *
 * Placements: right | left | top | bottom
 * Width: any CSS size (e.g. '20rem', '280px')
 */
export default function registerTooltip(Alpine) {
  Alpine.data('popover', () => ({
    open: false, // derived
    hoverOpen: false,
    pinned: false,
    styleObj: {},
    trigger: null,
    placement: 'right',
    width: '20rem',
    margin: 8,
    panelH: 0,
    panelW: 0,
    init(trigger, placement, width) {
      this.trigger = trigger;
      this.placement = placement || 'right';
      this.width = width || '20rem';
      this.$nextTick(() => {
        this.compute();
        window.addEventListener('resize', this.compute.bind(this));
        window.addEventListener('scroll', this.compute.bind(this), true);
      });
    },
    measureAndCompute() {
      this.$nextTick(() => {
        const el = this.$refs.panel;
        if (el) {
          const rect = el.getBoundingClientRect();
          this.panelH = rect.height || el.scrollHeight || 0;
          this.panelW = rect.width || el.scrollWidth || 0;
        }
        this.compute();
      });
    },
    closeWithDelay() {
      setTimeout(() => { if (!this.pinned) { this.hoverOpen = false; this.updateOpen() } }, 220);
    },
    updateOpen() {
      this.open = this.hoverOpen || this.pinned;
    },
    compute() {
      if (!this.trigger) return;
      const t = this.trigger.getBoundingClientRect();
      const vw = window.innerWidth;
      const vh = window.innerHeight;
      const panelWidth = this.panelW || this.parseWidth(this.width) || 320;
      const candidates = this.order(this.placement);
      let pos = null;
      for (const place of candidates) {
        pos = this.positionFor(place, t, panelWidth);
        if (this.fits(pos, panelWidth, vw, vh)) { break; }
      }
      // Clamp within viewport
      pos.left = Math.min(Math.max(this.margin, pos.left), vw - panelWidth - this.margin);
      pos.top = Math.max(this.margin, pos.top);
      this.styleObj = { top: pos.top + 'px', left: pos.left + 'px', width: this.width, maxWidth: '90vw' };
    },
    order(primary) {
      const all = ['right','left','bottom','top'];
      return [primary, ...all.filter(p => p !== primary)];
    },
    positionFor(place, t, w) {
      const m = this.margin;
      if (place === 'right') return { top: t.top, left: t.right + m };
      if (place === 'left')  return { top: t.top, left: t.left - w - m };
      if (place === 'top')   return { top: t.top - this.panelH - m, left: t.left + (t.width / 2) - (w / 2) };
      return { top: t.bottom + m, left: t.left + (t.width / 2) - (w / 2) };
    },
    parseWidth(width) {
      if (!width) return 0;
      if (typeof width === 'number') return width;
      const str = String(width).trim();
      if (str.endsWith('rem')) {
        const n = parseFloat(str);
        const fs = parseFloat(getComputedStyle(document.documentElement).fontSize) || 16;
        return n * fs;
      }
      if (str.endsWith('px')) return parseFloat(str);
      const n = parseFloat(str);
      return isNaN(n) ? 0 : n;
    },
    fits(pos, w, vw, vh) {
      const withinX = pos.left >= this.margin && (pos.left + w + this.margin) <= vw;
      const withinY = pos.top >= this.margin && pos.top <= (vh - this.margin);
      return withinX && withinY;
    }
  }));
}
