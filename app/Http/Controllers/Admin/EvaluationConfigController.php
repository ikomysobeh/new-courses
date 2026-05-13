<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Evaluation\StoreEvaluationConfigRequest;
use App\Http\Requests\Evaluation\UpdateEvaluationConfigRequest;
use App\Http\Requests\Evaluation\StoreEvaluationTypeRequest;
use App\Http\Resources\Evaluation\EvaluationConfigResource;
use App\Http\Resources\Evaluation\EvaluationTypeResource;
use App\Services\Evaluation\Config\EvaluationConfigService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EvaluationConfigController extends Controller
{
    public function __construct(private readonly EvaluationConfigService $service) {}

    public function getAll(): AnonymousResourceCollection
    {
        return EvaluationConfigResource::collection($this->service->getAllConfigs());
    }

    public function create(StoreEvaluationConfigRequest $request): JsonResponse
    {
        $config = $this->service->createConfig($request->validated());

        return (new EvaluationConfigResource($config->load('types')))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateEvaluationConfigRequest $request, int $id): EvaluationConfigResource
    {
        return new EvaluationConfigResource($this->service->updateConfig($id, $request->validated()));
    }

    public function delete(int $id): JsonResponse
    {
        $this->service->deleteConfig($id);

        return response()->json(['message' => 'Evaluation config deleted successfully.']);
    }

    public function createType(StoreEvaluationTypeRequest $request, int $id): JsonResponse
    {
        $type = $this->service->addType($id, $request->validated());

        return (new EvaluationTypeResource($type))
            ->response()
            ->setStatusCode(201);
    }
}
