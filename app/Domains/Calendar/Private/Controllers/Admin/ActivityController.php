<?php

namespace App\Domains\Calendar\Private\Controllers\Admin;

use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Calendar\Private\Models\Activity;
use App\Domains\Calendar\Private\Requests\Admin\ActivityRequest;
use App\Domains\Calendar\Public\Api\CalendarPublicApi;
use App\Domains\Calendar\Public\Api\CalendarRegistry;
use App\Domains\Calendar\Public\Contracts\ActivityToCreateDto;
use App\Domains\Calendar\Public\Contracts\ActivityToUpdateDto;
use App\Domains\Media\Public\Api\MediaPublicApi;
use App\Domains\Shared\Support\HtmlLinkUtils;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ActivityController extends Controller
{
    private const SCOPE = 'activities';

    public function __construct(
        private readonly CalendarPublicApi $api,
        private readonly CalendarRegistry $registry,
        private readonly AuthPublicApi $authApi,
        private readonly MediaPublicApi $media,
    ) {}

    /**
     * Resolve the image_path from the media-image-field payload.
     * A new upload is stored; otherwise the (possibly reused or kept) path is
     * used; empty means the image was removed. Files are never deleted here —
     * the Media GC reclaims any path no activity references anymore.
     *
     * @param array<string,mixed> $data validated request data
     */
    private function resolveImage(Request $request, array $data): ?string
    {
        $file = $request->file('image.file');

        return $file
            ? $this->media->store(self::SCOPE, $file)
            : (($data['image']['path'] ?? null) ?: null);
    }

    public function index(): View
    {
        $activities = Activity::query()->orderByDesc('created_at')->paginate(20);

        return view('calendar::pages.admin.activities.index', compact('activities'));
    }

    public function create(): View
    {
        return view('calendar::pages.admin.activities.create', [
            'activityTypes' => $this->activityTypeOptions(),
            'configComponents' => $this->configComponentKeys(),
            'roleOptions' => $this->roleOptions(),
        ]);
    }

    public function store(ActivityRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $imagePath = $this->resolveImage($request, $data);

        $dto = new ActivityToCreateDto(
            name: $data['name'],
            activity_type: $data['activity_type'],
            description: HtmlLinkUtils::addTargetBlankToExternalLinks($data['description'] ?? null),
            image_path: $imagePath,
            role_restrictions: $data['role_restrictions'] ?? null,
            requires_subscription: (bool) ($data['requires_subscription'] ?? false),
            max_participants: isset($data['max_participants']) ? (int) $data['max_participants'] : null,
            preview_starts_at: $this->parseDate($data['preview_starts_at'] ?? null),
            active_starts_at: $this->parseDate($data['active_starts_at'] ?? null),
            active_ends_at: $this->parseDate($data['active_ends_at'] ?? null),
            archived_at: $this->parseDate($data['archived_at'] ?? null),
        );

        // One transaction: an activity never exists without its type config.
        DB::transaction(function () use ($dto, $data) {
            $activityId = $this->api->create($dto, (int) Auth::id());
            $this->persistTypeConfig($data['activity_type'], $activityId, $data);
        });

        return redirect()->route('calendar.admin.activities.index')
            ->with('success', __('calendar::admin.activities.created'));
    }

    public function edit(Activity $activity): View
    {
        return view('calendar::pages.admin.activities.edit', [
            'activity' => $activity,
            'activityTypes' => $this->activityTypeOptions(),
            'configComponents' => $this->configComponentKeys(),
            'roleOptions' => $this->roleOptions(),
        ]);
    }

    public function update(ActivityRequest $request, Activity $activity): RedirectResponse
    {
        $data = $request->validated();

        $imagePath = $this->resolveImage($request, $data);

        $dto = new ActivityToUpdateDto(
            name: $data['name'],
            activity_type: $activity->activity_type,
            description: HtmlLinkUtils::addTargetBlankToExternalLinks($data['description'] ?? null),
            image_path: $imagePath,
            role_restrictions: $data['role_restrictions'] ?? null,
            requires_subscription: (bool) ($data['requires_subscription'] ?? false),
            max_participants: isset($data['max_participants']) ? (int) $data['max_participants'] : null,
            preview_starts_at: $this->parseDate($data['preview_starts_at'] ?? null),
            active_starts_at: $this->parseDate($data['active_starts_at'] ?? null),
            active_ends_at: $this->parseDate($data['active_ends_at'] ?? null),
            archived_at: $this->parseDate($data['archived_at'] ?? null),
        );

        // One transaction: the activity and its type config move together.
        DB::transaction(function () use ($activity, $dto, $data) {
            $this->api->update($activity->id, $dto, (int) Auth::id());
            $this->persistTypeConfig($activity->activity_type, $activity->id, $data);
        });

        return redirect()->route('calendar.admin.activities.index')
            ->with('success', __('calendar::admin.activities.updated'));
    }

    public function destroy(Activity $activity): RedirectResponse
    {
        $this->api->delete($activity->id, (int) Auth::id());

        return redirect()->route('calendar.admin.activities.index')
            ->with('success', __('calendar::admin.activities.deleted'));
    }

    /**
     * Hand the validated payload to the type's registration so it can save its
     * own config. Called from inside the activity transaction.
     *
     * @param array<string,mixed> $data validated request data
     */
    private function persistTypeConfig(string $activityType, int $activityId, array $data): void
    {
        if (! $this->registry->has($activityType)) {
            return;
        }

        $this->registry->get($activityType)->persistConfig($activityId, $data);
    }

    /**
     * Config component key per activity type, for the types that declare one.
     *
     * @return array<string,string>
     */
    private function configComponentKeys(): array
    {
        $keys = [];
        foreach ($this->registry->keys() ?? [] as $key) {
            $component = $this->registry->get($key)->configComponentKey();
            if ($component !== null) {
                $keys[$key] = $component;
            }
        }
        return $keys;
    }

    private function activityTypeOptions(): array
    {
        $opts = [];
        foreach ($this->registry->keys() ?? [] as $key) {
            $label = __('calendar::activities.' . $key);
            $opts[$key] = $label === 'calendar::activities.' . $key ? $key : $label;
        }
        return $opts;
    }

    private function roleOptions(): array
    {
        $opts = [];
        foreach ($this->authApi->getAllRoles() as $role) {
            $opts[] = ['slug' => $role->slug, 'name' => $role->name];
        }
        return $opts;
    }

    private function parseDate($value): ?\Carbon\CarbonInterface
    {
        if (!$value) {
            return null;
        }
        if ($value instanceof \Carbon\CarbonInterface) {
            return $value;
        }
        return Carbon::parse((string) $value);
    }
}
