<?php

namespace App\Domains\Auth\Services;

use App\Domains\Auth\Models\ActivationCode;
use App\Domains\Auth\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ActivationCodeService
{
    /**
     * Generate a new activation code
     */
    public function generateCode(
        ?User $sponsorUser = null,
        ?string $comment = null,
        ?Carbon $expiresAt = null
    ): ActivationCode {
        $code = $this->createUniqueCode();

        return ActivationCode::create([
            'code' => $code,
            'sponsor_user_id' => $sponsorUser?->id,
            'comment' => $comment,
            'expires_at' => $expiresAt,
        ]);
    }

    /**
     * Validate and use an activation code
     */
    public function validateAndUseCode(string $code, User $user): bool
    {
        $activationCode = ActivationCode::where('code', $code)->first();

        if (!$activationCode || !$activationCode->isValid()) {
            return false;
        }

        $activationCode->markAsUsed($user);
        return true;
    }

    /**
     * Check if an activation code exists and is valid
     */
    public function isCodeValid(string $code): bool
    {
        $activationCode = ActivationCode::where('code', $code)->first();
        return $activationCode && $activationCode->isValid();
    }

    /**
     * Get activation code by code string
     */
    public function findByCode(string $code): ?ActivationCode
    {
        return ActivationCode::where('code', $code)->first();
    }

    /**
     * Delete an activation code
     */
    public function deleteCode(ActivationCode $activationCode): bool
    {
        return $activationCode->delete();
    }

    /**
     * Get all activation codes with relationships
     */
    public function getAllCodes()
    {
        return ActivationCode::with(['sponsorUser', 'usedByUser'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Create a unique activation code in the format XYZT-ABCDEFGH-IJKL
     * Using only uppercase letters and digits
     */
    private function createUniqueCode(): string
    {
        do {
            $code = $this->generateCodeFormat();
        } while (ActivationCode::where('code', $code)->exists());

        return $code;
    }

    /**
     * Generate code in the format XYZT-ABCDEFGH-IJKL
     * 4 chars - 8 chars - 4 chars, using uppercase letters and digits
     */
    private function generateCodeFormat(): string
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        
        $part1 = $this->generateRandomString(4, $characters);
        $part2 = $this->generateRandomString(8, $characters);
        $part3 = $this->generateRandomString(4, $characters);

        return "{$part1}-{$part2}-{$part3}";
    }

    /**
     * Generate a random string of specified length using given characters
     */
    private function generateRandomString(int $length, string $characters): string
    {
        $result = '';
        $charactersLength = strlen($characters);
        
        for ($i = 0; $i < $length; $i++) {
            $result .= $characters[random_int(0, $charactersLength - 1)];
        }
        
        return $result;
    }
}
