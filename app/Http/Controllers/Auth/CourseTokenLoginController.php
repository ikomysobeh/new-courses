<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseAssignment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CourseTokenLoginController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        abort_unless($request->hasValidSignature(), 403, 'Invalid or expired signed URL.');

        $userId   = (int) $request->query('user');
        $courseId = (int) $request->query('course');
        $token    = (string) $request->query('token', '');

        $user   = User::query()->findOrFail($userId);
        $course = Course::query()->findOrFail($courseId);

        abort_unless($token !== '' && $user->hasValidLoginToken($token), 403, 'Invalid or expired login token.');

        $isAssigned = CourseAssignment::query()
            ->where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->exists();

        abort_unless($user->isAdmin() || $isAssigned, 403, 'Course is not assigned to this user.');

        $plainToken = $user->createToken('course-email-login')->plainTextToken;

        $user->update([
            'last_login_at' => now(),
        ]);

        $frontendBase = rtrim((string) config('app.frontend_url'), '/');
        $redirectUrl  = $frontendBase . '/courses/' . $courseId
            . '?token=' . urlencode($plainToken)
            . '&course=' . $courseId;

        return redirect()->away($redirectUrl);
    }
}
