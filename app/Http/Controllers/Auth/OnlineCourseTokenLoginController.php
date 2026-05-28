<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\CourseOnline;
use App\Models\CourseOnlineAssignment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OnlineCourseTokenLoginController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        abort_unless($request->hasValidSignature(), 403, 'Invalid or expired signed URL.');

        $userId         = (int) $request->query('user');
        $courseOnlineId = (int) $request->query('course_online');
        $token          = (string) $request->query('token', '');

        $user = User::query()->findOrFail($userId);
        CourseOnline::query()->findOrFail($courseOnlineId);

        abort_unless($token !== '' && $user->hasValidLoginToken($token), 403, 'Invalid or expired login token.');

        $isAssigned = CourseOnlineAssignment::query()
            ->where('user_id', $user->id)
            ->where('course_online_id', $courseOnlineId)
            ->exists();

        abort_unless($user->isAdmin() || $isAssigned, 403, 'Online course is not assigned to this user.');

        $plainToken = $user->createToken('online-course-email-login')->plainTextToken;

        $user->update([
            'login_token'            => null,
            'login_token_expires_at' => null,
        ]);

        $frontendBase = rtrim((string) (env('FRONTEND_URL', config('app.url'))), '/');
        $redirectUrl  = $frontendBase . '/online-courses/' . $courseOnlineId
            . '?token=' . urlencode($plainToken)
            . '&course_online=' . $courseOnlineId;

        return redirect()->away($redirectUrl);
    }
}
