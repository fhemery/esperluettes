<?php

namespace App\Domains\Shared\Support;

class HtmlLinkUtils
{
    /**
     * Add target="_blank" and safe rel attributes to external http(s) links only.
     * Internal links (same host as app.url), relative links and anchors are left unchanged.
     */
    public static function addTargetBlankToExternalLinks(?string $html): ?string
    {
        if ($html === null || $html === '') {
            return $html;
        }

        $appUrl = config('app.url');
        $appHost = $appUrl ? parse_url($appUrl, PHP_URL_HOST) : null;

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $internalErrors = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $links = $dom->getElementsByTagName('a');
        /** @var \DOMElement $a */
        foreach ($links as $a) {
            $href = $a->getAttribute('href');
            if (!$href) {
                continue;
            }

            // Only process absolute http(s) links; skip anchors and relative URLs
            if (!preg_match('/^https?:\/\//i', $href)) {
                continue;
            }

            $host = parse_url($href, PHP_URL_HOST);
            $isExternal = $appHost && $host ? strcasecmp($host, $appHost) !== 0 : true;

            if ($isExternal) {
                $a->setAttribute('target', '_blank');
                $rel = $a->getAttribute('rel');
                $existing = $rel ? preg_split('/\s+/', $rel, -1, PREG_SPLIT_NO_EMPTY) : [];
                $merged = array_unique(array_filter(array_merge($existing, ['noopener', 'noreferrer'])));
                $a->setAttribute('rel', implode(' ', $merged));
            }
        }

        $result = str_replace('<?xml encoding="utf-8" ?>', '', $dom->saveHTML());
        libxml_use_internal_errors($internalErrors);
        return $result;
    }
}
