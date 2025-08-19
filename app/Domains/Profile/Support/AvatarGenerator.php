<?php

namespace App\Domains\Profile\Support;

class AvatarGenerator
{
    /**
     * Generate a data URI SVG avatar for a user.
     */
    public static function forUser(string $username, int $size = 200, ?int $seed = null): string
    {
        $name = trim($username ?? 'User');
        $initials = self::initialsFromName($name);
        $seed = $seed ?? 0;
        return self::fromInitials($initials, $size, $seed);
    }

    /**
     * Generate a data URI SVG avatar from initials.
     */
    public static function fromInitials(string $initials, int $size = 200, int $seed = 0): string
    {
        [$bg, $fg] = self::pickColors($seed);
        $svg = self::buildSvg($initials, $size, $bg, $fg);
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    /**
     * Compute initials from a name string.
     */
    public static function initialsFromName(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return 'UU';
        }
        $words = preg_split('/\s+/', $name);
        if (!$words || count($words) === 0) {
            return strtoupper(mb_substr($name, 0, 2));
        }
        if (count($words) >= 2) {
            $a = mb_substr($words[0], 0, 1);
            $b = mb_substr($words[1], 0, 1);
            return mb_strtoupper($a . $b);
        }
        return mb_strtoupper(mb_substr($name, 0, 2));
    }

    /**
     * Pick deterministic colors from a pleasant palette using a seed.
     *
     * @return array{string,string} [$backgroundHex, $foregroundHex]
     */
    private static function pickColors(int $seed): array
    {
        $palette = [
            ['EBF4FF', '1E3A8A'], // light blue bg, dark blue text
            ['FFE4E6', '9F1239'], // rose
            ['ECFDF5', '065F46'], // emerald
            ['FEF3C7', '92400E'], // amber
            ['F3E8FF', '6B21A8'], // violet
            ['E0F2FE', '075985'], // sky
            ['FEE2E2', '991B1B'], // red
            ['EDE9FE', '4C1D95'], // indigo
        ];
        $index = ($seed >= 0 ? $seed : -$seed) % count($palette);
        [$bg, $fg] = $palette[$index];
        return ["#{$bg}", "#{$fg}"];
    }

    /**
     * Build the centered initials SVG.
     */
    private static function buildSvg(string $initials, int $size, string $bg, string $fg): string
    {
        $fontSize = (int) round($size * 0.42);
        $fontFamily = 'Inter, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif';
        $escapedInitials = htmlspecialchars($initials, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $radius = (int) round($size / 2);

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%1$d" height="%1$d" viewBox="0 0 %1$d %1$d" role="img" aria-label="%2$s">'
            . '<rect width="100%%" height="100%%" rx="%3$d" ry="%3$d" fill="%4$s" />'
            . '<text x="50%%" y="50%%" text-anchor="middle" dominant-baseline="middle" fill="%5$s" font-family="%6$s" font-size="%7$d" font-weight="700">%2$s</text>'
            . '</svg>',
            $size,
            $escapedInitials,
            $radius,
            $bg,
            $fg,
            $fontFamily,
            $fontSize
        );
    }
}
