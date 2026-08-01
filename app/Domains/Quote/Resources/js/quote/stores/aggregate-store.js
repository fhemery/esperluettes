import { getChapterAggregate } from '../api/client.js';

/**
 * Author-side aggregate of a chapter's quotes.
 *
 * `visible` is the heat toggle. It starts false on every page load and is never
 * persisted — neither in localStorage nor in a user setting: authors re-read
 * their own chapters constantly and must not have to keep untoggling.
 *
 * `totalCount` is seeded server-side by the badge, so it is correct at first
 * paint; the rows themselves are fetched only when the heat is first turned on.
 */
export function createAggregateStore() {
    return {
        rows: [],
        totalCount: 0,
        loaded: false,
        loading: false,
        visible: false,

        async ensureLoaded(chapterId) {
            if (this.loaded || this.loading) return;
            this.loading = true;
            try {
                const data = await getChapterAggregate(chapterId);
                this.rows = data.items ?? [];
                this.totalCount = data.total_count ?? this.totalCount;
            } catch {
                // Keep the server-seeded count; the heat simply stays empty.
            } finally {
                this.loaded = true;
                this.loading = false;
            }
        },

        async toggle(chapterId) {
            this.visible = !this.visible;
            if (this.visible) await this.ensureLoaded(chapterId);
        },
    };
}
