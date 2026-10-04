/**
 * Pure helpers for the reply threads of the annotations pop-up. Each returns
 * a new list and leaves the given one untouched.
 */

/** Append `reply` (newest, so last) to the thread of root `rootId`. */
export function appendReply(list, rootId, reply) {
    return list.map((root) => (root.id === rootId
        ? { ...root, replies: [...(root.replies ?? []), reply] }
        : root));
}

/** Drop reply `replyId` from whichever thread holds it. */
export function removeReply(list, replyId) {
    return list.map((root) => {
        const replies = root.replies ?? [];
        if (!replies.some((r) => r.id === replyId)) return root;
        return { ...root, replies: replies.filter((r) => r.id !== replyId) };
    });
}
