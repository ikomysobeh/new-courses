<?php

namespace App\Services\Video;

use App\Models\Video;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class VpsTranscodingService
{
    public function __construct(private readonly VpsApiClient $vpsClient) {}

    public function requestTranscoding(Video $video): void
    {
        // Generate a signed URL so the VPS can download the original video file.
        // The signed URL is valid for 4 hours and requires no auth token.
        $videoUrl = URL::temporarySignedRoute(
            'media.video-direct',
            now()->addHours(4),
            ['video_id' => $video->id]
        );

        $sent = $this->vpsClient->sendTranscodeRequest([
            'video_id'     => (string) $video->id,
            'video_url'    => $videoUrl,
            'callback_url' => route('transcode.callback'),
            'qualities'    => ['720p', '480p', '360p'],
        ]);

        if ($sent) {
            $video->update(['transcode_status' => 'processing']);
            Log::info('Transcode request sent to VPS', ['video_id' => $video->id]);
        } else {
            $video->update(['transcode_status' => 'failed']);
            Log::error('Transcode request to VPS failed', ['video_id' => $video->id]);
        }
    }
}
