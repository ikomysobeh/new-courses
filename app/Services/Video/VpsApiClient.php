<?php

namespace App\Services\Video;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VpsApiClient
{
    private string $url;
    private string $apiKey;
    private string $projectKey;

    public function __construct()
    {
        $this->url        = rtrim(config('services.transcoding.url', ''), '/');
        $this->apiKey     = config('services.transcoding.api_key', '');
        $this->projectKey = config('services.transcoding.project_key', '');
    }

    public function sendTranscodeRequest(array $data): bool
    {
        if (empty($this->url)) {
            Log::warning('VpsApiClient: TRANSCODING_URL is not configured.');
            return false;
        }

        $endpoint = "{$this->url}/api/transcode";

        Log::info('[transcode] Sending request to VPS', [
            'video_id'    => $data['video_id'] ?? null,
            'endpoint'    => $endpoint,
            'callback_url' => $data['callback_url'] ?? null,
            'video_url'   => $data['video_url'] ?? null,
            'qualities'   => $data['qualities'] ?? null,
            'has_api_key' => !empty($this->apiKey),
        ]);

        try {
            $response = Http::withHeaders(['X-API-Key' => $this->apiKey])
                ->timeout(30)
                ->post($endpoint, $data);

            Log::info('[transcode] VPS response received', [
                'video_id' => $data['video_id'] ?? null,
                'status'   => $response->status(),
                'body'     => $response->body(),
            ]);

            if (!$response->successful()) {
                Log::error('[transcode] VPS returned non-2xx status', [
                    'video_id' => $data['video_id'] ?? null,
                    'status'   => $response->status(),
                    'body'     => $response->body(),
                ]);
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('[transcode] Exception sending request to VPS', [
                'video_id' => $data['video_id'] ?? null,
                'error'    => $e->getMessage(),
                'endpoint' => $endpoint,
            ]);
            return false;
        }
    }

    /**
     * Download a transcoded file from the VPS, streaming it straight to disk.
     */
    public function downloadFile(string $url, string $savePath): bool
    {
        $directory = dirname($savePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $resource = fopen($savePath, 'wb');

        $response = Http::timeout(3600)
            ->withHeaders(['X-API-Key' => $this->apiKey])
            ->withOptions(['sink' => $resource])
            ->get($url);

        fclose($resource);

        return $response->successful();
    }

    public function getProjectKey(): string
    {
        return $this->projectKey;
    }
}
