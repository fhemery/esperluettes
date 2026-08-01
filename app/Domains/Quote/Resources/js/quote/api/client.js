function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

function headers() {
    return {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken(),
        'Accept': 'application/json',
    };
}

export async function createQuote({ chapterId, storyId, highlightedText, prefix, suffix, note }) {
    const res = await fetch('/quotes', {
        method: 'POST',
        headers: headers(),
        body: JSON.stringify({
            chapter_id: chapterId,
            story_id: storyId,
            highlighted_text: highlightedText,
            prefix: prefix ?? null,
            suffix: suffix ?? null,
            note: note ?? null,
        }),
    });
    if (!res.ok) throw new Error(await res.text());
    return res.json();
}

export async function updateQuoteNote(quoteId, note) {
    const res = await fetch(`/quotes/${quoteId}`, {
        method: 'PUT',
        headers: headers(),
        body: JSON.stringify({ note }),
    });
    if (!res.ok) throw new Error(await res.text());
    return res.json();
}

export async function deleteQuote(quoteId) {
    const res = await fetch(`/quotes/${quoteId}`, {
        method: 'DELETE',
        headers: headers(),
    });
    if (!res.ok) throw new Error(await res.text());
}

/**
 * The author-only aggregate of a chapter: every quote of the chapter, without
 * its reader's note. The chapter id is the only parameter — the story is
 * resolved server-side.
 */
export async function getChapterAggregate(chapterId) {
    const res = await fetch(`/quotes/chapter-aggregate?chapter_id=${chapterId}`, {
        headers: { 'Accept': 'application/json' },
    });
    if (!res.ok) throw new Error(await res.text());
    return res.json();
}

export async function getQuotesForChapter(chapterId, storyId) {
    const res = await fetch(`/quotes?chapter_id=${chapterId}&story_id=${storyId}`, {
        headers: { 'Accept': 'application/json' },
    });
    if (!res.ok) throw new Error(await res.text());
    return res.json();
}
