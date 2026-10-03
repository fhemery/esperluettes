import { describe, it, expect } from 'vitest';
import { appendReply, removeReply } from './replies.js';

const reply = (id) => ({ id, body: `<p>Réponse ${id}</p>` });

function list() {
    return [
        { id: 1, replies: [reply(10)] },
        { id: 2, replies: [] },
        { id: 3 },
    ];
}

describe('appendReply', () => {
    it('appends the reply at the end of the matching root’s thread only', () => {
        const before = list();
        const after = appendReply(before, 1, reply(11));

        expect(after[0].replies.map((r) => r.id)).toEqual([10, 11]);
        expect(after[1]).toBe(before[1]);
        expect(after[2]).toBe(before[2]);
        // The given list is not mutated.
        expect(before[0].replies.map((r) => r.id)).toEqual([10]);
    });

    it('starts a thread on a root without replies', () => {
        expect(appendReply(list(), 3, reply(12))[2].replies).toEqual([reply(12)]);
    });

    it('leaves the list as is for an unknown root', () => {
        const before = list();
        expect(appendReply(before, 99, reply(13))).toEqual(before);
    });
});

describe('removeReply', () => {
    it('removes the reply from whichever thread holds it', () => {
        const before = [{ id: 1, replies: [reply(10), reply(11)] }, { id: 2, replies: [reply(12)] }];
        const after = removeReply(before, 11);

        expect(after[0].replies.map((r) => r.id)).toEqual([10]);
        expect(after[1]).toBe(before[1]);
        expect(before[0].replies).toHaveLength(2);
    });

    it('leaves the list as is for an unknown reply', () => {
        const before = list();
        expect(removeReply(before, 99)).toEqual(before);
    });
});
