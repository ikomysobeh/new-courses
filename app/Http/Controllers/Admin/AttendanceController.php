<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAttendanceRequest;
use App\Http\Resources\Course\ClockingResource;
use App\Services\Course\ClockingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AttendanceController extends Controller
{
    public function __construct(private readonly ClockingService $clockingService) {}

    /** Get all clocking records (admin view, paginated). */
    public function getAll(Request $request): AnonymousResourceCollection
    {
        $records = $this->clockingService->getAllForAdmin($request->only('user_id', 'course_id'));

        return ClockingResource::collection($records)
            ->additional([
                'cards' => $this->clockingService->getAdminAttendanceCards(),
            ]);
    }

    /** Update a clocking record (recalculates duration if times changed). */
    public function update(UpdateAttendanceRequest $request, int $id): ClockingResource
    {
        $clocking = $this->clockingService->updateAttendance($id, $request->validated());

        return new ClockingResource($clocking);
    }

    /** Hard delete a clocking record. */
    public function delete(int $id): JsonResponse
    {
        $this->clockingService->deleteAttendance($id);

        return response()->json(['message' => 'Attendance record deleted successfully.']);
    }
}
