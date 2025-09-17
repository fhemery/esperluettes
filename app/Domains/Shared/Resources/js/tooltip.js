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
 *   - Call x-init="init($refs.trigger, '<placement>', '<maxWidth>')"
 *   - Optionally call x-effect="(hoverOpen || pinned) && measureAndCompute()" to re-measure when shown
 *
 * Placements: right | left | top | bottom
 * Max width: any CSS size (e.g. '20rem', '280px'). Actual panel shrinks to content.
 */
export default function registerTooltip(Alpine) {
  Alpine.data('popover', () => ({
    open: false, // derived
    hoverOpen: false,
    hoverPanel: false,
    hoverTrigger: false,
    pinned: false,
    styleObj: {},
    trigger: null,
    placement: 'right',
    maxWidth: '20rem',
    margin: 8,
    panelH: 0,
    panelW: 0,
    init(trigger, placement, maxWidth) {
      this.trigger = trigger;
      this.placement = placement || 'right';
      this.maxWidth = maxWidth || '20rem';
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
          // Bind hover listeners once to prevent blinking when panel overlaps trigger
          if (!el._popoverHoverBound) {
            el.addEventListener('mouseenter', () => {
              this.hoverPanel = true;
              this.hoverOpen = true;
              this.updateOpen();
            });
            el.addEventListener('mouseleave', () => {
              this.hoverPanel = false;
              this.closeWithDelay();
            });
            el._popoverHoverBound = true;
          }
        }
        this.compute();
      });
    },
    closeWithDelay() {
      setTimeout(() => {
        // Do not close if still hovering either the trigger (managed by Alpine bindings)
        // or the panel itself (tracked here). Only close when neither is hovered and not pinned.
        if (!this.pinned && !this.hoverPanel && !this.hoverTrigger) {
          this.hoverOpen = false;
          this.updateOpen();
        }
      }, 220);
    },
    updateOpen() {
      this.open = (this.hoverOpen || this.hoverPanel || this.hoverTrigger || this.pinned);
    },
    compute() {
      if (!this.trigger) return;
      const t = this.trigger.getBoundingClientRect();
      const vw = window.innerWidth;
      const vh = window.innerHeight;
      // Use measured width when available; otherwise estimate from maxWidth.
      // Also clamp by viewport max (accounting for margins) to avoid offscreen.
      const maxFromProp = this.parseSize(this.maxWidth) || 320;
      const maxByViewport = Math.max(0, vw - this.margin * 2);
      const estimatedW = Math.min(maxFromProp, maxByViewport);
      const panelWidth = this.panelW || estimatedW;
      const candidates = this.order(this.placement);
      let pos = { top: 0, left: 0 };
      for (const place of candidates) {
        const candidate = this.positionFor(place, t, panelWidth);
        if (this.fits(candidate, panelWidth, vw, vh)) {
          pos = candidate;
          break;
        } else {
          pos = candidate; // fallback to last computed and clamp below
        }
      }
      // Clamp within viewport (both axes)
      const h = this.panelH || 0;
      const clamp = (min, val, max) => Math.min(Math.max(min, val), max);
      pos.left = clamp(this.margin, pos.left, vw - panelWidth - this.margin);
      pos.top = clamp(this.margin, pos.top, vh - h - this.margin);

      // Set safe max dimensions and enable scrolling inside the panel as needed
      const maxWViewport = `calc(100vw - ${this.margin * 2}px)`;
      const maxH = `calc(100vh - ${this.margin * 2}px)`;
      this.styleObj = {
        top: pos.top + 'px',
        left: pos.left + 'px',
        maxWidth: `min(${this.maxWidth}, ${maxWViewport})`,
        maxHeight: maxH,
        overflowY: 'auto',
      };
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
    parseSize(size) {
      if (!size) return 0;
      if (typeof size === 'number') return size;
      const str = String(size).trim();
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
      const h = this.panelH || 0;
      const withinX = pos.left >= this.margin && (pos.left + w + this.margin) <= vw;
      const withinY = pos.top >= this.margin && (pos.top + h + this.margin) <= vh;
      return withinX && withinY;
    }
  }));
}

