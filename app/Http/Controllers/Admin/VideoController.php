<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\StreamsRangedFiles;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreVideoRequest;
use App\Http\Requests\Admin\UpdateVideoRequest;
use App\Http\Requests\Admin\UpdateVideoSubtitleRequest;
use App\Http\Requests\Admin\VideoChunkUploadRequest;
use App\Http\Resources\Video\VideoDetailResource;
use App\Http\Resources\Video\VideoResource;
use App\Services\Video\VideoChunkUploadService;
use App\Services\Video\VideoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VideoController extends Controller
{
    use StreamsRangedFiles;

    public function __construct(
        private readonly VideoService $videoService,
        private readonly VideoChunkUploadService $chunkService,
    ) {}

    public function getAll(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only(['video_category_id', 'transcode_status', 'search']);
        $perPage = (int) $request->query('per_page', 15);

        $videos = $this->videoService->getAllForAdmin($filters, $perPage);

        return VideoResource::collection($videos)
            ->additional([
                'cards' => $this->videoService->getAdminVideoCards(),
            ]);
    }

    public function create(StoreVideoRequest $request): JsonResponse
    {
        $video = $this->videoService->createVideo($request->validated(), $request->user());

        return (new VideoDetailResource($video->load(['videoCategory', 'creator', 'qualities'])))
            ->response()
            ->setStatusCode(201);
    }

    public function getById(int $id): VideoDetailResource
    {
        $video = $this->videoService->getVideoByIdForAdmin($id);

        return new VideoDetailResource($video);
    }

    public function update(UpdateVideoRequest $request, int $id): VideoDetailResource
    {
        $video = $this->videoService->updateVideo($id, $request->validated());

        return new VideoDetailResource($video);
    }

    public function delete(int $id): JsonResponse
    {
        $this->videoService->deleteVideo($id);

        return response()->json(['message' => 'Video deleted successfully.']);
    }

    public function uploadChunk(VideoChunkUploadRequest $request): JsonResponse
    {
        $result = $this->chunkService->uploadChunk(
            $request->file('chunk'),
            $request->input('upload_uuid'),
            (int) $request->input('chunk_index'),
            (int) $request->input('total_chunks'),
            $request->input('original_filename'),
        );

        return response()->json($result);
    }

    public function revertChunk(Request $request): JsonResponse
    {
        $uuid = (string) $request->input('upload_uuid', '');

        $this->chunkService->revert($uuid);

        return response()->json(['message' => 'Upload reverted.']);
    }

    public function retryTranscode(int $id): VideoDetailResource
    {
        $video = $this->videoService->retryTranscode($id);

        return new VideoDetailResource($video);
    }

    public function getSubtitle(int $id): JsonResponse
    {
        $video = $this->videoService->getVideoByIdForAdmin($id);

        if (!$video->subtitle_vtt_path) {
            return response()->json(['data' => null]);
        }

        return response()->json([
            'data' => [
                'video_id'          => $video->id,
                'subtitle_vtt_path' => $video->subtitle_vtt_path,
            ],
        ]);
    }

    public function uploadSubtitle(UpdateVideoSubtitleRequest $request, int $id): VideoDetailResource
    {
        $video = $this->videoService->uploadSubtitle($id, $request->file('subtitle_file'));

        return new VideoDetailResource($video);
    }

    public function updateSubtitle(UpdateVideoSubtitleRequest $request, int $id): VideoDetailResource
    {
        $video = $this->videoService->uploadSubtitle($id, $request->file('subtitle_file'));

        return new VideoDetailResource($video);
    }

    public function deleteSubtitle(int $id): JsonResponse
    {
        $this->videoService->deleteSubtitle($id);

        return response()->json(['message' => 'Subtitle deleted.']);
    }

    /**
     * Stream the raw video file for admin preview.
     * Supports Range requests so the browser can seek without downloading the whole file.
     */
    public function stream(Request $request, int $id): StreamedResponse
    {
        $video = $this->videoService->getVideoByIdForAdmin($id);

        abort_unless(
            $video->file_path && Storage::disk('local')->exists($video->file_path),
            404,
            'Video file not found.'
        );

        $fullPath = Storage::disk('local')->path($video->file_path);
        $size     = filesize($fullPath);

        return $this->streamWithRangeSupport($request, $fullPath, 'video/mp4', $size);
    }

    /**
     * Return a short-lived signed URL for the video, so the browser can
     * play it directly via <video src=...> and issue its own Range
     * requests instead of the frontend fetching the whole file first.
     */
    public function streamUrl(int $id): JsonResponse
    {
        $video = $this->videoService->getVideoByIdForAdmin($id);

        abort_unless(
            $video->file_path && Storage::disk('local')->exists($video->file_path),
            404,
            'Video file not found.'
        );

        return response()->json([
            'url' => URL::temporarySignedRoute(
                'media.video-direct',
                now()->addHours(4),
                ['video_id' => $video->id],
            ),
        ]);
    }

    /**
     * Return the raw VTT subtitle text for admin inspection.
     */
    public function streamSubtitle(int $id): Response
    {
        $video = $this->videoService->getVideoByIdForAdmin($id);

        abort_unless(
            $video->subtitle_vtt_path && Storage::disk('local')->exists($video->subtitle_vtt_path),
            404,
            'Subtitle file not found.'
        );

        return response(Storage::disk('local')->get($video->subtitle_vtt_path), 200, [
            'Content-Type'        => 'text/vtt',
            'Content-Disposition' => 'inline',
        ]);
    }
}
