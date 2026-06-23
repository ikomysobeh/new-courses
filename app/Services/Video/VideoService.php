<?php

namespace App\Services\Video;

use App\Models\User;
use App\Models\Video;
use App\Models\VideoQuality;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VideoService
{
    public function __construct(private readonly VpsTranscodingService $transcodingService) {}

    public function getAllForAdmin(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Video::query()
            ->with(['videoCategory', 'creator'])
            ->latest();

        if (!empty($filters['video_category_id'])) {
            $query->where('video_category_id', $filters['video_category_id']);
        }

        if (!empty($filters['transcode_status'])) {
            $query->where('transcode_status', $filters['transcode_status']);
        }

        if (!empty($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        return $query->paginate($perPage);
    }

    public function getVideoByIdForAdmin(int $id): Video
    {
        return Video::query()
            ->with(['videoCategory', 'creator', 'qualities'])
            ->findOrFail($id);
    }

    public function createVideo(array $data, User $admin): Video
    {
        $thumbnail = $data['thumbnail'] ?? null;

        if ($thumbnail instanceof UploadedFile) {
            $data['thumbnail_path'] = $this->storeThumbnail($thumbnail);
        }

        unset($data['thumbnail']);

        $data['created_by']       = $admin->id;
        $data['transcode_status'] = 'pending';

        $video = Video::query()->create($data);

        $this->transcodingService->requestTranscoding($video);

        return $video;
    }

    public function updateVideo(int $id, array $data): Video
    {
        $video = Video::query()->findOrFail($id);

        $thumbnail = $data['thumbnail'] ?? null;

        if ($thumbnail instanceof UploadedFile) {
            $this->deleteThumbnailIfExists($video->thumbnail_path);
            $data['thumbnail_path'] = $this->storeThumbnail($thumbnail);
        }

        unset($data['transcode_status'], $data['created_by']);
        unset($data['thumbnail']);

        $video->update($data);

        return $video->fresh(['videoCategory', 'creator', 'qualities']);
    }

    public function deleteVideo(int $id): void
    {
        $video = Video::query()->findOrFail($id);
        $video->delete();
    }

    public function retryTranscode(int $id): Video
    {
        $video = Video::query()->findOrFail($id);

        VideoQuality::query()->where('video_id', $id)->delete();

        $video->update(['transcode_status' => 'pending']);

        $this->transcodingService->requestTranscoding($video);

        return $video->fresh(['videoCategory', 'creator', 'qualities']);
    }

    public function uploadSubtitle(int $id, UploadedFile $file): Video
    {
        $video = $this->getVideoByIdForAdmin($id);

        if ($video->subtitle_vtt_path && Storage::disk('local')->exists($video->subtitle_vtt_path)) {
            Storage::disk('local')->delete($video->subtitle_vtt_path);
        }

        $filename = $id . '_' . $file->getClientOriginalName();
        $path     = Storage::disk('local')->putFileAs('subtitles', $file, $filename);

        $video->update(['subtitle_vtt_path' => $path]);

        return $video->fresh(['videoCategory', 'creator', 'qualities']);
    }

    public function deleteSubtitle(int $id): void
    {
        $video = $this->getVideoByIdForAdmin($id);

        abort_if(!$video->subtitle_vtt_path, 422, 'No subtitle to delete.');

        if (Storage::disk('local')->exists($video->subtitle_vtt_path)) {
            Storage::disk('local')->delete($video->subtitle_vtt_path);
        }

        $video->update(['subtitle_vtt_path' => null]);
    }

    public function getAdminVideoCards(): array
    {
        return [
            [
                'key'   => 'total_videos',
                'title' => 'Total Videos',
                'value' => Video::query()->count(),
            ],
            [
                'key'   => 'pending_transcode',
                'title' => 'Pending Transcode',
                'value' => Video::query()->where('transcode_status', 'pending')->count(),
            ],
            [
                'key'   => 'completed_transcode',
                'title' => 'Completed Transcode',
                'value' => Video::query()->where('transcode_status', 'completed')->count(),
            ],
            [
                'key'   => 'failed_transcode',
                'title' => 'Failed Transcode',
                'value' => Video::query()->where('transcode_status', 'failed')->count(),
            ],
        ];
    }

    private function storeThumbnail(UploadedFile $file): string
    {
        $filename = Str::uuid() . '_' . $this->sanitizeFilename($file->getClientOriginalName());
        $path     = Storage::disk('public')->putFileAs('video-thumbnails', $file, $filename);

        if ($path === false) {
            throw new \RuntimeException('Failed to store video thumbnail.');
        }

        return $path;
    }

    private function deleteThumbnailIfExists(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    private function sanitizeFilename(string $filename): string
    {
        return preg_replace('/[\/\\\\]/', '', basename($filename));
    }
}
