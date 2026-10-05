import { describe, it, expect } from 'vitest';
import { groupByPosition } from './gutter.js';

const item = (key, top, start, { visible = true, isRoot = true } = {}) => ({ key, top, start, visible, isRoot });

describe('groupByPosition', () => {
    it('groups two items 10px apart, the second counted as +1', () => {
        const groups = groupByPosition([item('a', 100, 0), item('b', 110, 50)]);

        expect(groups).toHaveLength(1);
        expect(groups[0].lead.key).toBe('a');
        expect(groups[0].extra.map((i) => i.key)).toEqual(['b']);
    });

    it('splits two items 25px apart into two groups', () => {
        const groups = groupByPosition([item('a', 100, 0), item('b', 125, 50)]);

        expect(groups.map((g) => g.lead.key)).toEqual(['a', 'b']);
        expect(groups.every((g) => g.extra.length === 0)).toBe(true);
    });

    it('chains a band on consecutive gaps', () => {
        const groups = groupByPosition([item('a', 100, 0), item('b', 115, 10), item('c', 130, 20)]);

        expect(groups).toHaveLength(1);
        expect(groups[0].extra.map((i) => i.key)).toEqual(['b', 'c']);
    });

    it('sorts its input by top, then canonical start', () => {
        const groups = groupByPosition([item('late', 300, 90), item('b', 100, 40), item('a', 100, 10)]);

        expect(groups.map((g) => g.lead.key)).toEqual(['a', 'late']);
        expect(groups[0].extra.map((i) => i.key)).toEqual(['b']);
    });

    it('takes the first visible item as lead', () => {
        const groups = groupByPosition([item('hidden', 100, 0, { visible: false }), item('b', 105, 10)]);

        expect(groups[0].lead.key).toBe('b');
        expect(groups[0].extra).toEqual([]);
        expect(groups[0].top).toBe(105);
    });

    it('never counts replies', () => {
        const groups = groupByPosition([
            item('a', 100, 0),
            item('reply', 102, 5, { isRoot: false }),
            item('b', 104, 10),
        ]);

        expect(groups[0].extra.map((i) => i.key)).toEqual(['b']);
    });

    it('drops a group with no visible item', () => {
        const groups = groupByPosition([
            item('x', 100, 0, { visible: false }),
            item('y', 105, 5, { visible: false }),
            item('c', 200, 50),
        ]);

        expect(groups.map((g) => g.lead.key)).toEqual(['c']);
    });

    it('keeps input order on equal tops and starts', () => {
        const groups = groupByPosition([item('first', 100, 10), item('second', 100, 10), item('third', 100, 10)]);

        expect(groups[0].lead.key).toBe('first');
        expect(groups[0].extra.map((i) => i.key)).toEqual(['second', 'third']);
    });

    it('keys the group by its lead and honours a custom gap', () => {
        const groups = groupByPosition([item('a', 100, 0), item('b', 125, 10)], 30);

        expect(groups).toHaveLength(1);
        expect(groups[0].key).toBe('a');
    });

    it('returns no group for no item', () => {
        expect(groupByPosition([])).toEqual([]);
    });
});
