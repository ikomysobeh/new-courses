<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\Evaluation\EvaluationResource;
use App\Services\Evaluation\Submission\EvaluationService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class UserEvaluationController extends Controller
{
    public function __construct(private readonly EvaluationService $service) {}

    public function getAll(Request $request): AnonymousResourceCollection
    {
        return EvaluationResource::collection($this->service->getAllForUser(auth()->id()));
    }

    public function getById(int $id): EvaluationResource
    {
        $evaluation = $this->service->getById($id);

        if ($evaluation->user_id !== auth()->id()) {
            throw new AccessDeniedHttpException('You are not allowed to view this evaluation.');
        }

        return new EvaluationResource($evaluation);
    }
}
