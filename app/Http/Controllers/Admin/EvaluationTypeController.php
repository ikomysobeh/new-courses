<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Evaluation\UpdateEvaluationTypeRequest;
use App\Http\Resources\Evaluation\EvaluationTypeResource;
use App\Services\Evaluation\Config\EvaluationConfigService;
use Illuminate\Http\JsonResponse;

class EvaluationTypeController extends Controller
{
    public function __construct(private readonly EvaluationConfigService $service) {}

    public function update(UpdateEvaluationTypeRequest $request, int $id): EvaluationTypeResource
    {
        return new EvaluationTypeResource($this->service->updateType($id, $request->validated()));
    }

    public function delete(int $id): JsonResponse
    {
        $this->service->deleteType($id);

        return response()->json(['message' => 'Evaluation type deleted successfully.']);
    }
}
