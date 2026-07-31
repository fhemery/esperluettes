<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\SecretGift\Support;

use App\Domains\Calendar\Private\Activities\SecretGift\Models\SecretGiftAssignment;
use Illuminate\Support\Facades\Storage;

/**
 * One-shot data move for gift images written before they lived on Media's
 * private disk: `local:calendar/secret-gift/{activity}/{giver}.ext` becomes
 * `private:secret-gift/{activity}/{giver}.ext`.
 *
 * It copies bytes between disks directly instead of going through
 * `MediaPublicApi` — there is no upload to store, only an existing file to
 * relocate, and the Media path shape is what the destination has to be. This is
 * the only place in SecretGift that touches an image file: normal saves go
 * through `MediaPublicApi::storePrivate`, and nothing ever deletes.
 *
 * Both directions are idempotent, so the migration that drives them can be run
 * and rolled back more than once.
 */
final class LegacyGiftImageMover
{
    private const LEGACY_DISK = 'local';
    private const MEDIA_DISK = 'private';
    private const LEGACY_PREFIX = 'calendar/secret-gift/';
    private const MEDIA_PREFIX = 'secret-gift/';

    /**
     * Move every legacy gift image onto the private disk.
     *
     * @return array{moved:int, already_migrated:int, missing:array<int,string>}
     */
    public function toMedia(): array
    {
        return $this->move(
            self::MEDIA_PREFIX,
            self::LEGACY_DISK,
            self::MEDIA_DISK,
            fn (SecretGiftAssignment $a): string => self::MEDIA_PREFIX . $a->activity_id . '/' . basename((string) $a->gift_image_path),
        );
    }

    /**
     * Move them back to the legacy `local` layout — the migration's down().
     *
     * @return array{moved:int, already_migrated:int, missing:array<int,string>}
     */
    public function toLegacy(): array
    {
        return $this->move(
            self::LEGACY_PREFIX,
            self::MEDIA_DISK,
            self::LEGACY_DISK,
            fn (SecretGiftAssignment $a): string => self::LEGACY_PREFIX . $a->activity_id . '/' . basename((string) $a->gift_image_path),
        );
    }

    /**
     * @param string $donePrefix Rows already on this prefix are counted, not moved.
     * @param callable(SecretGiftAssignment): string $target
     * @return array{moved:int, already_migrated:int, missing:array<int,string>}
     */
    private function move(string $donePrefix, string $from, string $to, callable $target): array
    {
        $moved = 0;
        $alreadyMigrated = 0;
        $missing = [];

        $assignments = SecretGiftAssignment::query()
            ->whereNotNull('gift_image_path')
            ->get();

        foreach ($assignments as $assignment) {
            $source = (string) $assignment->gift_image_path;

            if (str_starts_with($source, $donePrefix)) {
                $alreadyMigrated++;
                continue;
            }

            if (!Storage::disk($from)->exists($source)) {
                $missing[$assignment->id] = $source;
                continue;
            }

            $destination = $target($assignment);
            Storage::disk($to)->put($destination, Storage::disk($from)->get($source));

            $assignment->gift_image_path = $destination;
            $assignment->save();

            Storage::disk($from)->delete($source);
            $moved++;
        }

        return ['moved' => $moved, 'already_migrated' => $alreadyMigrated, 'missing' => $missing];
    }
}
