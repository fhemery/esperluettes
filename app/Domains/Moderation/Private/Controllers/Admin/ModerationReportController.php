<?php

namespace App\Domains\Moderation\Private\Controllers\Admin;

use App\Domains\Moderation\Private\Models\ModerationReport;
use App\Domains\Moderation\Public\Api\ModerationPublicApi;
use App\Domains\Moderation\Public\Services\ModerationRegistry;
use App\Domains\Shared\Contracts\ProfilePublicApi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class ModerationReportController extends Controller
{
    public function __construct(
        private readonly ModerationPublicApi $api,
        private readonly ModerationRegistry $registry,
        private readonly ProfilePublicApi $profileApi,
    ) {}

    public function index(Request $request): View
    {
        // ConvertEmptyStringsToNull turns "" into null, so we use has() to distinguish
        // "filter not submitted" (→ default) from "filter cleared" (→ show all)
        $status = $request->has('status') ? ($request->get('status') ?? '') : 'pending';
        $topicKey = $request->get('topic_key') ?? '';

        $query = ModerationReport::query()
            ->with('reason')
            ->latest('id');

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($topicKey !== '') {
            $query->where('topic_key', $topicKey);
        }

        $reports = $query->paginate(20)->withQueryString();

        $reporterIds = $reports->pluck('reported_by_user_id')->filter()->unique()->values()->all();
        $profiles = $this->profileApi->getPublicProfiles($reporterIds);

        $topics = $this->registry->getTopics();
        $distinctTopics = ModerationReport::query()->distinct()->pluck('topic_key');

        return view('moderation::pages.admin.moderation-reports.index', compact(
            'reports', 'profiles', 'topics', 'distinctTopics', 'status', 'topicKey'
        ));
    }

    public function show(ModerationReport $moderationReport): View
    {
        $moderationReport->load('reason');

        $reporter = $this->profileApi->getPublicProfile($moderationReport->reported_by_user_id);

        $snapshot = null;
        if (
            $moderationReport->content_snapshot
            && $this->registry->hasFormatter($moderationReport->topic_key)
        ) {
            $formatter = $this->registry->getFormatter($moderationReport->topic_key);
            $rendered = (string) $formatter->render($moderationReport->content_snapshot);
            $snapshot = trim($rendered) !== '' ? $rendered : null;
        }

        $topicLabel = $this->registry->hasTopic($moderationReport->topic_key)
            ? $this->registry->getTopic($moderationReport->topic_key)['displayName']
            : $moderationReport->topic_key;

        return view('moderation::pages.admin.moderation-reports.show', compact(
            'moderationReport', 'reporter', 'snapshot', 'topicLabel'
        ));
    }

    public function approve(ModerationReport $moderationReport): RedirectResponse
    {
        $this->api->approveReport($moderationReport->id);

        return redirect()->route('moderation.admin.moderation-reports.index')
            ->with('success', __('moderation::admin.reports.approved'));
    }

    public function dismiss(ModerationReport $moderationReport): RedirectResponse
    {
        $this->api->rejectReport($moderationReport->id);

        return redirect()->route('moderation.admin.moderation-reports.index')
            ->with('success', __('moderation::admin.reports.dismissed'));
    }

    public function updateComment(Request $request, ModerationReport $moderationReport): RedirectResponse
    {
        $validated = $request->validate([
            'review_comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $moderationReport->update(['review_comment' => $validated['review_comment']]);

        return redirect()->route('moderation.admin.moderation-reports.show', $moderationReport)
            ->with('success', __('moderation::admin.reports.comment_saved'));
    }

    public function destroy(ModerationReport $moderationReport): RedirectResponse
    {
        $this->api->deleteReport($moderationReport->id);

        return redirect()->route('moderation.admin.moderation-reports.index')
            ->with('success', __('moderation::admin.reports.deleted'));
    }
}
