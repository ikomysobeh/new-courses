<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Video\TranscodeWebhookService;
use App\Services\Video\VpsApiClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TranscodeCallbackController extends Controller
{
    public function __construct(
        private readonly TranscodeWebhookService $webhookService,
        private readonly VpsApiClient $vpsClient,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        Log::info('[transcode] Callback received from VPS', [
            'video_id' => $request->input('video_id'),
            'status'   => $request->input('status'),
            'ip'       => $request->ip(),
        ]);

        // Verify the request comes from our VPS using the shared project key
        $receivedKey = $request->input('project_key');
        $expectedKey = $this->vpsClient->getProjectKey();

        if (!empty($expectedKey) && $receivedKey !== $expectedKey) {
            Log::warning('[transcode] Callback rejected: project key mismatch', [
                'video_id'     => $request->input('video_id'),
                'received_key' => $receivedKey ? substr($receivedKey, 0, 4) . '…' : '(empty)',
            ]);

            return response()->json(['error' => 'Invalid project key'], 403);
        }

        $this->webhookService->handle($request->all());

        return response()->json(['message' => 'Callback received.']);
    }
}
