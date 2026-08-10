<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttentionScore\PreviewAttentionScoreConfigRequest;
use App\Http\Requests\AttentionScore\StoreAttentionScoreConfigRequest;
use App\Http\Resources\AttentionScore\AttentionScoreConfigHistoryResource;
use App\Http\Resources\AttentionScore\AttentionScoreConfigResource;
use App\Http\Resources\AttentionScore\AttentionScoreRecalculationJobResource;
use App\Models\AttentionScoreConfig;
use App\Models\AttentionScoreRecalculationJob;
use App\Services\AttentionScore\AttentionScoreConfigService;
use App\Services\AttentionScore\AttentionScoreEngine;
use App\Services\AttentionScore\AttentionScoreRecalculationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AttentionScoreConfigController extends Controller
{
    public function __construct(
        private readonly AttentionScoreConfigService $configService,
        private readonly AttentionScoreRecalculationService $recalculationService,
        private readonly AttentionScoreEngine $engine,
    ) {}

    public function getActive(): AttentionScoreConfigResource
    {
        return new AttentionScoreConfigResource($this->configService->getActiveConfig());
    }

    public function getHistory(): AnonymousResourceCollection
    {
        return AttentionScoreConfigHistoryResource::collection(
            $this->configService->getConfigHistory()->load('createdBy')
        );
    }

    public function preview(PreviewAttentionScoreConfigRequest $request): JsonResponse
    {
        $transientConfig = new AttentionScoreConfig(['config' => $request->validated('config')]);

        $results = collect($this->workedExamples())->map(function (array $example) use ($transientConfig) {
            $score = $example['content_type'] === 'pdf'
                ? $this->engine->calculatePdfScore($example['metrics']['completion_percentage'])
                : $this->engine->calculateVideoScore($example['metrics'], $transientConfig);

            return [
                'label'    => $example['label'],
                'expected' => $example['expected'],
                'result'   => is_array($score) ? $score : ['score' => $score],
            ];
        });

        return response()->json(['examples' => $results]);
    }

    public function save(StoreAttentionScoreConfigRequest $request): JsonResponse
    {
        $config = $this->configService->saveNewConfig($request->validated(), $request->user());
        $job    = $this->recalculationService->dispatchRecalculation($config);

        return response()->json([
            'config' => new AttentionScoreConfigResource($config),
            'recalculation_job' => new AttentionScoreRecalculationJobResource($job),
        ], 201);
    }

    public function restore(int $id): JsonResponse
    {
        $config = $this->configService->restoreConfig($id, request()->user());
        $job    = $this->recalculationService->dispatchRecalculation($config);

        return response()->json([
            'config' => new AttentionScoreConfigResource($config),
            'recalculation_job' => new AttentionScoreRecalculationJobResource($job),
        ]);
    }

    public function getRecalculationJobStatus(int $id): AttentionScoreRecalculationJobResource
    {
        return new AttentionScoreRecalculationJobResource(AttentionScoreRecalculationJob::findOrFail($id));
    }

    /**
     * The three PDF worked examples, used as the settings page's live-preview
     * fixtures so the client can compare his edits against known-good baselines
     * (Excellent=100, Moderate=85, Low Engagement=25 under the default config).
     */
    private function workedExamples(): array
    {
        return [
            [
                'label'         => 'Excellent Learner',
                'content_type'  => 'video',
                'expected'      => 100,
                'metrics'       => [
                    'active_playback_time'      => 420,
                    'video_duration'            => 300,
                    'completion_percentage'     => 95,
                    'speed_changes'             => 0,
                    'unwatched_seconds_skipped' => 0,
                ],
            ],
            [
                'label'         => 'Moderate Learner',
                'content_type'  => 'video',
                'expected'      => 85,
                'metrics'       => [
                    'active_playback_time'      => 240,
                    'video_duration'            => 300,
                    'completion_percentage'     => 80,
                    'speed_changes'             => 1,
                    'unwatched_seconds_skipped' => 30,
                ],
            ],
            [
                'label'         => 'Low Engagement',
                'content_type'  => 'video',
                'expected'      => 25,
                'metrics'       => [
                    'active_playback_time'      => 120,
                    'video_duration'            => 300,
                    'completion_percentage'     => 100,
                    'speed_changes'             => 4,
                    'unwatched_seconds_skipped' => 120,
                ],
            ],
        ];
    }
}
