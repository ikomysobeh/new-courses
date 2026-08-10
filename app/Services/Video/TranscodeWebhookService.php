<?php

namespace App\Services\Video;

use App\Models\Video;
use App\Models\VideoQuality;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TranscodeWebhookService
{
    public function __construct(
        private readonly VpsApiClient $vpsClient
    ) {}

    public function handle(array $payload): void
    {
        Log::info('[transcode] Webhook payload received', [
            'video_id' => $payload['video_id'] ?? null,
            'status'   => $payload['status'] ?? null,
            'keys'     => array_keys($payload),
        ]);

        $secret = config('app.transcode_secret');

        if ($secret) {
            $signature = $payload['signature'] ?? '';
            $expected  = hash_hmac('sha256', json_encode($payload['video_id'] ?? ''), $secret);

            if ($expected !== $signature) {
                Log::warning('[transcode] Webhook rejected: signature mismatch', [
                    'video_id' => $payload['video_id'] ?? null,
                ]);
            }

            abort_if($expected !== $signature, 403, 'Invalid transcode callback signature.');
        }

        $video = Video::query()->find($payload['video_id'] ?? null);

        if ($video === null) {
            Log::error('[transcode] Webhook video not found', ['video_id' => $payload['video_id'] ?? null]);
        }

        abort_if($video === null, 404, 'Video not found.');

        $this->processResult($video, $payload);
    }

    /**
     * Apply a transcode result to a video — shared by the real webhook (after
     * signature verification) and the "job already completed on the VPS"
     * path, where we synthesize the same shape ourselves without a signature.
     */
    public function processResult(Video $video, array $payload): void
    {
        $status = $payload['status'] ?? 'failed';

        if ($status === 'failed') {
            $video->update(['transcode_status' => 'failed']);
            Log::error('Transcode failed for video', [
                'video_id' => $video->id,
                'message'  => $payload['error'] ?? 'No error message provided.',
            ]);
            return;
        }

        // The VPS keeps the finished files and exposes them as download URLs.
        // Pull each quality into our own storage.
        $downloadUrls   = $payload['download_urls'] ?? [];
        $totalQualities = count($downloadUrls);
        $successCount   = 0;

        foreach ($downloadUrls as $quality => $url) {
            try {
                $this->downloadAndStoreQuality($video, (string) $quality, (string) $url);
                $successCount++;
            } catch (\Throwable $e) {
                Log::error("Failed to download {$quality} for video {$video->id}: {$e->getMessage()}");
            }
        }

        if ($successCount > 0) {
            $video->update(['transcode_status' => 'completed']);
            Log::info("Transcoding completed for video {$video->id} ({$successCount}/{$totalQualities} qualities)");
        } else {
            $video->update(['transcode_status' => 'failed']);
            Log::error("No qualities downloaded for video {$video->id}");
        }
    }

    /**
     * Download and store a single quality variant on the local (private) disk,
     * which is where the streaming route serves quality files from.
     */
    protected function downloadAndStoreQuality(Video $video, string $quality, string $url): void
    {
        $directory    = "videos/transcoded/{$video->id}";
        $relativePath = "{$directory}/{$quality}.mp4";
        $savePath     = Storage::disk('local')->path($relativePath);

        Log::info("[transcode] Downloading {$quality} for video {$video->id}", ['url' => $url]);

        Storage::disk('local')->makeDirectory($directory);

        $success = $this->vpsClient->downloadFile($url, $savePath);

        if ($success && file_exists($savePath)) {
            VideoQuality::updateOrCreate(
                ['video_id' => $video->id, 'quality' => $quality],
                [
                    'file_path' => $relativePath,
                    'file_size' => filesize($savePath),
                ]
            );
            Log::info("Downloaded {$quality} for video {$video->id}");
        } else {
            Log::error("Failed to download {$quality} for video {$video->id}");
        }
    }
}
