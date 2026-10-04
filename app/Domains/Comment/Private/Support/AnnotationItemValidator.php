<?php

namespace App\Domains\Comment\Private\Support;

use App\Domains\Comment\Public\Api\CommentPolicyRegistry;

/**
 * Per-item annotation rules: plain body length within the policy's caps, highlight
 * within the policy's cap, prefix/suffix within column size.
 */
class AnnotationItemValidator
{
    private const CONTEXT_MAX_LENGTH = 255;

    private const INVALID = 'comment::annotations.errors.invalid';

    public function __construct(
        private readonly CommentPolicyRegistry $policies,
        private readonly CommentBodySanitizer $sanitizer,
    ) {}

    /**
     * A null $highlightedText means "body only" (edits, replies): the highlight rule is skipped.
     *
     * @return string|null null when valid, else the translation key of the first failure
     */
    public function firstError(string $entityType, string $body, ?string $highlightedText, ?string $prefix, ?string $suffix): ?string
    {
        $bodyLength = $this->sanitizer->plainTextLength($body, CommentBodySanitizer::ANNOTATION);
        $bodyMax = $this->policies->getAnnotationBodyMaxLength($entityType);
        if ($bodyLength < 1 || ($bodyMax !== null && $bodyLength > $bodyMax)) {
            return self::INVALID;
        }

        if ($highlightedText !== null) {
            $highlightMax = $this->policies->getAnnotationHighlightMaxLength($entityType);
            $highlightLength = mb_strlen($highlightedText);
            if ($highlightLength < 1 || ($highlightMax !== null && $highlightLength > $highlightMax)) {
                return self::INVALID;
            }
        }

        foreach ([$prefix, $suffix] as $context) {
            if ($context !== null && mb_strlen($context) > self::CONTEXT_MAX_LENGTH) {
                return self::INVALID;
            }
        }

        return null;
    }
}
