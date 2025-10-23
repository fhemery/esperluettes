# JardiNo Activity - Technical Architecture

## Overview

Technical implementation of JardiNo: a collaborative writing challenge where users earn virtual flowers by reaching word count milestones, then plant them on a shared garden grid.

**Key Principles:**
- Event-driven architecture (Story → JardiNo)
- Formula-based flower calculation (no queue)
- Sparse data structures for performance
- Alpine.js for garden UI

---

## Database Schema

### `calendar_jardino_goals`

```sql
CREATE TABLE calendar_jardino_goals (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    activity_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,  -- NO FK
    target_word_count INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (activity_id) REFERENCES calendar_activities(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_per_activity (activity_id, user_id)
);
```

### `calendar_jardino_story_snapshots`

```sql
CREATE TABLE calendar_jardino_story_snapshots (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    goal_id BIGINT UNSIGNED NOT NULL,
    story_id BIGINT UNSIGNED NOT NULL,  -- NO FK
    story_title VARCHAR(255) NOT NULL,
    initial_word_count INT UNSIGNED NOT NULL,
    current_word_count INT UNSIGNED NOT NULL,
    biggest_word_count INT UNSIGNED NOT NULL,  -- Never decreases
    selected_at TIMESTAMP NOT NULL,
    deselected_at TIMESTAMP NULL,  -- NULL = currently active
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (goal_id) REFERENCES calendar_jardino_goals(id) ON DELETE CASCADE
);
```

**Word Count Logic:**
```
Progress = sum(current - initial) across all snapshots
Flower Eligibility = sum(biggest - initial) across all snapshots
```

### `calendar_jardino_garden_cells`

```sql
CREATE TABLE calendar_jardino_garden_cells (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    activity_id BIGINT UNSIGNED NOT NULL,
    x SMALLINT UNSIGNED NOT NULL,
    y SMALLINT UNSIGNED NOT NULL,
    type ENUM('flower', 'blocked') NOT NULL,
    flower_image VARCHAR(20) NULL,  -- e.g., "12.png"
    user_id BIGINT UNSIGNED NULL,  -- NO FK
    planted_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (activity_id) REFERENCES calendar_activities(id) ON DELETE CASCADE,
    UNIQUE KEY unique_cell_per_activity (activity_id, x, y)
);
```

**Sparse Storage:** Empty cells have no database row (performance optimization).

---

## Directory Structure

```
app/Domains/Calendar/Private/Activities/Jardino/
├── Services/
│   ├── JardinoGoalService.php          # Goal CRUD, story changes
│   ├── JardinoProgressService.php      # Word count tracking
│   ├── JardinoFlowerService.php        # Flower calculation
│   └── JardinoGardenService.php        # Garden operations
├── Listeners/
│   ├── HandleChapterCreated.php
│   ├── HandleChapterUpdated.php
│   └── HandleChapterDeleted.php
├── Models/
│   ├── JardinoGoal.php
│   ├── JardinoStorySnapshot.php
│   └── JardinoGardenCell.php
├── Resources/views/
│   ├── dashboard.blade.php
│   ├── garden.blade.php
│   └── components/
├── Http/Controllers/
│   ├── JardinoDashboardController.php
│   └── JardinoGardenController.php
├── JardinoComponent.php
└── JardinoServiceProvider.php
```

---

## Service Architecture

### JardinoGoalService

```php
createGoal(int $activityId, int $userId, int $storyId, int $targetWordCount): JardinoGoal
updateTargetWordCount(int $goalId, int $newTarget): void
changeTargetStory(int $goalId, int $newStoryId): void
```

### JardinoProgressService

```php
handleWordCountChange(int $storyId, int $newWordCount): void
calculateProgress(JardinoGoal $goal): array
```

### JardinoFlowerService

**Flower Calculation Formula:**

```php
$flowerEligibleWords = sum(biggest - initial);
$progressFlowers = floor(($flowerEligibleWords / $target) * 20);
$daysSinceStart = floor((now() - activityStart) / 86400);
$dailyLimitFlowers = 2 * $daysSinceStart;

return min($progressFlowers, $dailyLimitFlowers, 25);
```

### JardinoGardenService

```php
getGardenData(int $activityId, int $userId): array
plantFlower(int $activityId, int $userId, int $x, int $y, string $flowerImage): void
removeFlower(int $activityId, int $userId, int $x, int $y): void
```

---

## Event-Driven Integration

### Story Events → JardiNo Listeners

**JardinoServiceProvider.php:**

```php
Event::listen(ChapterCreated::class, HandleChapterCreated::class);
Event::listen(ChapterUpdated::class, HandleChapterUpdated::class);
Event::listen(ChapterDeleted::class, HandleChapterDeleted::class);
```

**HandleChapterUpdated.php:**

```php
public function handle(ChapterUpdated $event): void
{
    $this->progressService->handleWordCountChange(
        storyId: $event->storySnapshot->story_id,
        newWordCount: $event->storySnapshot->total_word_count
    );
}
```

---

## Garden Rendering (Alpine.js + CSS Grid)

### Server Response (Sparse Data)

```php
[
    'grid_size' => ['width' => 50, 'height' => 50],
    'cells' => [
        // Only occupied cells (~1,250 max, not 2,500)
        ['x' => 5, 'y' => 10, 'type' => 'flower', 'flower_image' => '12.png', 
         'owner_id' => 123, 'owner_name' => 'Alice', 'is_mine' => false],
        ['x' => 15, 'y' => 20, 'type' => 'blocked'],
    ]
]
```

### Frontend (garden.blade.php)

```blade
<div x-data="jardinoGarden(@js($gardenData))">
    <div class="garden-grid" :style="`grid-template-columns: repeat(${gridWidth}, 1fr);`">
        <template x-for="y in gridHeight">
            <template x-for="x in gridWidth">
                <div class="garden-cell" 
                     :class="getCellClass(x, y)"
                     @click="handleCellClick(x, y)"
                     @mouseenter="showTooltip(x, y, $event)">
                    <template x-if="getCell(x, y)?.type === 'flower'">
                        <img :src="`/images/jardino/flowers/${getCell(x, y).flower_image}`">
                    </template>
                </div>
            </template>
        </template>
    </div>
</div>
```

### Alpine Component

```javascript
Alpine.data('jardinoGarden', (initialData) => ({
    cells: new Map(), // Sparse: key="x,y", value=cellData
    
    init() {
        initialData.cells.forEach(cell => {
            this.cells.set(`${cell.x},${cell.y}`, cell);
        });
    },
    
    getCell(x, y) {
        return this.cells.get(`${x},${y}`) || null;
    },
    
    handleCellClick(x, y) {
        const cell = this.getCell(x, y);
        if (!cell) {
            this.openFlowerModal(x, y);  // Plant flower
        } else if (cell.is_mine) {
            this.removeFlower(x, y);  // Remove own flower
        }
    }
}));
```

---

## API Integration

### Story Domain

```php
// Get stories for user
StoryPublicApi::getStoriesForUser($userId, includePrivate: true)

// Get word count
StoryPublicApi::getStoryWordCount($storyId)
```

### Profile Domain

```php
// Batch-load display names
ProfilePublicApi::getDisplayNamesBatch($userIds)
```

---

## Routes

```php
Route::middleware(['auth', 'verified'])->prefix('calendar/activities/{activity}')->group(function () {
    Route::get('/jardino', [JardinoDashboardController::class, 'index']);
    Route::post('/jardino/goal', [JardinoDashboardController::class, 'createGoal']);
    Route::put('/jardino/goal', [JardinoDashboardController::class, 'updateGoal']);
    Route::get('/jardino/garden', [JardinoGardenController::class, 'index']);
    Route::post('/jardino/garden/plant', [JardinoGardenController::class, 'plant']);
    Route::post('/jardino/garden/remove', [JardinoGardenController::class, 'remove']);
});
```

---

## Testing Strategy

### Feature Tests

**Goal Management:**
- User can create/update goal
- User can change target story
- Progress accumulates across stories

**Flower Calculation:**
- Flowers earned at 5% increments
- Daily limit (2/day) enforced
- Cap at 25 flowers

**Garden Operations:**
- User can plant/remove flowers
- Cannot plant in occupied/blocked cells
- Cannot remove other users' flowers

**Event Handling:**
- Word count updates on chapter events
- `biggest_word_count` never decreases

---

## Performance Considerations

1. **Sparse Garden Data:** Only ~1,250 cells transmitted (not 5,625)
2. **CSS Grid Layout:** Browser-optimized rendering
3. **Batch Profile Loading:** Single query for all display names
4. **Formula-Based Flowers:** No queue table to manage
5. **Small Cell Size:** 15-20px for pixel art effect, entire grid fits in viewport
