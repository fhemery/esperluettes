import { describe, it, expect } from 'vitest';
import { groupPassages, segmentByDepth } from './author-summary.js';

function row(id, text, quoterId, range = { start: 0, end: 1 }) {
    return {
        id,
        highlighted_text: text,
        prefix: '',
        suffix: '',
        created_at: '2026-08-01T10:00:00+00:00',
        quoter: { user_id: quoterId, display_name: `Reader ${quoterId}`, slug: `reader-${quoterId}`, avatar_url: null },
        range,
    };
}

describe('groupPassages', () => {
    it('returns an empty list for an empty input', () => {
        expect(groupPassages([])).toEqual([]);
    });

    it('groups rows sharing the exact same text', () => {
        const groups = groupPassages([row(1, 'le chat dort', 10), row(2, 'le chat dort', 11)]);

        expect(groups).toHaveLength(1);
        expect(groups[0].count).toBe(2);
        expect(groups[0].text).toBe('le chat dort');
        expect(groups[0].rows.map(r => r.id)).toEqual([1, 2]);
    });

    it('normalises leading, trailing and repeated whitespace', () => {
        const groups = groupPassages([
            row(1, '  le chat   dort\n', 10),
            row(2, 'le chat dort', 11),
        ]);

        expect(groups).toHaveLength(1);
        expect(groups[0].key).toBe('le chat dort');
    });

    it('keeps passages that differ by case apart', () => {
        const groups = groupPassages([row(1, 'Le chat dort', 10), row(2, 'le chat dort', 11)]);

        expect(groups).toHaveLength(2);
    });

    it('lists the distinct readers of a passage, without duplicating one reader', () => {
        const groups = groupPassages([
            row(1, 'le chat dort', 10),
            row(2, 'le chat dort', 10),
            row(3, 'le chat dort', 11),
        ]);

        expect(groups[0].count).toBe(3);
        expect(groups[0].readers.map(r => r.user_id)).toEqual([10, 11]);
    });

    it('orders passages by count descending', () => {
        const groups = groupPassages([
            row(1, 'une fois', 10),
            row(2, 'trois fois', 10),
            row(3, 'trois fois', 11),
            row(4, 'trois fois', 12),
            row(5, 'deux fois', 10),
            row(6, 'deux fois', 11),
        ]);

        expect(groups.map(g => g.key)).toEqual(['trois fois', 'deux fois', 'une fois']);
    });

    it('marks a passage with no resolved range as stale and sorts it after the live ones', () => {
        const groups = groupPassages([
            row(1, 'passage disparu', 10, null),
            row(2, 'passage disparu', 11, null),
            row(3, 'passage vivant', 12),
        ]);

        expect(groups.map(g => g.key)).toEqual(['passage vivant', 'passage disparu']);
        expect(groups[0].stale).toBe(false);
        expect(groups[1].stale).toBe(true);
        expect(groups[1].count).toBe(2);
    });

    it('treats a passage as live as soon as one of its rows resolved', () => {
        const groups = groupPassages([
            row(1, 'le chat dort', 10, null),
            row(2, 'le chat dort', 11),
        ]);

        expect(groups[0].stale).toBe(false);
    });
});

describe('segmentByDepth', () => {
    it('returns an empty list for an empty input', () => {
        expect(segmentByDepth([])).toEqual([]);
    });

    it('keeps disjoint ranges at depth 1', () => {
        expect(segmentByDepth([{ start: 0, end: 5 }, { start: 10, end: 15 }])).toEqual([
            { start: 0, end: 5, depth: 1 },
            { start: 10, end: 15, depth: 1 },
        ]);
    });

    it('splits a partial overlap into three segments of depth 1, 2, 1', () => {
        expect(segmentByDepth([{ start: 0, end: 10 }, { start: 5, end: 15 }])).toEqual([
            { start: 0, end: 5, depth: 1 },
            { start: 5, end: 10, depth: 2 },
            { start: 10, end: 15, depth: 1 },
        ]);
    });

    it('deepens the nested part of a fully nested range', () => {
        expect(segmentByDepth([{ start: 0, end: 20 }, { start: 5, end: 10 }])).toEqual([
            { start: 0, end: 5, depth: 1 },
            { start: 5, end: 10, depth: 2 },
            { start: 10, end: 20, depth: 1 },
        ]);
    });

    it('returns a single segment of depth 2 for two identical ranges', () => {
        expect(segmentByDepth([{ start: 3, end: 8 }, { start: 3, end: 8 }])).toEqual([
            { start: 3, end: 8, depth: 2 },
        ]);
    });

    it('produces no zero-width segment for adjacent ranges', () => {
        const segments = segmentByDepth([{ start: 0, end: 5 }, { start: 5, end: 9 }]);

        expect(segments).toEqual([
            { start: 0, end: 5, depth: 1 },
            { start: 5, end: 9, depth: 1 },
        ]);
        expect(segments.every(s => s.end > s.start)).toBe(true);
    });

    it('ignores empty and inverted ranges', () => {
        expect(segmentByDepth([{ start: 4, end: 4 }, { start: 9, end: 2 }, { start: 0, end: 3 }])).toEqual([
            { start: 0, end: 3, depth: 1 },
        ]);
    });

    it('counts three overlapping ranges up to depth 3', () => {
        expect(segmentByDepth([
            { start: 0, end: 9 },
            { start: 3, end: 12 },
            { start: 6, end: 15 },
        ])).toEqual([
            { start: 0, end: 3, depth: 1 },
            { start: 3, end: 6, depth: 2 },
            { start: 6, end: 9, depth: 3 },
            { start: 9, end: 12, depth: 2 },
            { start: 12, end: 15, depth: 1 },
        ]);
    });
});
