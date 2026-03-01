<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * For each network, strip the known base URL prefix (and any trailing slash)
     * so only the handle/path remainder is stored.
     *
     * Rows whose value does not start with a known prefix are left untouched
     * (they are already handles or empty).
     */
    public function up(): void
    {
        $networks = [
            'facebook_handle' => ['https://www.facebook.com/', 'https://facebook.com/', 'http://www.facebook.com/', 'http://facebook.com/', 'https://fb.com/', 'http://fb.com/'],
            'x_handle'        => ['https://www.x.com/', 'https://x.com/', 'http://www.x.com/', 'http://x.com/', 'https://www.twitter.com/', 'https://twitter.com/', 'http://www.twitter.com/', 'http://twitter.com/'],
            'instagram_handle'=> ['https://www.instagram.com/', 'https://instagram.com/', 'http://www.instagram.com/', 'http://instagram.com/'],
            'youtube_handle'  => ['https://www.youtube.com/', 'https://youtube.com/', 'http://www.youtube.com/', 'http://youtube.com/', 'https://youtu.be/', 'http://youtu.be/'],
        ];

        foreach ($networks as $column => $prefixes) {
            $rows = DB::table('profile_profiles')
                ->whereNotNull($column)
                ->where($column, '!=', '')
                ->get(['user_id', $column]);

            foreach ($rows as $row) {
                $value = $row->{$column};
                $stripped = $this->stripPrefix($value, $prefixes);
                if ($stripped !== $value) {
                    DB::table('profile_profiles')
                        ->where('user_id', $row->user_id)
                        ->update([$column => $stripped === '' ? null : $stripped]);
                }
            }
        }
    }

    public function down(): void
    {
        // Not reversible: we cannot know which handles came from full URLs
    }

    private function stripPrefix(string $value, array $prefixes): string
    {
        foreach ($prefixes as $prefix) {
            if (str_starts_with(strtolower($value), strtolower($prefix))) {
                return substr($value, strlen($prefix));
            }
        }
        return $value;
    }
};
