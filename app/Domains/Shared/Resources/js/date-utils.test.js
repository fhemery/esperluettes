import { describe, it, expect, beforeEach, afterEach } from 'vitest';
import { utcToLocalInput, localToUtcInput } from './date-utils.js';

describe('datetime-local <-> UTC conversion (browser in Europe/Paris)', () => {
    const originalTz = process.env.TZ;

    beforeEach(() => {
        process.env.TZ = 'Europe/Paris';
    });

    afterEach(() => {
        process.env.TZ = originalTz;
    });

    it('converts a stored UTC value to local time in summer (CEST, UTC+2)', () => {
        expect(utcToLocalInput('2026-06-21T13:07')).toBe('2026-06-21T15:07');
    });

    it('converts a stored UTC value to local time in winter (CET, UTC+1)', () => {
        expect(utcToLocalInput('2026-01-15T13:07')).toBe('2026-01-15T14:07');
    });

    it('converts a local input value back to the UTC value the server expects', () => {
        expect(localToUtcInput('2026-06-21T15:07')).toBe('2026-06-21T13:07');
        expect(localToUtcInput('2026-01-15T14:07')).toBe('2026-01-15T13:07');
    });

    it('round-trips UTC -> local -> UTC across a DST boundary', () => {
        const utc = '2026-06-21T13:07';
        expect(localToUtcInput(utcToLocalInput(utc))).toBe(utc);
    });

    it('returns an empty string for unparseable input', () => {
        expect(localToUtcInput('not-a-date')).toBe('');
    });
});
