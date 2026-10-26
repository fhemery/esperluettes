// Story-page audio player: one <audio> element, a queue of chapters with a published audio.
export function createFanartPlayer(deps = {}) {
    return {
        queue: [],
        currentId: null,
        audio: deps.audio ?? null,
        csrf: deps.csrf ?? '',
        request: deps.fetch ?? ((...args) => window.fetch(...args)),

        // Called by the story-player component once its <audio> element exists.
        attach({ audio, csrf, entries }) {
            this.audio = audio;
            this.csrf = csrf;
            this.setQueue(entries);
        },

        setQueue(entries) {
            this.queue = entries;
            this.currentId = null;
        },

        // The queue entry being played, or null.
        current() {
            return this.queue.find((e) => e.chapterId === this.currentId) ?? null;
        },

        play(chapterId) {
            const entry = this.queue.find((e) => e.chapterId === chapterId);
            if (!entry || !this.audio) return;
            this.currentId = entry.chapterId;
            this.audio.src = entry.src;
            this.audio.play()?.catch(() => {});
        },

        // Marks the finished chapter read (failure ignored), then plays the next entry or stops.
        async onEnded() {
            const index = this.queue.findIndex((e) => e.chapterId === this.currentId);
            if (index === -1) return;
            const entry = this.queue[index];

            try {
                await this.request(entry.markReadUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': this.csrf, Accept: 'text/plain' },
                });
            } catch (e) {
                // Reading progress is best effort: keep listening.
            }

            if (this.currentId !== entry.chapterId) return; // the listener picked another chapter meanwhile
            const next = this.queue[index + 1];
            if (next) {
                this.play(next.chapterId);
            } else {
                this.currentId = null;
            }
        },
    };
}

document.addEventListener('alpine:init', () => {
    Alpine.store('fanartPlayer', createFanartPlayer());
});
