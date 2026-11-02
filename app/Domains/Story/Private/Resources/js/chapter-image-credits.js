// Live "pending image uploads vs credit balance" counter of the chapter form.
//
// Informational only: the server stays authoritative and submit is never blocked.
// It reads the public DOM contract of <x-media::image-field> (the
// `blocks[<uid>][file]` input) instead of Media's internal Alpine state.

const FILE_INPUT = 'input[type=file][name^="blocks["][name$="[file]"]';

export function countPendingUploads(root) {
    const mode = root.querySelector('input[name="mode"]');
    if (!mode || mode.value !== 'advanced') {
        return 0;
    }
    return [...root.querySelectorAll(FILE_INPUT)].filter((input) => input.files && input.files.length > 0).length;
}

export function buildLabel({ count, balance, creationCost }, labels) {
    const total = creationCost + count;
    const set = creationCost > 0 ? labels.create : labels.edit;
    const quantity = creationCost > 0 ? total : count;
    const template = quantity === 1 || quantity === 0 ? set.one : set.other;
    const text = template
        .replaceAll(':total', total)
        .replaceAll(':count', count)
        .replaceAll(':balance', balance);
    return total > balance ? text + labels.insufficient : text;
}

export function chapterImageCredits({ balance, creationCost, labels }) {
    return {
        count: 0,
        observer: null,

        init() {
            const root = this.$el;
            const recount = () => setTimeout(() => { this.count = countPendingUploads(root); }, 0);
            // click: the image-field clears its input from remove / library buttons without a change event.
            ['change', 'drop', 'click'].forEach((type) => root.addEventListener(type, recount));
            this.observer = new MutationObserver(recount);
            this.observer.observe(root, { childList: true, subtree: true, attributes: true, attributeFilter: ['value'] });
            recount();
        },

        destroy() {
            this.observer?.disconnect();
        },

        get label() {
            return buildLabel({ count: this.count, balance, creationCost }, labels);
        },
    };
}

document.addEventListener('alpine:init', () => {
    window.Alpine.data('chapterImageCredits', chapterImageCredits);
});
