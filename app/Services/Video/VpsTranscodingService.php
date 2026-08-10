<?php

namespace App\Services\Video;

use App\Models\Video;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class VpsTranscodingService
{
    private const QUALITIES = ['720p', '480p', '360p'];

    public function __construct(
        private readonly VpsApiClient $vpsClient,
        private readonly TranscodeWebhookService $webhookService,
    ) {}

    public function requestTranscoding(Video $video): void
    {
        // Generate a signed URL so the VPS can download the original video file.
        // The signed URL is valid for 4 hours and requires no auth token.
        $videoUrl    = URL::temporarySignedRoute(
            'media.video-direct',
            now()->addHours(4),
            ['video_id' => $video->id]
        );
        $callbackUrl = route('transcode.callback');
        $token       = $video->transcodeToken();

        Log::info('[transcode] Preparing VPS request', [
            'video_id'     => $video->id,
            'token'        => $token,
            'video_url'    => $videoUrl,
            'callback_url' => $callbackUrl,
        ]);

        $result = $this->vpsClient->sendTranscodeRequest([
            'video_id'     => $token,
            'video_url'    => $videoUrl,
            'callback_url' => $callbackUrl,
            'qualities'    => self::QUALITIES,
        ]);

        // The VPS dedupes jobs by (project_key, video_id) and has no way to force
        // a new job or resend its callback for one it already finished. Since its
        // download URLs are deterministic, reconstruct them ourselves and process
        // the result the same way we would a real callback.
        if ($result['already_completed']) {
            Log::info('[transcode] Reconstructing result for already-completed VPS job', [
                'video_id' => $video->id,
            ]);

            $baseUrl    = rtrim(config('services.transcoding.url', ''), '/');
            $projectKey = $this->vpsClient->getProjectKey();

            $downloadUrls = [];
            foreach (self::QUALITIES as $quality) {
                $downloadUrls[$quality] = "{$baseUrl}/api/download/{$projectKey}/{$token}/{$quality}";
            }

            $this->webhookService->processResult($video, [
                'status'        => 'completed',
                'download_urls' => $downloadUrls,
            ]);
            return;
        }

        if ($result['ok']) {
            $video->update(['transcode_status' => 'processing']);
            Log::info('Transcode request sent to VPS', ['video_id' => $video->id]);
        } else {
            $video->update(['transcode_status' => 'failed']);
            Log::error('Transcode request to VPS failed', ['video_id' => $video->id]);
        }
    }
}
