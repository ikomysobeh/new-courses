<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Video\TranscodeWebhookService;
use App\Services\Video\VpsApiClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TranscodeCallbackController extends Controller
{
    public function __construct(
        private readonly TranscodeWebhookService $webhookService,
        private readonly VpsApiClient $vpsClient,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        // Verify the request comes from our VPS using the shared project key
        $receivedKey = $request->input('project_key');
        $expectedKey = $this->vpsClient->getProjectKey();

        if (!empty($expectedKey) && $receivedKey !== $expectedKey) {
            return response()->json(['error' => 'Invalid project key'], 403);
        }

        $this->webhookService->handle($request->all());

        return response()->json(['message' => 'Callback received.']);
    }
}
