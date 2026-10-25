import { fetchFragment } from '../../../../Shared/Resources/js/fragment-fetch.js';

const FOCUSABLE = 'a[href], button:not([disabled]), textarea, input:not([type="hidden"]), select, [tabindex]:not([tabindex="-1"])';

// Modal that shows the viewer fragment (image/audio, details, report, review form).
export function fanartViewer() {
    return {
        isOpen: false,
        html: '',
        opener: null,
        requestId: 0,

        async open(url) {
            this.opener = document.activeElement;
            this.html = '';
            this.isOpen = true;
            document.body.classList.add('overflow-hidden');
            const requestId = ++this.requestId;
            this.$nextTick(() => this.$refs.closeButton?.focus());

            const html = await fetchFragment(url);
            if (requestId !== this.requestId) return;
            if (html === null) {
                this.close();
                return;
            }
            this.html = html;
        },

        close() {
            if (!this.isOpen) return;
            this.isOpen = false;
            this.requestId++;
            this.html = '';
            document.body.classList.remove('overflow-hidden');
            if (this.opener && document.contains(this.opener)) this.opener.focus();
            this.opener = null;
        },

        trapFocus(event) {
            if (!this.isOpen) return;
            const items = Array.from(this.$refs.panel.querySelectorAll(FOCUSABLE))
                .filter((el) => el.offsetParent !== null);
            if (items.length === 0) {
                event.preventDefault();
                return;
            }
            const first = items[0];
            const last = items[items.length - 1];
            if (event.shiftKey && (document.activeElement === first || !this.$refs.panel.contains(document.activeElement))) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        },
    };
}

document.addEventListener('alpine:init', () => {
    Alpine.data('fanartViewer', fanartViewer);
});
