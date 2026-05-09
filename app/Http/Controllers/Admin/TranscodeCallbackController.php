<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Video\TranscodeWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TranscodeCallbackController extends Controller
{
    public function __construct(private readonly TranscodeWebhookService $webhookService) {}

    public function handle(Request $request): JsonResponse
    {
        $this->webhookService->handle($request->all());

        return response()->json(['message' => 'Callback received.']);
    }
}
