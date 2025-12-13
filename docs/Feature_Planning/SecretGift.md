# Secret Gift Activity Specification

## Overview

The Secret Gift is a Calendar activity implementing a "Secret Santa" style exchange where participants are randomly assigned another participant to create a personalized gift for. Each participant provides preferences (likes/dislikes) during subscription, receives an assignment when the event starts, and creates either a text-based or image-based gift for their assigned recipient.

---

## Functional Specification

### Activity Lifecycle

| Phase | State | User Actions |
|-------|-------|--------------|
| **Subscription** | Preview | Subscribe, fill preferences, unsubscribe |
| **Gift Creation** | Active | View assignment, create gift (text or image), save progress |
| **Reveal** | Ended | View received gift and giver identity |

### Subscription Phase (Preview State)

**Entry conditions:**
- Activity is in `preview` state (`preview_starts_at` <= now < `active_starts_at`)
- User has required role (per `role_restrictions`)

**User flow:**
1. User visits activity page, sees description and "Subscribe" button
2. On subscribe, user is shown a rich text editor pre-filled with a template prompt
3. Template contains sections like: what they like, what they dislike, whether they allow fanart, favorite genres, etc.
4. User can edit this text freely and save
5. User can re-edit preferences as long as activity hasn't started
6. User can unsubscribe (with confirmation) as long as activity hasn't started

**Constraints:**
- No late subscriptions once `active_starts_at` is reached
- If insufficient participants, admin cancels activity manually

### Shuffling (Transition to Active)

**Trigger:** Automatic when `active_starts_at` is reached (or manual admin command for current instance)

**Algorithm:**
1. Fetch all subscribed participants
2. Shuffle the list randomly
3. Assign each participant the **next** participant in the shuffled list (last → first)
4. Persist assignments

**Result:** Each participant has exactly one giver and one recipient (circular chain).

### Gift Creation Phase (Active State)

**Entry conditions:**
- Activity is in `active` state
- User is a subscribed participant with an assignment

**User sees two tabs:**

#### Tab 1: "My Gift to Prepare"
- **Target info:** Display name and avatar of the assigned recipient
- **Preferences:** The rich text preferences the recipient filled during subscription
- **Gift creation area:**
  - Toggle/tabs to switch between "Text" and "Image" modes
  - **Text mode:** Rich text editor (Quill, `strict` purifier profile)
  - **Image mode:** Image upload component (max 5MB, jpg/png only)
  - User can fill **both** (changed from original requirement)
  - Auto-save or explicit save button
  - Gift content is private until event ends

#### Tab 2: "My Received Gift" (placeholder)
- Shows message: "Your gift will be revealed when the event ends"

**Constraints:**
- User can edit their gift as long as activity is active
- If user doesn't submit anything, recipient receives no gift

### Reveal Phase (Ended State)

**Entry conditions:**
- Activity is in `ended` state (`active_ends_at` <= now < `archived_at`)

**User sees two tabs:**

#### Tab 1: "My Gift" (read-only)
- Shows what the user created (if anything)
- Read-only view

#### Tab 2: "Gift I Received"
- Shows the gift (text and/or image) created by their giver
- Shows giver's display name and avatar
- If no gift was submitted: "Unfortunately, no gift was submitted for you"

---

## Technical Specification

### Database Schema

#### Table: `calendar_secret_gift_participants`

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint PK | |
| `activity_id` | bigint FK | References `calendar_activities.id` |
| `user_id` | bigint | User ID (no FK to stay domain-isolated) |
| `preferences` | text | Rich text preferences filled by user |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

**Indexes:** `(activity_id, user_id)` unique

#### Table: `calendar_secret_gift_assignments`

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint PK | |
| `activity_id` | bigint FK | References `calendar_activities.id` |
| `giver_user_id` | bigint | User ID of the gift giver |
| `recipient_user_id` | bigint | User ID of the gift recipient |
| `gift_text` | text nullable | Rich text gift content |
| `gift_image_path` | string nullable | Path to uploaded image |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

**Indexes:** 
- `(activity_id, giver_user_id)` unique
- `(activity_id, recipient_user_id)` unique

### File Structure

```
app/Domains/Calendar/Private/Activities/SecretGift/
├── Database/
│   └── Migrations/
│       ├── 2024_12_13_140000_create_secret_gift_participants_table.php
│       └── 2024_12_13_140001_create_secret_gift_assignments_table.php
├── Http/
│   └── Controllers/
│       └── SecretGiftController.php
├── Models/
│   ├── SecretGiftParticipant.php
│   └── SecretGiftAssignment.php
├── Resources/
│   └── views/
│       ├── components/
│       │   └── secret-gift-component.blade.php
│       └── partials/
│           ├── _subscription-form.blade.php
│           ├── _gift-preparation.blade.php
│           └── _gift-reveal.blade.php
├── Services/
│   ├── SecretGiftService.php
│   └── ShuffleService.php
├── SecretGiftRegistration.php
└── SecretGiftServiceProvider.php
```

### Activity Registration

```php
class SecretGiftRegistration implements ActivityRegistrationInterface
{
    public const ACTIVITY_TYPE = 'secret-gift';

    public function displayComponentKey(): string
    {
        return 'secret-gift::secret-gift-component';
    }

    public function configComponentKey(): ?string
    {
        return null;
    }
}
```

### Key Services

#### ShuffleService
```php
class ShuffleService
{
    public function performShuffle(Activity $activity): void
    {
        $participants = SecretGiftParticipant::where('activity_id', $activity->id)
            ->pluck('user_id')
            ->shuffle()
            ->values();
        
        foreach ($participants as $index => $giverUserId) {
            $recipientUserId = $participants[($index + 1) % $participants->count()];
            
            SecretGiftAssignment::create([
                'activity_id' => $activity->id,
                'giver_user_id' => $giverUserId,
                'recipient_user_id' => $recipientUserId,
            ]);
        }
    }
}
```

### Routes

```php
// Inside SecretGiftServiceProvider or routes file
Route::middleware(['auth', 'verified'])->prefix('calendar/secret-gift/{activity:slug}')->group(function () {
    Route::post('/subscribe', [SecretGiftController::class, 'subscribe'])->name('secret-gift.subscribe');
    Route::post('/unsubscribe', [SecretGiftController::class, 'unsubscribe'])->name('secret-gift.unsubscribe');
    Route::post('/preferences', [SecretGiftController::class, 'savePreferences'])->name('secret-gift.preferences');
    Route::post('/gift', [SecretGiftController::class, 'saveGift'])->name('secret-gift.save-gift');
});
```

### Image Storage

Images must be stored privately to prevent theft/early access.

- **Disk:** `local` (private, not web-accessible)
- **Path:** `calendar/secret-gift/{activity_id}/{giver_user_id}.{ext}`
- **Validation:** `mimes:jpg,jpeg,png|max:5120` (5MB)
- **Access:** Via authenticated route that checks:
  - User is the giver (can see their own upload)
  - OR activity has ended AND user is the recipient

**Route for serving images:**
```php
Route::get('/calendar/secret-gift/{activity}/image/{assignment}', [SecretGiftController::class, 'serveImage'])
    ->name('secret-gift.image');
```

### Translations

Location: `app/Domains/Calendar/Private/Activities/SecretGift/Resources/lang/fr/secret-gift.php`

Key translations needed:
- `preferences_template` - The default template text
- `tab_my_gift` - "Mon cadeau à préparer"
- `tab_received_gift` - "Mon cadeau reçu"
- `no_gift_received` - Message when no gift submitted
- `gift_will_be_revealed` - Placeholder during active phase
- Various form labels and buttons

### Purifier Profile

Use existing `strict` profile from `@/home/fred/ws/esperluettes/config/purifier.php:35-45` for both:
- Participant preferences
- Gift text content

---

## Implementation Priority for Current Instance

Since the "Secret Santa" instance is already mid-event with participants from Google Form:

### Phase 1: Immediate (Must Have)
1. ✅ Database migrations
2. ✅ Models (SecretGiftParticipant, SecretGiftAssignment)
3. ✅ Artisan command for manual shuffle
4. ✅ Gift preparation UI (Tab 1 during Active state)
5. ✅ Gift save endpoint

### Phase 2: After Event Ends
6. Gift reveal UI (Tab 2 during Ended state)

### Phase 3: For Future Instances (Theoretical)
7. Subscription UI
8. Preferences editing UI
9. Unsubscribe functionality
10. Auto-shuffle on state transition

---

## Open Questions (Resolved)

| Question | Decision |
|----------|----------|
| Minimum participants? | Admin decides, cancels if too few |
| Text OR image exclusive? | Both allowed |
| Giver identity revealed? | Yes |
| Admin moderation? | No |
| Template location? | Global translations |
| Rich text or plain? | Rich text (Quill, strict profile) |

---

**Document Status:** Draft  
**Last Updated:** 2024-12-13  
**Current Instance:** Secret Santa (participants to be injected manually)
