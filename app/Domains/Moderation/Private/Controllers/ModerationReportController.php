<?php

namespace App\Domains\Moderation\Private\Controllers;

use App\Domains\Moderation\Private\Requests\StoreReportRequest;
use App\Domains\Moderation\Private\Services\ModerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class ModerationReportController extends Controller
{
    public function __construct(
        private ModerationService $service
    ) {
    }

    /**
     * Load report form with reasons for a specific topic (AJAX endpoint, returns HTML).
     */
    public function form(string $topicKey, int $entityId): Response
    {
        try {
            $reasons = $this->service->getReasonsForTopic($topicKey);

            return response()->view('moderation::report-form', [
                'topicKey' => $topicKey,
                'entityId' => $entityId,
                'reasons' => $reasons,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->view('moderation::report-error', [
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Submit a report (AJAX endpoint, returns JSON).
     */
    public function store(StoreReportRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            $report = $this->service->createReport(
                topicKey: $data['topic_key'],
                entityId: (int) $data['entity_id'],
                reasonId: (int) $data['reason_id'],
                description: $data['description'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => __('moderation::report.submitted'),
                'report_id' => $report->id,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('moderation::report.error'),
            ], 500);
        }
    }
}
