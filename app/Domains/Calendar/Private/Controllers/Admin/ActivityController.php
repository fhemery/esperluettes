<?php

namespace App\Domains\Calendar\Private\Controllers\Admin;

use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Calendar\Private\Models\Activity;
use App\Domains\Calendar\Private\Requests\Admin\ActivityRequest;
use App\Domains\Calendar\Public\Api\CalendarPublicApi;
use App\Domains\Calendar\Public\Api\CalendarRegistry;
use App\Domains\Calendar\Public\Contracts\ActivityToCreateDto;
use App\Domains\Calendar\Public\Contracts\ActivityToUpdateDto;
use App\Domains\Shared\Services\ImageService;
use App\Domains\Shared\Support\HtmlLinkUtils;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ActivityController extends Controller
{
    public function __construct(
        private readonly CalendarPublicApi $api,
        private readonly CalendarRegistry $registry,
        private readonly AuthPublicApi $authApi,
        private readonly ImageService $imageService,
    ) {}

    public function index(): View
    {
        $activities = Activity::query()->orderByDesc('created_at')->paginate(20);

        return view('calendar::pages.admin.activities.index', compact('activities'));
    }

    public function create(): View
    {
        return view('calendar::pages.admin.activities.create', [
            'activityTypes' => $this->activityTypeOptions(),
            'roleOptions' => $this->roleOptions(),
        ]);
    }

    public function store(ActivityRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $this->imageService->process(
                'public',
                'activities/' . date('Y/m'),
                $request->file('image'),
                [400, 800]
            );
        }

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

        $this->api->create($dto, (int) Auth::id());

        return redirect()->route('calendar.admin.activities.index')
            ->with('success', __('calendar::admin.activities.created'));
    }

    public function edit(Activity $activity): View
    {
        return view('calendar::pages.admin.activities.edit', [
            'activity' => $activity,
            'activityTypes' => $this->activityTypeOptions(),
            'roleOptions' => $this->roleOptions(),
        ]);
    }

    public function update(ActivityRequest $request, Activity $activity): RedirectResponse
    {
        $data = $request->validated();

        $imagePath = $activity->image_path;
        if ($request->boolean('image_remove')) {
            $this->imageService->deleteWithVariants('public', $activity->image_path);
            $imagePath = null;
        } elseif ($request->hasFile('image')) {
            $this->imageService->deleteWithVariants('public', $activity->image_path);
            $imagePath = $this->imageService->process(
                'public',
                'activities/' . date('Y/m'),
                $request->file('image'),
                [400, 800]
            );
        }

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

        $this->api->update($activity->id, $dto, (int) Auth::id());

        return redirect()->route('calendar.admin.activities.index')
            ->with('success', __('calendar::admin.activities.updated'));
    }

    public function destroy(Activity $activity): RedirectResponse
    {
        $this->api->delete($activity->id, (int) Auth::id());

        return redirect()->route('calendar.admin.activities.index')
            ->with('success', __('calendar::admin.activities.deleted'));
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
