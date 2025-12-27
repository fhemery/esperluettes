<?php

declare(strict_types=1);

use App\Domains\Shared\Views\Components\CountdownTimerComponent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Countdown Timer Component', function () {
    it('should render countdown with days, hours, and minutes when more than 1 hour remaining', function () {
        // End time: 7 days, 18 hours, 23 minutes from now
        $endTime = now()->addDays(7)->addHours(18)->addMinutes(23);
        
        $component = new CountdownTimerComponent($endTime->toIso8601String(), 'test-class');
        $html = $component->render()->with('data', $component->data())->render();

        expect($html)->toContain('x-data="countdownTimer"')
            ->and($html)->toContain('data-end-time="' . $endTime->toIso8601String() . '"')
            ->and($html)->toContain('data-update-interval="60000"') // 1 minute interval
            ->and($html)->toContain('data-show-seconds="false"')
            ->and($html)->toContain('test-class');
    });

    it('should render countdown with minutes and seconds when less than 1 hour remaining', function () {
        // End time: 45 minutes and 30 seconds from now
        $endTime = now()->addMinutes(45)->addSeconds(30);
        
        $component = new CountdownTimerComponent($endTime->toIso8601String());
        $html = $component->render()->with('data', $component->data())->render();

        expect($html)->toContain('data-update-interval="1000"') // 1 second interval
            ->and($html)->toContain('data-show-seconds="true"');
    });

    it('should render countdown with exactly 1 hour remaining', function () {
        // End time: 1 hour + 1 second from now (to ensure it's over 3600 seconds)
        $endTime = now()->addHour()->addSecond();
        
        $component = new CountdownTimerComponent($endTime->toIso8601String());
        $html = $component->render()->with('data', $component->data())->render();

        expect($html)->toContain('data-update-interval="60000"') // 1 minute interval
            ->and($html)->toContain('data-show-seconds="false"');
    });

    it('should show finished message when end time is in the past', function () {
        // End time: 1 hour ago
        $endTime = now()->subHour();
        
        $component = new CountdownTimerComponent($endTime->toIso8601String());
        $html = $component->render()->with('data', $component->data())->render();

        expect($html)->toContain('shared::countdown.finished')
            ->and($html)->not->toContain('x-data="countdownTimer"');
    });

    it('should include all required translation keys in data attributes', function () {
        $endTime = now()->addDays(2)->addHours(5)->addMinutes(30);
        
        $component = new CountdownTimerComponent($endTime->toIso8601String());
        $html = $component->render()->with('data', $component->data())->render();

        // Check for translation data attributes
        expect($html)->toContain('data-trans-day="shared::countdown.day"')
            ->and($html)->toContain('data-trans-days="shared::countdown.days"')
            ->and($html)->toContain('data-trans-hour="shared::countdown.hour"')
            ->and($html)->toContain('data-trans-hours="shared::countdown.hours"')
            ->and($html)->toContain('data-trans-minute="shared::countdown.minute"')
            ->and($html)->toContain('data-trans-minutes="shared::countdown.minutes"')
            ->and($html)->toContain('data-trans-second="shared::countdown.second"')
            ->and($html)->toContain('data-trans-seconds="shared::countdown.seconds"')
            ->and($html)->toContain('data-trans-separator="shared::countdown.separator"')
            ->and($html)->toContain('data-trans-finished="shared::countdown.finished"');
    });

    it('should include unique ID based on end time', function () {
        $endTime = now()->addHour();
        $expectedId = 'countdown-' . md5($endTime->toIso8601String());
        
        $component = new CountdownTimerComponent($endTime->toIso8601String());
        $html = $component->render()->with('data', $component->data())->render();

        expect($html)->toContain('id="' . $expectedId . '"');
    });
});

/**
 * Helper function to render the countdown timer component.
 */
function renderCountdownTimer(string $endTime): string
{
    $component = new \App\Domains\Shared\Views\Components\CountdownTimerComponent($endTime);
    return $component->render()->with('data', $component->data())->render();
}
