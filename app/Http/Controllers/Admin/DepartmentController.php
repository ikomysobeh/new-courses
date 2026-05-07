<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DepartmentStoreRequest;
use App\Http\Requests\Admin\DepartmentUpdateRequest;
use App\Http\Resources\Admin\DepartmentResource;
use App\Http\Resources\Admin\DepartmentTreeResource;
use App\Models\Department;
use App\Services\Department\DepartmentService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\JsonResponse;

class DepartmentController extends Controller
{
    public function __construct(private readonly DepartmentService $departmentService)
    {
    }

    /**
     * List all departments
     *
     * Returns the full department hierarchy as a nested tree.
     */
    public function getAll(): AnonymousResourceCollection
    {
        $departments = $this->departmentService->getAll();

        return DepartmentTreeResource::collection($departments)
            ->additional([
                'cards' => $this->departmentService->getCards(),
            ]);
    }

    /**
     * Create a department
     */
    public function create(DepartmentStoreRequest $request): DepartmentResource
    {
        $department = $this->departmentService->create($request->validated());

        return new DepartmentResource($department);
    }

    /**
     * Update a department
     */
    public function update(DepartmentUpdateRequest $request, int $id): DepartmentResource
    {
        $department = Department::query()->findOrFail($id);
        $updated = $this->departmentService->update($department, $request->validated());

        return new DepartmentResource($updated);
    }

    /**
     * Delete a department
     *
     * Soft-deletes the department. Fails if it has child departments.
     */
    public function delete(int $id): JsonResponse
    {
        $department = Department::query()->findOrFail($id);
        $this->departmentService->delete($department);

        return response()->json([
            'message' => 'Department deleted successfully.',
        ]);
    }
}
