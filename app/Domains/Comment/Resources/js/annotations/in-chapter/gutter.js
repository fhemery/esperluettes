/**
 * Gutter avatars: one button per band of passages starting within `minGap`
 * pixels of each other (Quote's markers use the same 24px column).
 */

/**
 * Group gutter items by vertical position. Pure.
 *
 * Items are sorted by `top`, then canonical `start` (stable); a band runs while
 * consecutive tops differ by less than `minGap`. The lead is the band's first
 * visible item in reading order, `extra` its other visible roots (the « +N »,
 * A2). A band with no visible item yields no group.
 *
 * @param {Array<{ key: string, top: number, start: number, visible: boolean, isRoot: boolean }>} items
 * @returns {Array<{ key: string, top: number, lead: object, extra: object[] }>}
 */
export function groupByPosition(items, minGap = 20) {
    const sorted = items.slice().sort((a, b) => (a.top - b.top) || (a.start - b.start));

    const bands = [];
    let previousTop = null;
    for (const item of sorted) {
        if (previousTop === null || item.top - previousTop >= minGap) bands.push([]);
        bands[bands.length - 1].push(item);
        previousTop = item.top;
    }

    const groups = [];
    for (const band of bands) {
        const visible = band.filter((item) => item.visible);
        const lead = visible[0];
        if (!lead) continue;
        groups.push({
            key: lead.key,
            top: lead.top,
            lead,
            extra: visible.slice(1).filter((item) => item.isRoot),
        });
    }
    return groups;
}

/** Stable key of an overlay row: server id, or the local draft's tempId. */
export function rowKey(row) {
    return row.id != null ? `id:${row.id}` : `temp:${row.tempId}`;
}

/**
 * Gutter items for the placed ranges of `renderHighlights()`: `top` is the
 * passage's first line, relative to the gutter. A range with no client rect
 * (collapsed, or inside a hidden block) is not visible.
 */
export function gutterItems(placed, gutterTop) {
    return placed.map(({ row, range, start }) => {
        const rect = range.getClientRects()[0];
        return {
            key: rowKey(row),
            top: rect ? rect.top - gutterTop : 0,
            start: start ?? 0,
            visible: Boolean(rect),
            isRoot: row.parent_annotation_id == null,
            row,
        };
    });
}
