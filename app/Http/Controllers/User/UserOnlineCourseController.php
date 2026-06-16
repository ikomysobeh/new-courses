<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\User\OnlineCourse\ContentViewResource;
use App\Http\Resources\User\OnlineCourse\UserCourseDetailResource;
use App\Http\Resources\User\OnlineCourse\UserCourseListResource;
use App\Models\CourseOnlineAssignment;
use App\Models\ModuleContent;
use App\Services\OnlineCourse\User\UserCourseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UserOnlineCourseController extends Controller
{
    public function __construct(private UserCourseService $service) {}

    public function index(Request $request)
    {
        $paginator = $this->service->getUserCourses(
            auth()->id(),
            $request->only(['status', 'search', 'per_page'])
        );

        return UserCourseListResource::collection($paginator);
    }

    public function show(int $id)
    {
        $result = $this->service->getCourseDetail(auth()->id(), $id);

        return new UserCourseDetailResource($result);
    }

    public function content(int $courseId, int $contentId)
    {
        $result = $this->service->getContentView(auth()->id(), $courseId, $contentId);

        return new ContentViewResource($result);
    }

    public function resume(int $contentId)
    {
        $result = $this->service->getResumePosition(auth()->id(), $contentId);

        return response()->json($result);
    }

    public function downloadAttachment(int $courseId, int $contentId)
    {
        $userId = auth()->id();

        $assigned = CourseOnlineAssignment::where('user_id', $userId)
            ->where('course_online_id', $courseId)
            ->exists();

        if (!$assigned) {
            abort(403, 'Not assigned to this course.');
        }

        $content = ModuleContent::with('module')->findOrFail($contentId);

        if ($content->module->course_online_id !== $courseId) {
            abort(404, 'Content not found in this course.');
        }

        if (!$content->attachment_path) {
            abort(404, 'No attachment for this content.');
        }

        $filename = $content->attachment_name ?? basename($content->attachment_path);

        return Storage::disk('public')->download($content->attachment_path, $filename);
    }
}
