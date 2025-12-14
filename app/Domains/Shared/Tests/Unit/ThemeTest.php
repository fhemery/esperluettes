<?php

namespace App\Domains\Shared\Tests\Unit;

use App\Domains\Shared\Contracts\Theme;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class ThemeTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_it_returns_winter_theme_in_december_after_21(): void
    {
        Carbon::setTestNow(Carbon::create(2024, 12, 25));

        $this->assertEquals(Theme::WINTER, Theme::seasonal());
    }

    public function test_it_returns_winter_theme_in_january(): void
    {
        Carbon::setTestNow(Carbon::create(2025, 1, 15));

        $this->assertEquals(Theme::WINTER, Theme::seasonal());
    }

    public function test_it_returns_winter_theme_in_march_before_21(): void
    {
        Carbon::setTestNow(Carbon::create(2025, 3, 20));

        $this->assertEquals(Theme::WINTER, Theme::seasonal());
    }

    public function test_it_returns_spring_theme_in_march_after_20(): void
    {
        Carbon::setTestNow(Carbon::create(2025, 3, 21));

        $this->assertEquals(Theme::SPRING, Theme::seasonal());
    }

    public function test_it_returns_spring_theme_in_may(): void
    {
        Carbon::setTestNow(Carbon::create(2025, 5, 15));

        $this->assertEquals(Theme::SPRING, Theme::seasonal());
    }

    public function test_it_returns_spring_theme_in_june_before_21(): void
    {
        Carbon::setTestNow(Carbon::create(2025, 6, 20));

        $this->assertEquals(Theme::SPRING, Theme::seasonal());
    }

    public function test_it_returns_summer_theme_in_june_after_20(): void
    {
        Carbon::setTestNow(Carbon::create(2025, 6, 21));

        $this->assertEquals(Theme::SUMMER, Theme::seasonal());
    }

    public function test_it_returns_summer_theme_in_august(): void
    {
        Carbon::setTestNow(Carbon::create(2025, 8, 15));

        $this->assertEquals(Theme::SUMMER, Theme::seasonal());
    }

    public function test_it_returns_summer_theme_in_september_before_23(): void
    {
        Carbon::setTestNow(Carbon::create(2025, 9, 22));

        $this->assertEquals(Theme::SUMMER, Theme::seasonal());
    }

    public function test_it_returns_autumn_theme_in_september_after_22(): void
    {
        Carbon::setTestNow(Carbon::create(2025, 9, 23));

        $this->assertEquals(Theme::AUTUMN, Theme::seasonal());
    }

    public function test_it_returns_autumn_theme_in_november(): void
    {
        Carbon::setTestNow(Carbon::create(2025, 11, 15));

        $this->assertEquals(Theme::AUTUMN, Theme::seasonal());
    }

    public function test_it_returns_autumn_theme_in_december_before_21(): void
    {
        Carbon::setTestNow(Carbon::create(2025, 12, 20));

        $this->assertEquals(Theme::AUTUMN, Theme::seasonal());
    }

    public function test_logo_path_returns_correct_path(): void
    {
        $this->assertEquals('/images/themes/winter/logo.png', Theme::WINTER->logo());
        $this->assertEquals('/images/themes/autumn/logo.png', Theme::AUTUMN->logo());
    }

    public function test_logo_full_path_returns_correct_path(): void
    {
        $this->assertEquals('/images/themes/winter/logo-full.png', Theme::WINTER->logoFull());
        $this->assertEquals('/images/themes/autumn/logo-full.png', Theme::AUTUMN->logoFull());
    }

    public function test_ribbon_path_returns_correct_path(): void
    {
        $this->assertEquals('/images/themes/winter/top-ribbon.png', Theme::WINTER->ribbon());
        $this->assertEquals('/images/themes/autumn/top-ribbon.png', Theme::AUTUMN->ribbon());
    }
}
