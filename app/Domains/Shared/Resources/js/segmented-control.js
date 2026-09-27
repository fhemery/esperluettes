/**
 * Segmented control — single-choice button group (WAI-ARIA radio group).
 *
 * Rendered by <x-shared::segmented-control>. On init and on every change it
 * dispatches `segmented-control-change` on window with `{ name, value }`, and
 * mirrors the current value in `data-value` on its root for late listeners.
 * With a `storageKey`, the value is remembered in localStorage; an unreadable
 * or unknown stored value falls back to `selected`.
 */
export default function registerSegmentedControl(Alpine) {
    Alpine.data('segmentedControl', ({ name, options, selected, storageKey = null }) => ({
        value: selected,

        init() {
            this.value = this.readStored() ?? selected;
            this.dispatch();
        },

        readStored() {
            if (!storageKey) return null;
            try {
                const stored = localStorage.getItem(storageKey);
                return options.includes(stored) ? stored : null;
            } catch (e) {
                return null;
            }
        },

        select(value) {
            if (!options.includes(value) || value === this.value) return;
            this.value = value;
            if (storageKey) {
                try {
                    localStorage.setItem(storageKey, value);
                } catch (e) {
                    // Storage unavailable: the choice simply is not remembered.
                }
            }
            this.dispatch();
        },

        dispatch() {
            this.$el.dataset.value = this.value;
            window.dispatchEvent(new CustomEvent('segmented-control-change', {
                detail: { name, value: this.value },
            }));
        },

        onKeydown(event) {
            const current = options.indexOf(this.value);
            const last = options.length - 1;
            const targets = {
                ArrowRight: current === last ? 0 : current + 1,
                ArrowDown: current === last ? 0 : current + 1,
                ArrowLeft: current <= 0 ? last : current - 1,
                ArrowUp: current <= 0 ? last : current - 1,
                Home: 0,
                End: last,
            };
            if (!(event.key in targets)) return;
            event.preventDefault();
            const index = targets[event.key];
            this.select(options[index]);
            this.$el.querySelectorAll('[role="radio"]')[index]?.focus();
        },
    }));
}
