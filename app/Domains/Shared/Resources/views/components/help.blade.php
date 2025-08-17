@props([
    'icon' => 'help',
    'title' => null,
    'placement' => 'right', // right|left|top|bottom
    'width' => '20rem',
])
<span class="inline-flex items-center align-middle select-none z-10">
    <span class="ml-1 relative" x-data="popover()" @keydown.escape.window="open = false">
        <button type="button"
                class="inline-flex items-center justify-center h-5 w-5 rounded-full text-gray-500 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                aria-haspopup="dialog"
                :aria-expanded="open ? 'true' : 'false'"
                x-ref="trigger"
                @click.prevent="open = !open"
                @blur="setTimeout(() => { open = false }, 120)">
            <span class="material-symbols-outlined text-[18px] leading-none">{{ $icon }}</span>
        </button>
        <template x-teleport="body">
            <div x-cloak x-show="open" x-transition.opacity.duration.100
                 class="fixed z-[9999] p-3 rounded-md shadow-lg bg-white ring-1 ring-black/5 text-sm text-gray-700"
                 role="dialog" aria-modal="true" :aria-hidden="(!open).toString()"
                 :style="styleObj"
                 style="display:none"
                 x-init="init($refs.trigger, '{{ $placement }}', '{{ $width }}')"
                 @click.outside="open = false">
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
            open: false,
            styleObj: {},
            trigger: null,
            placement: 'right',
            width: '20rem',
            margin: 8,
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
            compute() {
                if (!this.trigger) return;
                const t = this.trigger.getBoundingClientRect();
                const vw = window.innerWidth;
                const vh = window.innerHeight;
                const panelWidth = parseFloat(this.width) || 320;
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
                if (place === 'right') return { top: t.top, left: t.right + m };
                if (place === 'left')  return { top: t.top, left: t.left - w - m };
                if (place === 'top')   return { top: t.top - 10 - m, left: t.left };
                // bottom
                return { top: t.bottom + m, left: t.left };
            },
            fits(pos, w, vw, vh) {
                const withinX = pos.left >= this.margin && (pos.left + w + this.margin) <= vw;
                const withinY = pos.top >= this.margin && pos.top <= (vh - this.margin);
                return withinX && withinY;
            }
        }
    }
</script>
