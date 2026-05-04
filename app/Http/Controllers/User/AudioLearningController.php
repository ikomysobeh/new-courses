<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\UpdateAudioProgressRequest;
use App\Http\Resources\Audio\AudioPlayerResource;
use App\Http\Resources\Audio\AudioProgressResource;
use App\Http\Resources\Audio\AudioResource;
use App\Models\Audio;
use App\Services\Audio\AudioService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;

class AudioLearningController extends Controller
{
    public function __construct(private readonly AudioService $audioService) {}

    /**
     * List audios assigned to the authenticated user.
     */
    public function getAll(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only(['audio_category_id', 'search']);
        $perPage = (int) $request->query('per_page', 15);

        $audios = $this->audioService->getAllForUser($request->user(), $filters, $perPage);

        return AudioResource::collection($audios)
            ->additional([
                'cards' => $this->audioService->getUserAudioCards($request->user()),
            ]);
    }

    /**
     * Get a single assigned audio with user progress payload.
     */
    public function getById(Request $request, int $id): AudioPlayerResource
    {
        $audio = $this->audioService->findAudioForUserOrFail($request->user(), $id);

        return new AudioPlayerResource($audio);
    }

    /**
     * Stream assigned audio file.
     */
    public function stream(Request $request, int $id): BinaryFileResponse
    {
        $audio = Audio::query()->findOrFail($id);
        $path = $this->audioService->getStreamPath($request->user(), $audio);

        return response()->file(Storage::disk('local')->path($path));
    }

    /**
     * Update user audio progress with batched chunks.
     */
    public function updateProgress(UpdateAudioProgressRequest $request, int $audioId): AudioProgressResource
    {
        $progress = $this->audioService->updateProgressBatch(
            (int) $request->user()->id,
            $audioId,
            $request->validated(),
        );

        return new AudioProgressResource($progress);
    }
}
