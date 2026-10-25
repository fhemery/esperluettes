import { describe, it, expect, beforeEach, afterEach } from 'vitest';
import { utcToLocalInput, localToUtcInput, splitLocalInput, joinLocalInput } from './date-utils.js';

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

describe('split / join of a local datetime value', () => {
    it('splits a combined local value into date and time', () => {
        expect(splitLocalInput('2026-06-21T15:07')).toEqual({ date: '2026-06-21', time: '15:07' });
    });

    it('splits a value with a space separator and seconds', () => {
        expect(splitLocalInput('2026-06-21 15:07:30')).toEqual({ date: '2026-06-21', time: '15:07' });
    });

    it('splits an empty or unparseable value into two empty strings', () => {
        const empty = { date: '', time: '' };
        expect(splitLocalInput('')).toEqual(empty);
        expect(splitLocalInput(null)).toEqual(empty);
        expect(splitLocalInput(undefined)).toEqual(empty);
        expect(splitLocalInput('not-a-date')).toEqual(empty);
    });

    it('joins a date and a time into the combined shape', () => {
        expect(joinLocalInput('2026-06-21', '15:07')).toBe('2026-06-21T15:07');
    });

    it('defaults the time to 00:00 when only the date is set', () => {
        expect(joinLocalInput('2026-06-21', '')).toBe('2026-06-21T00:00');
    });

    it('returns an empty string when the date is empty, even with a time', () => {
        expect(joinLocalInput('', '15:07')).toBe('');
    });

    it('truncates a time carrying seconds', () => {
        expect(joinLocalInput('2026-06-21', '15:07:30')).toBe('2026-06-21T15:07');
    });

    it('round-trips split -> join', () => {
        for (const v of ['2026-06-21T15:07', '2026-03-29T02:30']) {
            const { date, time } = splitLocalInput(v);
            expect(joinLocalInput(date, time)).toBe(v);
        }
    });
});
