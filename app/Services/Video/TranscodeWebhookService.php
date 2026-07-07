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

        // TEMP: log the raw payload so we can confirm the VPS's exact structure.
        Log::info('[transcode] callback payload received', [
            'video_id' => $video->id,
            'payload'  => $payload,
        ]);

        if ($status === 'completed' && !empty($payload['qualities'])) {
            $rows = [];

            foreach ($payload['qualities'] as $key => $quality) {
                // Tolerate multiple shapes the VPS may send:
                //  - list of objects:  [{quality, file_path, file_size}]
                //  - map name=>path:   {"720p": "videos/11/720p.mp4"}
                //  - map name=>object: {"720p": {file_path, file_size}}
                if (is_array($quality)) {
                    $qualityName = $quality['quality'] ?? (is_string($key) ? $key : null);
                    $filePath    = $quality['file_path'] ?? $quality['path'] ?? $quality['url'] ?? null;
                    $fileSize    = $quality['file_size'] ?? $quality['size'] ?? null;
                } elseif (is_string($quality)) {
                    $qualityName = is_string($key) ? $key : null;
                    $filePath    = $quality;
                    $fileSize    = null;
                } else {
                    continue;
                }

                if (empty($qualityName) || empty($filePath)) {
                    Log::warning('[transcode] skipped malformed quality entry', [
                        'video_id' => $video->id,
                        'key'      => $key,
                        'value'    => $quality,
                    ]);
                    continue;
                }

                $rows[] = [
                    'video_id'   => $video->id,
                    'quality'    => $qualityName,
                    'file_path'  => $filePath,
                    'file_size'  => $fileSize,
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

        if ($status === 'failed') {
            Log::error('Transcode failed for video', [
                'video_id' => $video->id,
                'message'  => $payload['error'] ?? 'No error message provided.',
            ]);
        }
    }
}
