<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAudioAssignmentRequest;
use App\Http\Resources\Audio\AudioAssignmentResource;
use App\Models\AudioAssignment;
use App\Services\Audio\AudioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AudioAssignmentController extends Controller
{
    public function __construct(private readonly AudioService $audioService) {}

    /**
     * List audio assignments.
     */
    public function getAll(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only(['audio_id', 'user_id', 'search']);
        $perPage = (int) $request->query('per_page', 15);

        $assignments = $this->audioService->getAssignmentsList($filters, $perPage);

        return AudioAssignmentResource::collection($assignments)
            ->additional([
                'cards' => $this->audioService->getAdminAudioAssignmentCards(),
            ]);
    }

    /**
     * Assign audio to one or more users.
     */
    public function create(StoreAudioAssignmentRequest $request): JsonResponse
    {
        $result = $this->audioService->assignAudioToUsers(
            (int) $request->validated('audio_id'),
            $request->validated('user_ids'),
            (int) $request->user()->id,
            (bool) $request->boolean('send_notification', true),
        );

        return AudioAssignmentResource::collection($result['created'])
            ->additional([
                'skipped_user_ids' => $result['skipped_user_ids'],
            ])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Remove an audio assignment.
     */
    public function delete(int $id): JsonResponse
    {
        $assignment = AudioAssignment::query()->findOrFail($id);
        $this->audioService->removeAssignment($assignment);

        return response()->json([
            'message' => 'Audio assignment deleted successfully.',
        ]);
    }
}
