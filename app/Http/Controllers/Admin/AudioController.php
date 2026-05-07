<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAudioRequest;
use App\Http\Requests\Admin\UpdateAudioRequest;
use App\Http\Resources\Audio\AdminAudioDetailResource;
use App\Http\Resources\Audio\AudioResource;
use App\Models\Audio;
use App\Services\Audio\AudioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AudioController extends Controller
{
    public function __construct(private readonly AudioService $audioService) {}

    /**
     * List audio content for admins.
     */
    public function getAll(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only(['audio_category_id', 'search']);
        $perPage = (int) $request->query('per_page', 15);

        $audios = $this->audioService->getAllForAdmin($filters, $perPage);

        return AudioResource::collection($audios)
            ->additional([
                'cards' => $this->audioService->getAdminAudioCards(),
            ]);
    }

    /**
     * Create audio content.
     */
    public function create(StoreAudioRequest $request): JsonResponse
    {
        $audio = $this->audioService->createAudio($request->validated());

        return (new AudioResource($audio))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Get audio details by id.
     */
    public function getById(int $id): AdminAudioDetailResource
    {
        $audio = $this->audioService->findAudioForAdminOrFail($id);

        return new AdminAudioDetailResource($audio);
    }

    /**
     * Stream audio file for admin preview.
     */
    public function stream(int $id): BinaryFileResponse
    {
        $audio = Audio::query()->findOrFail($id);

        abort_if(
            ! $audio->local_path || ! Storage::disk('local')->exists($audio->local_path),
            404,
            'Audio file not found.'
        );

        return response()->file(Storage::disk('local')->path($audio->local_path));
    }

    /**
     * Update audio content.
     */
    public function update(UpdateAudioRequest $request, int $id): AudioResource
    {
        $audio = Audio::query()->findOrFail($id);
        $audio = $this->audioService->updateAudio($audio, $request->validated());

        return new AudioResource($audio);
    }

    /**
     * Soft delete audio content.
     */
    public function delete(int $id): JsonResponse
    {
        $audio = Audio::query()->findOrFail($id);
        $this->audioService->deleteAudio($audio);

        return response()->json([
            'message' => 'Audio deleted successfully.',
        ]);
    }
}
