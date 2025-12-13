<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\SecretGift\Console;

use App\Domains\Calendar\Private\Activities\SecretGift\SecretGiftRegistration;
use App\Domains\Calendar\Private\Activities\SecretGift\Services\ShuffleService;
use App\Domains\Calendar\Private\Models\Activity;
use Illuminate\Console\Command;

class ShuffleSecretGiftCommand extends Command
{
    protected $signature = 'secret-gift:shuffle {activity_id : The ID of the Secret Gift activity}';

    protected $description = 'Perform the shuffle for a Secret Gift activity, assigning each participant a recipient';

    public function __construct(private ShuffleService $shuffleService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $activityId = (int) $this->argument('activity_id');

        $activity = Activity::find($activityId);

        if (!$activity) {
            $this->error("Activity with ID {$activityId} not found.");
            return self::FAILURE;
        }

        if ($activity->activity_type !== SecretGiftRegistration::ACTIVITY_TYPE) {
            $this->error("Activity {$activityId} is not a Secret Gift activity (type: {$activity->activity_type}).");
            return self::FAILURE;
        }

        if ($this->shuffleService->hasBeenShuffled($activity)) {
            if (!$this->confirm('This activity has already been shuffled. Do you want to re-shuffle? This will delete all existing assignments and gifts!')) {
                $this->info('Shuffle cancelled.');
                return self::SUCCESS;
            }
        }

        try {
            $count = $this->shuffleService->performShuffle($activity);
            $this->info("Successfully shuffled {$count} participants for activity '{$activity->name}'.");
            return self::SUCCESS;
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }
}
