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

/** Moderators only. */
export async function deleteAnnotation(annotationId) {
    const res = await fetch(`/comments/annotations/${annotationId}`, {
        method: 'DELETE',
        headers: headers(),
    });
    if (!res.ok) throw new Error(await res.text());
}
