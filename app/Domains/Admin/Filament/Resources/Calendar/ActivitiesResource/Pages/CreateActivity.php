<?php

namespace App\Domains\Admin\Filament\Resources\Calendar\ActivitiesResource\Pages;

use App\Domains\Admin\Filament\Resources\Calendar\ActivitiesResource;
use App\Domains\Calendar\Private\Models\Activity;
use App\Domains\Calendar\Public\Api\CalendarPublicApi;
use App\Domains\Calendar\Public\Contracts\ActivityToCreateDto;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Filament\Notifications\Notification;

class CreateActivity extends CreateRecord
{
    protected static string $resource = ActivitiesResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        // Handle image move from tmp to final directory if provided
        $imagePath = $data['image_path'] ?? null;
        if (!empty($data['image'])) {
            // $data['image'] is a TemporaryUploadedFile managed by Filament -> already stored at disk('public') under tmp/activities
            $tmpPath = $data['image'];
            if (is_string($tmpPath) && Storage::disk('public')->exists($tmpPath)) {
                $filename = basename($tmpPath);
                $final = 'activities/' . $filename;
                if (!Storage::disk('public')->exists($final)) {
                    Storage::disk('public')->move($tmpPath, $final);
                } else {
                    // Ensure unique
                    $final = 'activities/' . uniqid() . '-' . $filename;
                    Storage::disk('public')->move($tmpPath, $final);
                }
                $imagePath = $final;
            }
        }

        $dto = new ActivityToCreateDto(
            name: (string) ($data['name'] ?? ''),
            activity_type: (string) ($data['activity_type'] ?? ''),
            description: $data['description'] ?? null,
            image_path: $imagePath,
            role_restrictions: $data['role_restrictions'] ?? null,
            requires_subscription: (bool) ($data['requires_subscription'] ?? false),
            max_participants: isset($data['max_participants']) && $data['max_participants'] !== '' ? (int) $data['max_participants'] : null,
            preview_starts_at: $this->toCarbon($data['preview_starts_at'] ?? null),
            active_starts_at: $this->toCarbon($data['active_starts_at'] ?? null),
            active_ends_at: $this->toCarbon($data['active_ends_at'] ?? null),
            archived_at: $this->toCarbon($data['archived_at'] ?? null),
        );

        /** @var CalendarPublicApi $api */
        $api = app(CalendarPublicApi::class);
        try {
            $id = $api->create($dto, (int) Auth::id());
        } catch (ValidationException $e) {
            // Surface backend validation errors onto the form
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

        return Activity::query()->findOrFail($id);
    }

    private function toCarbon($value): ?\Carbon\CarbonInterface
    {
        if (!$value) return null;
        if ($value instanceof \Carbon\CarbonInterface) return $value;
        return Carbon::parse((string) $value);
    }
}
