<?php

namespace App\Domains\Shared\Contracts;

enum Theme: string
{
    case WINTER = 'winter';
    case SPRING = 'spring';
    case SUMMER = 'summer';
    case AUTUMN = 'autumn';

    /**
     * Get the base path for theme assets.
     */
    public function basePath(): string
    {
        return "/images/themes/{$this->value}";
    }

    /**
     * Get the small logo path.
     */
    public function logo(): string
    {
        return $this->basePath().'/logo.png';
    }

    /**
     * Get the full/expanded logo path.
     */
    public function logoFull(): string
    {
        return $this->basePath().'/logo-full.png';
    }

    /**
     * Get the top ribbon path.
     */
    public function ribbon(): string
    {
        return $this->basePath().'/top-ribbon.png';
    }

    /**
     * Get the full asset URL for a themed resource.
     *
     * @param  string  $path  Relative path within the theme folder (e.g., 'favicons/favicon.ico')
     */
    public function asset(string $path): string
    {
        return asset($this->basePath().'/'.$path);
    }

    /**
     * Get the current seasonal theme based on astronomical seasons.
     * Winter: Dec 21 - Mar 20
     * Spring: Mar 21 - Jun 20
     * Summer: Jun 21 - Sep 22
     * Autumn: Sep 23 - Dec 20
     */
    public static function seasonal(): self
    {
        $now = now();
        $month = $now->month;
        $day = $now->day;

        return match (true) {
            self::isWinter($month, $day) => self::WINTER,
            self::isSpring($month, $day) => self::SPRING,
            self::isSummer($month, $day) => self::SUMMER,
            default => self::AUTUMN,
        };
    }

    private static function isWinter(int $month, int $day): bool
    {
        return ($month === 12 && $day >= 21)
            || $month === 1
            || $month === 2
            || ($month === 3 && $day <= 20);
    }

    private static function isSpring(int $month, int $day): bool
    {
        return ($month === 3 && $day >= 21)
            || $month === 4
            || $month === 5
            || ($month === 6 && $day <= 20);
    }

    private static function isSummer(int $month, int $day): bool
    {
        return ($month === 6 && $day >= 21)
            || $month === 7
            || $month === 8
            || ($month === 9 && $day <= 22);
    }
}
