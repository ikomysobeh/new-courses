<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\User\OnlineCourse\ContentViewResource;
use App\Http\Resources\User\OnlineCourse\UserCourseDetailResource;
use App\Http\Resources\User\OnlineCourse\UserCourseListResource;
use App\Services\OnlineCourse\User\UserCourseService;
use Illuminate\Http\Request;

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
}
