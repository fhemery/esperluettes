import { describe, it, expect } from 'vitest';
import { placePanel } from './popover.js';

const trigger = { top: 200, bottom: 224, left: 340, right: 364, width: 24 };

describe('placePanel', () => {
    it('sits beside the avatar on a wide viewport', () => {
        const pos = placePanel(trigger, 320, 200, 1400, 900);
        expect(pos.left).toBe(364 + 8);
        expect(pos.top).toBe(200);
    });

    it('is centered under the avatar below lg', () => {
        for (const viewport of [375, 1000]) {
            const pos = placePanel(trigger, 320, 200, viewport, 800);
            expect(pos.left).toBe((viewport - 320) / 2);
            expect(pos.top).toBe(224 + 8);
        }
    });

    it('goes above the avatar when there is no room below on a narrow viewport', () => {
        const pos = placePanel({ ...trigger, top: 700, bottom: 724 }, 320, 200, 375, 800);
        expect(pos.top).toBe(700 - 200 - 8);
    });
});
