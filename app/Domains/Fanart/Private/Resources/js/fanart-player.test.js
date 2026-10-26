import { describe, it, expect, vi } from 'vitest';
import { createFanartPlayer } from './fanart-player.js';

const entries = [
    { chapterId: 1, src: '/a1.mp3', markReadUrl: '/read/1' },
    { chapterId: 3, src: '/a3.mp3', markReadUrl: '/read/3' },
    { chapterId: 5, src: '/a5.mp3', markReadUrl: '/read/5' },
];

function make(fetchImpl = vi.fn().mockResolvedValue({ ok: true, status: 204 })) {
    const audio = { src: '', play: vi.fn().mockResolvedValue(undefined) };
    const player = createFanartPlayer({ audio, fetch: fetchImpl, csrf: 'tok' });
    player.setQueue(entries);
    return { player, audio, fetchImpl };
}

describe('fanartPlayer store', () => {
    it('plays the requested chapter', () => {
        const { player, audio } = make();
        player.play(3);
        expect(audio.src).toBe('/a3.mp3');
        expect(audio.play).toHaveBeenCalledTimes(1);
        expect(player.currentId).toBe(3);
    });

    it('skips chapters without audio', async () => {
        const { player, audio } = make();
        player.play(2);
        expect(audio.play).not.toHaveBeenCalled();
        expect(player.currentId).toBeNull();

        player.play(1);
        await player.onEnded();
        expect(audio.src).toBe('/a3.mp3');
        expect(player.currentId).toBe(3);
    });

    it('stops after the last', async () => {
        const { player, audio } = make();
        player.play(5);
        await player.onEnded();
        expect(audio.play).toHaveBeenCalledTimes(1);
        expect(player.currentId).toBeNull();
    });

    it('posts mark-read on end before advancing', async () => {
        const order = [];
        const fetchImpl = vi.fn(async () => {
            order.push('post');
            return { ok: true, status: 204 };
        });
        const { player, audio } = make(fetchImpl);
        audio.play.mockImplementation(async () => { order.push('play'); });
        player.play(1);
        await player.onEnded();
        expect(fetchImpl).toHaveBeenCalledWith('/read/1', expect.objectContaining({
            method: 'POST',
            headers: expect.objectContaining({ 'X-CSRF-TOKEN': 'tok' }),
        }));
        expect(order).toEqual(['play', 'post', 'play']);
        expect(player.currentId).toBe(3);
    });

    it('advances even if mark-read fails', async () => {
        const { player, audio } = make(vi.fn().mockRejectedValue(new Error('network')));
        player.play(1);
        await player.onEnded();
        expect(audio.src).toBe('/a3.mp3');
        expect(player.currentId).toBe(3);
    });
});

describe('fanartPlayer current', () => {
    it('current returns the playing entry', () => {
        const { player } = make();
        expect(player.current()).toBeNull();
        player.play(3);
        expect(player.current()).toEqual(entries[1]);
    });
});
