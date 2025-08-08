<?php

namespace App\Domains\Admin\Filament\Resources\ActivationCodeResource\Pages;

use App\Domains\Admin\Filament\Resources\ActivationCodeResource;
use App\Domains\Auth\Services\ActivationCodeService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateActivationCode extends CreateRecord
{
    protected static string $resource = ActivationCodeResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $activationCodeService = app(ActivationCodeService::class);
        
        $sponsorUser = $data['sponsor_user_id'] 
            ? \App\Domains\Auth\Models\User::findOrFail($data['sponsor_user_id'])
            : null;
        
        return $activationCodeService->generateCode(
            sponsorUser: $sponsorUser,
            comment: $data['comment'] ?? null,
            expiresAt: $data['expires_at'] ? \Carbon\Carbon::parse($data['expires_at']) : null
        );
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
