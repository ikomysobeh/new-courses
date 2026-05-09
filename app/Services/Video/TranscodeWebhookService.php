<?php

namespace App\Services\Video;

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

        if ($status === 'completed' && !empty($payload['qualities'])) {
            $rows = [];
            foreach ($payload['qualities'] as $quality) {
                $rows[] = [
                    'video_id'   => $video->id,
                    'quality'    => $quality['quality'],
                    'file_path'  => $quality['file_path'],
                    'file_size'  => $quality['file_size'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            VideoQuality::upsert(
                $rows,
                ['video_id', 'quality'],
                ['file_path', 'file_size', 'updated_at']
            );
        }

        if ($status === 'failed') {
            Log::error('Transcode failed for video', [
                'video_id' => $video->id,
                'message'  => $payload['error'] ?? 'No error message provided.',
            ]);
        }
    }
}
