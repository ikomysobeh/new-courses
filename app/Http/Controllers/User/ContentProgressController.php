<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\OnlineCourse\UpdatePdfProgressRequest;
use App\Services\OnlineCourse\User\ContentProgressService;

class ContentProgressController extends Controller
{
    public function __construct(private ContentProgressService $service) {}

    public function updatePdf(UpdatePdfProgressRequest $request)
    {
        $result = $this->service->updatePdfProgress(auth()->id(), $request->validated());

        return response()->json($result);
    }
}
