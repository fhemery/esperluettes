<?php

declare(strict_types=1);

namespace App\Domains\Shared\Views\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class CountdownTimerComponent extends Component
{
    public string $endTime;
    public bool $isFinished;
    public string $componentId;
    public string $class;
    public int $updateInterval;
    public bool $showSeconds;

    public function __construct(string $endTime, string $class = '')
    {
        $this->endTime = $endTime;
        $endTimeCarbon = \Carbon\Carbon::parse($endTime);
        $this->isFinished = $endTimeCarbon->isPast();
        $this->componentId = 'countdown-' . md5($endTime);
        $this->class = $class;
        
        $secondsUntilEnd = $this->isFinished ? 0 : now()->diffInSeconds($endTimeCarbon);
        $this->showSeconds = $secondsUntilEnd <= 3600;
        $this->updateInterval = $this->showSeconds ? 1000 : 60000;
    }

    public function render(): View
    {
        return view('shared::components.countdown', [
            'endTime' => $this->endTime,
            'isFinished' => $this->isFinished,
            'componentId' => $this->componentId,
            'class' => $this->class,
            'updateInterval' => $this->updateInterval,
            'showSeconds' => $this->showSeconds,
        ]);
    }
}
