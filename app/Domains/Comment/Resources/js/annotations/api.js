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

/** GET the annotations under a root comment, as the server lets this viewer see them. */
export async function fetchAnnotations(commentId) {
    const res = await fetch(`/comments/${commentId}/annotations`, {
        headers: { 'Accept': 'application/json' },
    });
    if (!res.ok) throw new Error(await res.text());
    return res.json();
}

/** Chapter authors only: mark a root annotation as processed (or not). */
export async function setProcessed(annotationId, value) {
    const res = await fetch(`/comments/annotations/${annotationId}/processed`, {
        method: 'PUT',
        headers: headers(),
        body: JSON.stringify({ value }),
    });
    if (!res.ok) throw new Error(await res.text());
    return res.json();
}

/**
 * Root comment's writer: save the pending `annotationChanges` slot in one PUT.
 * Resolves `{ ok: true, list }` on 200, `{ ok: false, status, errors }` on 422
 * (errors keyed `adds.<tempId>`, `edits.<id>`, `deletes.<id>`) or 404 (root gone);
 * throws on any other failure.
 */
export async function saveAnnotationChanges(commentId, { adds = [], edits = {}, deletes = [] }) {
    const res = await fetch(`/comments/${commentId}/annotations`, {
        method: 'PUT',
        headers: headers(),
        body: JSON.stringify({
            adds: adds.map((a) => ({
                key: a.tempId,
                body: a.body,
                highlighted_text: a.highlighted,
                prefix: a.prefix,
                suffix: a.suffix,
            })),
            edits: Object.entries(edits).map(([id, body]) => ({ id: Number(id), body })),
            deletes,
        }),
    });
    if (res.ok) return { ok: true, list: await res.json() };
    if (res.status === 422 || res.status === 404) {
        const payload = await res.json().catch(() => ({}));
        return { ok: false, status: res.status, errors: payload?.errors ?? {} };
    }
    throw new Error(await res.text());
}

/** Reply to a root annotation; returns the created reply. */
export async function postReply(annotationId, body) {
    const res = await fetch(`/comments/annotations/${annotationId}/replies`, {
        method: 'POST',
        headers: headers(),
        body: JSON.stringify({ body }),
    });
    if (!res.ok) throw new Error(await res.text());
    return res.json();
}

/** Writer only: delete one's own reply. */
export async function deleteReply(replyId) {
    const res = await fetch(`/comments/annotations/replies/${replyId}`, {
        method: 'DELETE',
        headers: headers(),
    });
    if (!res.ok) throw new Error(await res.text());
}

/** Moderators only. */
export async function deleteAnnotation(annotationId) {
    const res = await fetch(`/comments/annotations/${annotationId}`, {
        method: 'DELETE',
        headers: headers(),
    });
    if (!res.ok) throw new Error(await res.text());
}
