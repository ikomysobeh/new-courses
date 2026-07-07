<?php

namespace App\Jobs;

use App\Models\Video;
use App\Models\VideoQuality;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Downloads each transcoded quality from the VPS download URLs and stores it
 * on the local disk, then records the quality row. The VPS exposes finished
 * files as URLs (it does not push them to us), so we must pull them in.
 */
class StoreTranscodedQualitiesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    /**
     * @param array<string,string> $downloadUrls  map of quality => download URL
     */
    public function __construct(
        public readonly int   $videoId,
        public readonly array $downloadUrls,
    ) {}

    public function handle(): void
    {
        $video = Video::query()->find($this->videoId);

        if (!$video) {
            Log::error('[transcode] StoreTranscodedQualitiesJob: video not found', ['video_id' => $this->videoId]);
            return;
        }

        $apiKey = (string) config('services.transcoding.api_key', '');
        $stored = 0;

        foreach ($this->downloadUrls as $quality => $url) {
            if (!is_string($url) || $url === '') {
                continue;
            }

            $relativePath = "videos/{$this->videoId}/{$quality}.mp4";

            try {
                Storage::disk('local')->makeDirectory("videos/{$this->videoId}");
                $destination = Storage::disk('local')->path($relativePath);

                // Stream the file straight to disk (sink) so large files don't
                // blow the memory limit.
                $response = Http::withHeaders(['X-API-Key' => $apiKey])
                    ->timeout(600)
                    ->sink($destination)
                    ->get($url);

                if (!$response->successful()) {
                    Log::error('[transcode] failed to download quality', [
                        'video_id' => $this->videoId,
                        'quality'  => $quality,
                        'status'   => $response->status(),
                        'url'      => $url,
                    ]);
                    continue;
                }

                $size = Storage::disk('local')->exists($relativePath)
                    ? Storage::disk('local')->size($relativePath)
                    : null;

                VideoQuality::upsert(
                    [[
                        'video_id'   => $this->videoId,
                        'quality'    => $quality,
                        'file_path'  => $relativePath,
                        'file_size'  => $size,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]],
                    ['video_id', 'quality'],
                    ['file_path', 'file_size', 'updated_at']
                );

                $stored++;
            } catch (\Throwable $e) {
                Log::error('[transcode] exception downloading quality', [
                    'video_id' => $this->videoId,
                    'quality'  => $quality,
                    'error'    => $e->getMessage(),
                ]);
            }
        }

        if ($stored === 0) {
            $video->update(['transcode_status' => 'failed']);
            Log::error('[transcode] no qualities stored for video', ['video_id' => $this->videoId]);
        } else {
            $video->update(['transcode_status' => 'completed']);
            Log::info('[transcode] stored qualities for video', [
                'video_id' => $this->videoId,
                'count'    => $stored,
            ]);
        }
    }
}
