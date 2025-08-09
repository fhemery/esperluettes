<?php

use App\Domains\Auth\Models\User;
use App\Domains\Profile\Models\Profile;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('profile_profiles', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('user_id');
        });

        // Backfill slugs for existing profiles
        Profile::with('user')->chunkById(100, function ($profiles) {
            foreach ($profiles as $profile) {
                if (!empty($profile->slug)) {
                    continue;
                }
                $base = $profile->user ? Str::slug($profile->user->name) : 'user-' . $profile->user_id;
                if ($base === '') {
                    $base = 'user-' . $profile->user_id;
                }
                $slug = $this->uniqueSlug($base, $profile->user_id);
                $profile->slug = $slug;
                $profile->saveQuietly();
            }
        });

        // Note: keeping slug nullable to avoid requiring doctrine/dbal for column change
    }

    public function down(): void
    {
        Schema::table('profile_profiles', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }

    private function uniqueSlug(string $base, int $userId): string
    {
        $slug = $base;
        $i = 0;
        while (
            Profile::where('slug', $slug)
                ->where('user_id', '!=', $userId)
                ->exists()
        ) {
            $i++;
            $slug = $base . '-' . $i;
        }
        return $slug;
    }
};
