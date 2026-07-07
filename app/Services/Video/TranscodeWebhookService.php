<?php

namespace App\Services\Video;

use App\Jobs\StoreTranscodedQualitiesJob;
use App\Models\Video;
use App\Models\VideoQuality;
use Illuminate\Support\Facades\Log;

class TranscodeWebhookService
{
    public function handle(array $payload): void
    {
        $secret = config('app.transcode_secret');

        if ($secret) {
            $signature = $payload['signature'] ?? '';
            $expected  = hash_hmac('sha256', json_encode($payload['video_id'] ?? ''), $secret);

            abort_if($expected !== $signature, 403, 'Invalid transcode callback signature.');
        }

        $video = Video::query()->find($payload['video_id'] ?? null);

        abort_if($video === null, 404, 'Video not found.');

        $status = $payload['status'] ?? 'failed';

        $video->update(['transcode_status' => $status]);

        if ($status === 'completed') {
            $downloadUrls = $payload['download_urls'] ?? [];

            if (!empty($downloadUrls) && is_array($downloadUrls)) {
                // Real VPS contract: files live on the VPS behind download URLs.
                // Pull each quality into local storage via a job. Keep the
                // callback fast — we mark 'completed' only once files land.
                $video->update(['transcode_status' => 'processing']);
                StoreTranscodedQualitiesJob::dispatch($video->id, $downloadUrls);
            } elseif (!empty($payload['qualities']) && is_array($payload['qualities'])) {
                // Legacy contract: qualities delivered inline as objects with
                // an already-local file_path.
                $rows = [];
                foreach ($payload['qualities'] as $quality) {
                    if (!is_array($quality) || empty($quality['quality']) || empty($quality['file_path'])) {
                        continue;
                    }
                    $rows[] = [
                        'video_id'   => $video->id,
                        'quality'    => $quality['quality'],
                        'file_path'  => $quality['file_path'],
                        'file_size'  => $quality['file_size'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                if (!empty($rows)) {
                    VideoQuality::upsert(
                        $rows,
                        ['video_id', 'quality'],
                        ['file_path', 'file_size', 'updated_at']
                    );
                }
            }
        }

        if ($status === 'failed') {
            Log::error('Transcode failed for video', [
                'video_id' => $video->id,
                'message'  => $payload['error'] ?? 'No error message provided.',
            ]);
        }
    }
}
