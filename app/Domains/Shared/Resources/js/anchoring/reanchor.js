const CONTEXT_WORDS = 5;

/**
 * Re-locate a stored anchor in the current canonical text.
 *
 * Two-step algorithm:
 *   1. Full triple match: search for highlighted; among hits, keep those whose
 *      surrounding N-word context matches stored prefix and suffix.
 *      Exactly one hit → ok.
 *   2. Prefix+suffix fallback: find the region between the stored prefix and
 *      suffix substrings. Exactly one such region → moved.
 *   Otherwise → missing.
 *
 * @param {{ text: string, nodeMap: any[] } | string} canonicalText
 * @param {{ prefix: string, highlighted: string, suffix: string }} anchor
 * @returns {{ status: 'ok'|'moved'|'missing', start: number, end: number }}
 */
export function findAnchor(canonicalText, { prefix, highlighted, suffix }) {
    const text = typeof canonicalText === 'string' ? canonicalText : canonicalText.text;

    if (!highlighted) {
        return { status: 'missing', start: 0, end: 0 };
    }

    // Step 1: Full triple match
    const positions = findAllOccurrences(text, highlighted);
    const tripleMatches = positions.filter(pos =>
        contextMatches(text, pos, highlighted.length, prefix, suffix)
    );

    if (tripleMatches.length === 1) {
        const pos = tripleMatches[0];
        return { status: 'ok', start: pos, end: pos + highlighted.length };
    }

    // Step 2: Prefix + suffix context fallback
    const contextResults = findBySurroundingContext(text, prefix, suffix);

    if (contextResults.length === 1) {
        return { status: 'moved', start: contextResults[0].start, end: contextResults[0].end };
    }

    return { status: 'missing', start: 0, end: 0 };
}

function findAllOccurrences(text, substring) {
    const positions = [];
    let pos = 0;
    while (pos <= text.length - substring.length) {
        const found = text.indexOf(substring, pos);
        if (found === -1) break;
        positions.push(found);
        pos = found + 1;
    }
    return positions;
}

function contextMatches(text, pos, length, prefix, suffix) {
    if (prefix) {
        const prefixWords = normalizeWords(prefix).split(' ').length;
        const before = text.slice(0, pos);
        if (normalizeWords(lastNWords(before, prefixWords)) !== normalizeWords(prefix)) return false;
    }
    if (suffix) {
        const suffixWords = normalizeWords(suffix).split(' ').length;
        const after = text.slice(pos + length);
        if (normalizeWords(firstNWords(after, suffixWords)) !== normalizeWords(suffix)) return false;
    }
    return true;
}

function findBySurroundingContext(text, prefix, suffix) {
    if (!prefix && !suffix) return [];

    const matches = [];

    if (!prefix) {
        for (const pos of findAllOccurrences(text, suffix)) {
            matches.push({ start: 0, end: pos });
        }
        return matches;
    }

    for (const prefixPos of findAllOccurrences(text, prefix)) {
        let contentStart = prefixPos + prefix.length;

        if (!suffix) {
            while (contentStart < text.length && text[contentStart] === ' ') contentStart++;
            matches.push({ start: contentStart, end: text.length });
        } else {
            const suffixPos = text.indexOf(suffix, contentStart);
            if (suffixPos !== -1 && suffixPos > contentStart) {
                let start = contentStart;
                let end = suffixPos;
                while (start < end && text[start] === ' ') start++;
                while (end > start && text[end - 1] === ' ') end--;
                matches.push({ start, end });
            }
        }
    }

    return matches;
}

function lastNWords(str, n) {
    const words = str.trim().split(/\s+/).filter(Boolean);
    return words.slice(-n).join(' ');
}

function firstNWords(str, n) {
    const words = str.trim().split(/\s+/).filter(Boolean);
    return words.slice(0, n).join(' ');
}

function normalizeWords(str) {
    return str.trim().split(/\s+/).filter(Boolean).join(' ');
}
