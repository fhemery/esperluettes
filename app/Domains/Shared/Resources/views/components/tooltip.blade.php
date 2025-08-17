@props([
    'type' => 'info', // info|help
    'icon' => null,   // optional override
    'title' => null,
    'placement' => 'right', // right|left|top|bottom
    'width' => '20rem',
])
@php($resolvedIcon = $icon ?? ($type === 'help' ? 'help' : 'info'))
<span class="inline-flex items-center align-middle select-none z-10">
    <span class="ml-1 relative" x-data="popover()" @keydown.escape.window="hoverOpen = false; pinned = false; updateOpen()">
        <button type="button"
                class="inline-flex items-center justify-center h-5 w-5 rounded-full text-gray-500 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                aria-haspopup="dialog"
                :aria-expanded="(hoverOpen || pinned) ? 'true' : 'false'"
                x-ref="trigger"
                @mouseenter="hoverOpen = true; updateOpen()"
                @mouseleave="closeWithDelay()"
                @mousedown.stop.prevent="pinned = !pinned; if (pinned) { hoverOpen = true } updateOpen()"
                @click.stop.prevent
                @blur="setTimeout(() => { if (!pinned) { hoverOpen = false; updateOpen() } }, 220)">
            <span class="material-symbols-outlined text-[18px] leading-none">{{ $resolvedIcon }}</span>
        </button>
        <template x-teleport="body">
            <div x-cloak x-show="hoverOpen || pinned" x-transition.opacity.duration.100
                 class="fixed z-[9999] p-3 rounded-md shadow-lg bg-white ring-1 ring-black/5 text-sm text-gray-700"
                 role="dialog" aria-modal="true" :aria-hidden="(!open).toString()"
                 x-ref="panel"
                 :style="styleObj"
                 style="display:none"
                 x-init="init($refs.trigger, '{{ $placement }}', '{{ $width }}')"
                 x-effect="(hoverOpen || pinned) && measureAndCompute()"
                 @mouseenter="hoverOpen = true; updateOpen()"
                 @mouseleave="closeWithDelay()"
                 @click.outside="if (this.trigger && this.trigger.contains($event.target)) { return } pinned = false; hoverOpen = false; updateOpen()">
                @if($title)
                    <div class="font-semibold text-gray-900 mb-1">{{ $title }}</div>
                @endif
                <div class="prose prose-sm max-w-none">
                    {!! trim($slot) !== '' ? $slot : '' !!}
                </div>
            </div>
        </template>
    </span>
</span>

<script>
    function popover() {
        return {
            open: false, // derived: hoverOpen || pinned (kept for backward compat if referenced)
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
                // Measure panel height when visible, then recompute position (needed for top placement)
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
                // Allow a slightly longer delay so the pointer can reach the panel without flicker
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
                // Clamp within viewport as a last resort
                pos.left = Math.min(Math.max(this.margin, pos.left), vw - panelWidth - this.margin);
                pos.top = Math.max(this.margin, pos.top);
                this.styleObj = {
                    top: pos.top + 'px',
                    left: pos.left + 'px',
                    width: this.width,
                    maxWidth: '90vw',
                };
            },
            order(primary) {
                const all = ['right','left','bottom','top'];
                return [primary, ...all.filter(p => p !== primary)];
            },
            positionFor(place, t, w) {
                const m = this.margin;
                // Right/left align to the top of the trigger
                if (place === 'right') return { top: t.top, left: t.right + m };
                if (place === 'left')  return { top: t.top, left: t.left - w - m };
                // Top: center horizontally, and set bottom edge just above trigger using measured height
                if (place === 'top')   return { top: t.top - this.panelH - m, left: t.left + (t.width / 2) - (w / 2) };
                // bottom
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
                return isNaN(n) ? 0 : n; // assume pixels
            },
            fits(pos, w, vw, vh) {
                const withinX = pos.left >= this.margin && (pos.left + w + this.margin) <= vw;
                const withinY = pos.top >= this.margin && pos.top <= (vh - this.margin);
                return withinX && withinY;
            }
        }
    }
</script>
