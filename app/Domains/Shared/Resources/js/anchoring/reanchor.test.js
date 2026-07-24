import { describe, it, expect } from 'vitest';
import { findAnchor } from './reanchor.js';

// "The quick brown fox jumps over the lazy dog"
//   0         1         2         3         4
//   0123456789012345678901234567890123456789012
// "The quick" = 9 chars (0-8), space at 9
// "brown fox" starts at 10, ends at 19
// " jumps over the lazy dog" starts at 19
const SENTENCE = 'The quick brown fox jumps over the lazy dog';

describe('findAnchor — ok (exact triple match)', () => {
    it('locates highlighted text with matching context', () => {
        const canonical = { text: SENTENCE, nodeMap: [] };
        const result = findAnchor(canonical, {
            prefix: 'The quick',
            highlighted: 'brown fox',
            suffix: 'jumps over the lazy dog',
        });
        expect(result.status).toBe('ok');
        expect(result.start).toBe(10);
        expect(result.end).toBe(19);
    });

    it('returns ok for unique highlighted text with empty prefix and suffix', () => {
        const result = findAnchor({ text: SENTENCE, nodeMap: [] }, {
            prefix: '',
            highlighted: 'brown fox',
            suffix: '',
        });
        expect(result.status).toBe('ok');
        expect(result.start).toBe(10);
        expect(result.end).toBe(19);
    });

    it('disambiguates when context narrows multiple raw occurrences to one', () => {
        // "big dog" appears twice; only the first has suffix "bites"
        const text = 'dog is big the big dog bites the big dog runs';
        // positions of "big dog": 14 and 33
        // "big dog" at 14: before="dog is big the " → last 5 words="dog is big the", suffix starts with "bites"
        // "big dog" at 33: before="dog is big the big dog bites the " → suffix starts with "runs"
        const result = findAnchor({ text, nodeMap: [] }, {
            prefix: 'the',
            highlighted: 'big dog',
            suffix: 'bites',
        });
        expect(result.status).toBe('ok');
        expect(text.slice(result.start, result.end)).toBe('big dog');
    });
});

describe('findAnchor — moved (prefix+suffix fallback)', () => {
    it('returns moved when highlighted text changed but context is intact', () => {
        // Original: "The quick brown fox jumps over the lazy dog"
        // Modified: "The quick red cat jumps over the lazy dog"
        const modified = 'The quick red cat jumps over the lazy dog';
        const result = findAnchor({ text: modified, nodeMap: [] }, {
            prefix: 'The quick',
            highlighted: 'brown fox',
            suffix: 'jumps over the lazy dog',
        });
        expect(result.status).toBe('moved');
        // Content between "The quick" and "jumps over the lazy dog" is "red cat"
        expect(modified.slice(result.start, result.end)).toBe('red cat');
    });
});

describe('findAnchor — missing', () => {
    it('returns missing when nothing matches at all', () => {
        const result = findAnchor({ text: SENTENCE, nodeMap: [] }, {
            prefix: 'totally',
            highlighted: 'nonexistent text',
            suffix: 'nowhere',
        });
        expect(result.status).toBe('missing');
    });

    it('returns missing for empty highlighted text', () => {
        const result = findAnchor({ text: SENTENCE, nodeMap: [] }, {
            prefix: 'The',
            highlighted: '',
            suffix: 'quick',
        });
        expect(result.status).toBe('missing');
    });

    it('returns missing when step 1 is ambiguous and step 2 context is also ambiguous', () => {
        // "cat" appears 3 times with empty context → step 1 ambiguous
        // step 2 has no prefix/suffix → missing
        const text = 'cat and cat and cat';
        const result = findAnchor({ text, nodeMap: [] }, {
            prefix: '',
            highlighted: 'cat',
            suffix: '',
        });
        expect(result.status).toBe('missing');
    });

    it('returns missing when step 2 finds multiple prefix+suffix pairs', () => {
        // "START" appears twice, each followed by "END"
        const text = 'START middle END and START other END';
        const result = findAnchor({ text, nodeMap: [] }, {
            prefix: 'START',
            highlighted: 'XYZ',
            suffix: 'END',
        });
        expect(result.status).toBe('missing');
    });

    it('returns missing when step 1 has multiple triple matches', () => {
        // "the" appears twice with identical surrounding context (empty prefix/suffix)
        const text = 'the end the end';
        const result = findAnchor({ text, nodeMap: [] }, {
            prefix: '',
            highlighted: 'the',
            suffix: '',
        });
        expect(result.status).toBe('missing');
    });
});

describe('findAnchor — accepts raw string as canonicalText', () => {
    it('works when passed a plain string instead of an object', () => {
        const result = findAnchor(SENTENCE, {
            prefix: 'The quick',
            highlighted: 'brown fox',
            suffix: 'jumps over the lazy dog',
        });
        expect(result.status).toBe('ok');
    });
});
