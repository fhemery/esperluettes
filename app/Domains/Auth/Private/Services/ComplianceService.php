<?php

namespace App\Domains\Auth\Private\Services;

use App\Domains\Auth\Private\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ComplianceService
{
    private const AUTHORIZATION_FOLDER = 'parental_authorizations';
    private const DISK = 'private';

    /**
     * Store parental authorization file for a user.
     */
    public function storeParentalAuthorization(int $userId, UploadedFile $file): string
    {
        $fileName = $this->getAuthorizationFileName($userId);
        $file->storeAs(self::AUTHORIZATION_FOLDER, $fileName, self::DISK);

        return $fileName;
    }

    /**
     * Check if parental authorization file exists for a user.
     */
    public function hasParentalAuthorization(int $userId): bool
    {
        return Storage::disk(self::DISK)->exists($this->getAuthorizationFilePath($userId));
    }

    /**
     * Download parental authorization file for a user.
     * Returns null if file doesn't exist.
     */
    public function downloadParentalAuthorization(int $userId): ?StreamedResponse
    {
        $filePath = $this->getAuthorizationFilePath($userId);

        if (!Storage::disk(self::DISK)->exists($filePath)) {
            return null;
        }

        $fileName = $this->getAuthorizationFileName($userId);

        return Storage::disk(self::DISK)->download($filePath, $fileName);
    }

    /**
     * Mark user's parental authorization as verified.
     */
    public function verifyParentalAuthorization(User $user): void
    {
        $user->verifyParentalAuthorization();
    }

    /**
     * Clear parental authorization for a user.
     * Deletes the file and resets the verification timestamp.
     */
    public function clearParentalAuthorization(User $user): bool
    {
        if (!$user->is_under_15) {
            return false;
        }

        $filePath = $this->getAuthorizationFilePath($user->id);

        // Delete file if exists
        if (Storage::disk(self::DISK)->exists($filePath)) {
            Storage::disk(self::DISK)->delete($filePath);
        }

        // Reset verification timestamp
        $user->update(['parental_authorization_verified_at' => null]);

        return true;
    }

    private function getAuthorizationFileName(int $userId): string
    {
        return 'authorization-' . $userId . '.pdf';
    }

    private function getAuthorizationFilePath(int $userId): string
    {
        return self::AUTHORIZATION_FOLDER . '/' . $this->getAuthorizationFileName($userId);
    }
}
