<?php

namespace App\Domains\Admin\Filament\Resources\Calendar\ActivitiesResource\Pages;

use App\Domains\Admin\Filament\Resources\Calendar\ActivitiesResource;
use App\Domains\Calendar\Private\Models\Activity;
use App\Domains\Calendar\Public\Api\CalendarPublicApi;
use App\Domains\Calendar\Public\Contracts\ActivityToUpdateDto;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Filament\Notifications\Notification;

class EditActivity extends EditRecord
{
    protected static string $resource = ActivitiesResource::class;

    protected function handleRecordUpdate($record, array $data): Activity
    {
        $imagePath = $data['image_path'] ?? $record->image_path;

        // Remove current image if requested
        if (!empty($data['remove_image'])) {
            $imagePath = null;
        }

        // Move uploaded temp image to final directory
        if (!empty($data['image'])) {
            $tmpPath = $data['image'];
            if (is_string($tmpPath) && Storage::disk('public')->exists($tmpPath)) {
                $filename = basename($tmpPath);
                $final = 'activities/' . $filename;
                if (!Storage::disk('public')->exists($final)) {
                    Storage::disk('public')->move($tmpPath, $final);
                } else {
                    $final = 'activities/' . uniqid() . '-' . $filename;
                    Storage::disk('public')->move($tmpPath, $final);
                }
                $imagePath = $final;
            }
        }

        $dto = new ActivityToUpdateDto(
            name: (string) ($data['name'] ?? $record->name),
            activity_type: (string) ($data['activity_type'] ?? $record->activity_type),
            description: $data['description'] ?? $record->description,
            image_path: $imagePath,
            role_restrictions: $data['role_restrictions'] ?? $record->role_restrictions,
            requires_subscription: isset($data['requires_subscription']) ? (bool) $data['requires_subscription'] : (bool) $record->requires_subscription,
            max_participants: isset($data['max_participants']) && $data['max_participants'] !== '' ? (int) $data['max_participants'] : $record->max_participants,
            preview_starts_at: $this->toCarbon($data['preview_starts_at'] ?? $record->preview_starts_at),
            active_starts_at: $this->toCarbon($data['active_starts_at'] ?? $record->active_starts_at),
            active_ends_at: $this->toCarbon($data['active_ends_at'] ?? $record->active_ends_at),
            archived_at: $this->toCarbon($data['archived_at'] ?? $record->archived_at),
        );

        /** @var CalendarPublicApi $api */
        $api = app(CalendarPublicApi::class);
        try {
            $api->update($record->id, $dto, (int) Auth::id());
        } catch (ValidationException $e) {
            $this->setErrorBag($e->validator->getMessageBag());
            $errors = $e->validator->errors()->all();
            $message = implode("\n", $errors);
            Notification::make()
                ->title(__('Validation failed'))
                ->body($message)
                ->danger()
                ->send();
            $this->halt();
        }

        // Reload record
        return Activity::query()->findOrFail($record->id);
    }

    private function toCarbon($value): ?\Carbon\CarbonInterface
    {
        if (!$value) return null;
        if ($value instanceof \Carbon\CarbonInterface) return $value;
        return Carbon::parse((string) $value);
    }
}
