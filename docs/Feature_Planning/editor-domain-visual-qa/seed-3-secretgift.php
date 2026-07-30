<?php

// Rows 7-8: a SecretGift activity in the *gift preparation* phase (active, not
// ended) with fred (user 2) as a participant holding an assignment as giver,
// and a second one in a phase that shows no editor (preview).

use Illuminate\Support\Facades\DB;

$now = now();

$mk = function (string $slug, string $name, array $dates) use ($now) {
    $row = array_merge([
        'name' => $name,
        'slug' => $slug,
        'description' => '<p>Activité de QA pour la vérification visuelle.</p>',
        'activity_type' => 'secret-gift',
        'role_restrictions' => json_encode([]),
        'requires_subscription' => 1,
        'max_participants' => null,
        'archived_at' => null,
        'created_by_user_id' => 2,
        'updated_at' => $now,
    ], $dates);
    $existing = DB::table('calendar_activities')->where('slug', $slug)->first();
    if ($existing) {
        DB::table('calendar_activities')->where('id', $existing->id)->update($row);
        return (int) $existing->id;
    }
    $row['created_at'] = $now;
    return (int) DB::table('calendar_activities')->insertGetId($row);
};

// active now -> gift preparation
$activeId = $mk('qa-secret-gift-active', 'QA Secret Gift (actif)', [
    'preview_starts_at' => $now->copy()->subDays(10),
    'active_starts_at' => $now->copy()->subDays(2),
    'active_ends_at' => $now->copy()->addDays(20),
]);

// preview only -> no editor
$previewId = $mk('qa-secret-gift-preview', 'QA Secret Gift (préversion)', [
    'preview_starts_at' => $now->copy()->subDays(1),
    'active_starts_at' => $now->copy()->addDays(10),
    'active_ends_at' => $now->copy()->addDays(30),
]);

foreach ([$activeId, $previewId] as $aid) {
    foreach ([2, 3] as $uid) {
        if (!DB::table('calendar_secret_gift_participants')->where('activity_id', $aid)->where('user_id', $uid)->exists()) {
            DB::table('calendar_secret_gift_participants')->insert([
                'activity_id' => $aid, 'user_id' => $uid, 'preferences' => 'Aime les histoires courtes.',
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }
}

// fred gives to alice on the active one
if (!DB::table('calendar_secret_gift_assignments')->where('activity_id', $activeId)->where('giver_user_id', 2)->exists()) {
    DB::table('calendar_secret_gift_assignments')->insert([
        'activity_id' => $activeId, 'giver_user_id' => 2, 'recipient_user_id' => 3,
        'created_at' => $now, 'updated_at' => $now,
    ]);
    DB::table('calendar_secret_gift_assignments')->insert([
        'activity_id' => $activeId, 'giver_user_id' => 3, 'recipient_user_id' => 2,
        'created_at' => $now, 'updated_at' => $now,
    ]);
}

echo "active   /activities/qa-secret-gift-active   (id {$activeId})\n";
echo "preview  /activities/qa-secret-gift-preview  (id {$previewId})\n";
