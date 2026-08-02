/**
 * Pure aggregation helpers for the author view of a chapter's quotes.
 *
 * No DOM, no Alpine, no store: both functions take plain data and return plain
 * data, so they can be unit-tested in isolation.
 */

/**
 * Normalise a passage for grouping: trimmed, inner whitespace collapsed.
 * Case is significant — two passages differing by case are different passages.
 *
 * @param {string} text
 * @returns {string}
 */
function normalise(text) {
    return String(text ?? '').replace(/\s+/g, ' ').trim();
}

/**
 * Group aggregate rows by normalised highlighted text.
 *
 * A row is "live" when it carries a resolved `{start, end}` range; a group with
 * no live row at all is stale (the passage no longer exists in the chapter).
 * Groups are ordered live first, then by count descending.
 *
 * @param {Array<Object>} rows aggregate rows, each optionally carrying `range`
 * @returns {Array<{key: string, text: string, count: number, stale: boolean, rows: Array<Object>, readers: Array<Object>}>}
 */
export function groupPassages(rows) {
    const groups = new Map();

    for (const row of rows) {
        const key = normalise(row.highlighted_text);
        let group = groups.get(key);

        if (!group) {
            group = { key, text: key, count: 0, stale: true, rows: [], readers: [] };
            groups.set(key, group);
        }

        group.rows.push(row);
        group.count += 1;

        if (row.range) {
            group.stale = false;
        }

        const quoter = row.quoter;
        if (quoter && !group.readers.some(r => r.user_id === quoter.user_id)) {
            group.readers.push(quoter);
        }
    }

    return Array.from(groups.values()).sort((a, b) => {
        if (a.stale !== b.stale) return a.stale ? 1 : -1;
        return b.count - a.count;
    });
}

/**
 * Split overlapping ranges into non-overlapping segments carrying a depth.
 *
 * Overlapping ranges cannot be expressed as nested elements, so the caller
 * wraps each returned segment exactly once, at its own depth. Empty and
 * inverted ranges are ignored; no zero-width segment is ever returned.
 *
 * @param {Array<{start: number, end: number}>} ranges
 * @returns {Array<{start: number, end: number, depth: number}>}
 */
export function segmentByDepth(ranges) {
    const valid = ranges.filter(r => r.end > r.start);
    if (valid.length === 0) return [];

    const boundaries = Array.from(new Set(valid.flatMap(r => [r.start, r.end]))).sort((a, b) => a - b);
    const segments = [];

    for (let i = 0; i < boundaries.length - 1; i++) {
        const start = boundaries[i];
        const end = boundaries[i + 1];
        const depth = valid.filter(r => r.start <= start && r.end >= end).length;

        if (depth > 0) {
            segments.push({ start, end, depth });
        }
    }

    return segments;
}
