<?php

namespace App\Services\Video;

use App\Models\User;
use App\Models\Video;
use App\Models\VideoQuality;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class VideoService
{
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
        $data['created_by']       = $admin->id;
        $data['transcode_status'] = 'pending';

        return Video::query()->create($data);
    }

    public function updateVideo(int $id, array $data): Video
    {
        $video = Video::query()->findOrFail($id);

        unset($data['transcode_status'], $data['created_by']);

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

        return $video->fresh(['videoCategory', 'creator', 'qualities']);
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
}
