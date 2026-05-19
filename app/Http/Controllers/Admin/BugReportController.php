<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignBugReportRequest;
use App\Http\Requests\Admin\StoreBugReportRequest;
use App\Http\Requests\Admin\UpdateBugReportRequest;
use App\Http\Resources\Support\BugReportResource;
use App\Services\Support\BugReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BugReportController extends Controller
{
    public function __construct(private readonly BugReportService $service) {}

    public function getAll(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only(['status', 'priority', 'assigned_to', 'search']);

        return BugReportResource::collection($this->service->getAllForAdmin($filters));
    }

    public function getById(int $id): BugReportResource
    {
        return new BugReportResource($this->service->getById($id));
    }

    public function create(StoreBugReportRequest $request): JsonResponse
    {
        $report = $this->service->createReport(auth()->id(), $request->validated());

        return (new BugReportResource($report))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateBugReportRequest $request, int $id): BugReportResource
    {
        return new BugReportResource($this->service->update($id, $request->validated()));
    }

    public function assign(AssignBugReportRequest $request, int $id): BugReportResource
    {
        return new BugReportResource(
            $this->service->assign($id, $request->validated('assigned_to'))
        );
    }

    public function resolve(int $id): BugReportResource
    {
        return new BugReportResource($this->service->resolve($id));
    }

    public function delete(int $id): JsonResponse
    {
        $this->service->delete($id);

        return response()->json(['message' => 'Bug report deleted successfully.']);
    }
}
