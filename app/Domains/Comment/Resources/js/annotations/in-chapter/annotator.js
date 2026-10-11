/**
 * Display name of an annotation or reply author. An anonymised (deleted)
 * commenter has no profile, or no name: they read as `unknownName`
 * (`comment::comments.unknown_user`), as in the rest of the comments.
 */
export function annotatorName(profile, unknownName) {
    return profile?.display_name || unknownName;
}
