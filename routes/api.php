<?php

use App\Http\Controllers\AuthController as UnifiedAuthController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\AudioAssignmentController;
use App\Http\Controllers\Admin\AudioCategoryController;
use App\Http\Controllers\Admin\AudioController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\User\AuthController as UserAuthController;
use App\Http\Controllers\User\AudioLearningController;
use App\Http\Controllers\Admin\CourseController as AdminCourseController;
use App\Http\Controllers\Admin\CourseAssignmentController;
use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\VideoController;
use App\Http\Controllers\Admin\QuizController as AdminQuizController;
use App\Http\Controllers\Admin\QuizQuestionController;
use App\Http\Controllers\Admin\QuizAttemptController;
use App\Http\Controllers\Admin\QuizAnswerController;
use App\Http\Controllers\Admin\QuizAssignmentController;
use App\Http\Controllers\User\CourseController as UserCourseController;
use App\Http\Controllers\User\ClockingController;
use App\Http\Controllers\User\QuizController as UserQuizController;
use App\Http\Controllers\Admin\OnlineCourse\OnlineCourseController;
use App\Http\Controllers\Admin\OnlineCourse\OnlineCourseAssignmentController;
use App\Http\Controllers\Admin\VideoCategoryController;
use App\Http\Controllers\Admin\EvaluationConfigController;
use App\Http\Controllers\Admin\EvaluationTypeController;
use App\Http\Controllers\Admin\EvaluationController;
use App\Http\Controllers\Admin\EvaluationHistoryController;
use App\Http\Controllers\Admin\EvaluationNotificationController;
use App\Http\Controllers\User\UserEvaluationController;
use App\Http\Controllers\Admin\FeedbackController as AdminFeedbackController;
use App\Http\Controllers\Admin\BugReportController;
use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\User\FeedbackController as UserFeedbackController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
Route::post('/login', [UnifiedAuthController::class, 'login'])->name('login');
Route::middleware('auth:sanctum')->post('/logout', [UnifiedAuthController::class, 'logout'])->name('logout');

Route::prefix('admin')->group(function () {
    // Protected admin routes (require authentication)
    Route::middleware(['auth:sanctum', 'admin'])->group(function () {
        Route::get('/me', [AuthController::class, 'me'])->name('admin.me');

        // Department routes
        Route::prefix('departments')->group(function () {
            Route::get('/getAll', [DepartmentController::class, 'getAll'])->name('admin.departments.getAll');
            Route::post('/create', [DepartmentController::class, 'create'])->name('admin.departments.create');
            Route::put('/update/{id}', [DepartmentController::class, 'update'])->name('admin.departments.update');
            Route::delete('/delete/{id}', [DepartmentController::class, 'delete'])->name('admin.departments.delete');
        });

        // User routes
        Route::prefix('users')->group(function () {
            Route::get('/getAll', [UserController::class, 'getAll'])->name('admin.users.getAll');
            Route::post('/create', [UserController::class, 'create'])->name('admin.users.create');
            Route::put('/update/{id}', [UserController::class, 'update'])->name('admin.users.update');
            Route::delete('/delete/{id}', [UserController::class, 'delete'])->name('admin.users.delete');
        });

        // Audio category routes
        Route::prefix('audio-categories')->group(function () {
            Route::get('/getAll', [AudioCategoryController::class, 'getAll'])->name('admin.audio-categories.getAll');
            Route::post('/create', [AudioCategoryController::class, 'create'])->name('admin.audio-categories.create');
            Route::put('/update/{id}', [AudioCategoryController::class, 'update'])->name('admin.audio-categories.update');
            Route::delete('/delete/{id}', [AudioCategoryController::class, 'delete'])->name('admin.audio-categories.delete');
        });

        // Audio content routes
        Route::prefix('audio')->group(function () {
            Route::get('/getAll', [AudioController::class, 'getAll'])->name('admin.audio.getAll');
            Route::post('/create', [AudioController::class, 'create'])->name('admin.audio.create');
            Route::get('/getById/{id}', [AudioController::class, 'getById'])->name('admin.audio.getById');
            Route::get('/stream/{id}', [AudioController::class, 'stream'])->name('admin.audio.stream');
            Route::put('/update/{id}', [AudioController::class, 'update'])->name('admin.audio.update');
            Route::delete('/delete/{id}', [AudioController::class, 'delete'])->name('admin.audio.delete');
        });

        // Audio assignment routes
        Route::prefix('audio-assignments')->group(function () {
            Route::get('/getAll', [AudioAssignmentController::class, 'getAll'])->name('admin.audio-assignments.getAll');
            Route::post('/create', [AudioAssignmentController::class, 'create'])->name('admin.audio-assignments.create');
            Route::delete('/delete/{id}', [AudioAssignmentController::class, 'delete'])->name('admin.audio-assignments.delete');
        });

        // Course routes
        Route::prefix('courses')->group(function () {
            Route::get('/getAll', [AdminCourseController::class, 'getAll'])->name('admin.courses.getAll');
            Route::post('/create', [AdminCourseController::class, 'create'])->name('admin.courses.create');
            Route::get('/getById/{id}', [AdminCourseController::class, 'getById'])->name('admin.courses.getById');
            Route::put('/update/{id}', [AdminCourseController::class, 'update'])->name('admin.courses.update');
            Route::delete('/delete/{id}', [AdminCourseController::class, 'delete'])->name('admin.courses.delete');
        });
         // Video category routes
        Route::prefix('video-categories')->group(function () {
            Route::get('/getAll',        [VideoCategoryController::class, 'getAll'])  ->name('admin.video-categories.getAll');
            Route::post('/create',       [VideoCategoryController::class, 'create'])  ->name('admin.video-categories.create');
            Route::get('/getById/{id}',  [VideoCategoryController::class, 'getById'])->name('admin.video-categories.getById');
            Route::put('/update/{id}',   [VideoCategoryController::class, 'update'])  ->name('admin.video-categories.update');
            Route::delete('/delete/{id}',[VideoCategoryController::class, 'delete'])  ->name('admin.video-categories.delete');
        });

        // Video routes
        Route::prefix('videos')->group(function () {
            Route::get('/getAll', [VideoController::class, 'getAll'])->name('admin.videos.getAll');
            Route::post('/create', [VideoController::class, 'create'])->name('admin.videos.create');
            Route::get('/getById/{id}', [VideoController::class, 'getById'])->name('admin.videos.getById');
            Route::put('/update/{id}', [VideoController::class, 'update'])->name('admin.videos.update');
            Route::delete('/delete/{id}', [VideoController::class, 'delete'])->name('admin.videos.delete');
            Route::post('/upload-chunk', [VideoController::class, 'uploadChunk'])->name('admin.videos.upload-chunk');
            Route::delete('/upload-chunk/revert', [VideoController::class, 'revertChunk'])->name('admin.videos.upload-chunk.revert');
            Route::post('/{id}/retry-transcode', [VideoController::class, 'retryTranscode'])->name('admin.videos.retry-transcode');
            Route::get('/{id}/subtitle', [VideoController::class, 'getSubtitle'])->name('admin.videos.subtitle.get');
            Route::post('/{id}/subtitle', [VideoController::class, 'uploadSubtitle'])->name('admin.videos.subtitle.upload');
            Route::delete('/{id}/subtitle', [VideoController::class, 'deleteSubtitle'])->name('admin.videos.subtitle.delete');
        });

        // Course assignment routes
        Route::prefix('course-assignments')->group(function () {
            Route::get('/getAll', [CourseAssignmentController::class, 'getAll'])->name('admin.course-assignments.getAll');
            Route::post('/create', [CourseAssignmentController::class, 'create'])->name('admin.course-assignments.create');
            Route::delete('/delete/{id}', [CourseAssignmentController::class, 'delete'])->name('admin.course-assignments.delete');
        });

        // Attendance (clocking) routes
        Route::prefix('attendance')->group(function () {
            Route::get('/getAll', [AttendanceController::class, 'getAll'])->name('admin.attendance.getAll');
            Route::put('/update/{id}', [AttendanceController::class, 'update'])->name('admin.attendance.update');
            Route::delete('/delete/{id}', [AttendanceController::class, 'delete'])->name('admin.attendance.delete');
        });

        // Quiz routes
        Route::prefix('quizzes')->group(function () {
            Route::get('/getAll', [AdminQuizController::class, 'getAll'])->name('admin.quizzes.getAll');
            Route::post('/create', [AdminQuizController::class, 'create'])->name('admin.quizzes.create');
            Route::get('/getById/{id}', [AdminQuizController::class, 'getById'])->name('admin.quizzes.getById');
            Route::put('/update/{id}', [AdminQuizController::class, 'update'])->name('admin.quizzes.update');
            Route::delete('/delete/{id}', [AdminQuizController::class, 'delete'])->name('admin.quizzes.delete');

            // Quiz question routes (nested under quiz)
            Route::prefix('{quizId}/questions')->group(function () {
                Route::post('/create', [QuizQuestionController::class, 'create'])->name('admin.quiz-questions.create');
                Route::put('/update/{questionId}', [QuizQuestionController::class, 'update'])->name('admin.quiz-questions.update');
                Route::delete('/delete/{questionId}', [QuizQuestionController::class, 'delete'])->name('admin.quiz-questions.delete');
            });

            // Quiz attempt routes (nested under quiz)
            Route::prefix('{quizId}/attempts')->group(function () {
                Route::get('/getAll', [QuizAttemptController::class, 'getAll'])->name('admin.quiz-attempts.getAll');
                Route::get('/getById/{attemptId}', [QuizAttemptController::class, 'getById'])->name('admin.quiz-attempts.getById');
            });
        });

        // Quiz answer manual grading
        Route::prefix('quiz-answers')->group(function () {
            Route::post('/grade/{answerId}', [QuizAnswerController::class, 'grade'])->name('admin.quiz-answers.grade');
        });

        // Quiz assignment routes
        Route::prefix('quiz-assignments')->group(function () {
            Route::get('/getAll', [QuizAssignmentController::class, 'getAll'])->name('admin.quiz-assignments.getAll');
            Route::post('/create', [QuizAssignmentController::class, 'create'])->name('admin.quiz-assignments.create');
            Route::delete('/delete/{id}', [QuizAssignmentController::class, 'delete'])->name('admin.quiz-assignments.delete');
        });

        // Online course routes — all course, module, and content management
        Route::prefix('online-courses')->group(function () {
            Route::get('/getAll',          [OnlineCourseController::class, 'getAll'])         ->name('admin.online-courses.getAll');
            Route::post('/create',         [OnlineCourseController::class, 'create'])         ->name('admin.online-courses.create');
            Route::get('/getById/{id}',    [OnlineCourseController::class, 'getById'])        ->name('admin.online-courses.getById');
            Route::put('/update/{id}',     [OnlineCourseController::class, 'update'])         ->name('admin.online-courses.update');
            Route::delete('/delete/{id}',  [OnlineCourseController::class, 'delete'])         ->name('admin.online-courses.delete');
            Route::post('/upload-pdf',     [OnlineCourseController::class, 'uploadPdf'])      ->name('admin.online-courses.upload-pdf');
            Route::put('/modules/reorder', [OnlineCourseController::class, 'reorderModules']) ->name('admin.online-courses.modules.reorder');
        });

        // Online course assignment routes
        Route::prefix('online-course-assignments')->group(function () {
            Route::get('/getAll',         [OnlineCourseAssignmentController::class, 'getAll'])  ->name('admin.online-course-assignments.getAll');
            Route::post('/create',        [OnlineCourseAssignmentController::class, 'create'])  ->name('admin.online-course-assignments.create');
            Route::delete('/delete/{id}', [OnlineCourseAssignmentController::class, 'delete'])  ->name('admin.online-course-assignments.delete');
        });

        // Evaluation config routes
        Route::prefix('evaluation-configs')->group(function () {
            Route::get('/getAll',          [EvaluationConfigController::class, 'getAll'])      ->name('admin.evaluation-configs.getAll');
            Route::post('/create',         [EvaluationConfigController::class, 'create'])      ->name('admin.evaluation-configs.create');
            Route::put('/update/{id}',     [EvaluationConfigController::class, 'update'])      ->name('admin.evaluation-configs.update');
            Route::delete('/delete/{id}',  [EvaluationConfigController::class, 'delete'])      ->name('admin.evaluation-configs.delete');
            Route::post('/{id}/types/create', [EvaluationConfigController::class, 'createType'])->name('admin.evaluation-configs.types.create');
        });

        // Evaluation type routes
        Route::prefix('evaluation-types')->group(function () {
            Route::put('/update/{id}',    [EvaluationTypeController::class, 'update'])->name('admin.evaluation-types.update');
            Route::delete('/delete/{id}', [EvaluationTypeController::class, 'delete'])->name('admin.evaluation-types.delete');
        });

        // Evaluation routes (regular + online unified)
        Route::prefix('evaluations')->group(function () {
            Route::get('/getAll',           [EvaluationController::class, 'getAll'])      ->name('admin.evaluations.getAll');
            Route::get('/getById/{id}',     [EvaluationController::class, 'getById'])     ->name('admin.evaluations.getById');
            Route::post('/create',          [EvaluationController::class, 'create'])      ->name('admin.evaluations.create');
            Route::post('/bulk-create',     [EvaluationController::class, 'bulkCreate'])  ->name('admin.evaluations.bulk-create');
            Route::put('/update/{id}',      [EvaluationController::class, 'update'])      ->name('admin.evaluations.update');
            Route::delete('/delete/{id}',   [EvaluationController::class, 'delete'])      ->name('admin.evaluations.delete');
            Route::get('/users',            [EvaluationController::class, 'users'])       ->name('admin.evaluations.users');
            Route::get('/user-courses',     [EvaluationController::class, 'userCourses']) ->name('admin.evaluations.user-courses');
        });

        // Evaluation history routes
        Route::prefix('evaluation-history')->group(function () {
            Route::get('/getAll',          [EvaluationHistoryController::class, 'getAll'])       ->name('admin.evaluation-history.getAll');
            Route::get('/getById/{id}',    [EvaluationHistoryController::class, 'getById'])      ->name('admin.evaluation-history.getById');
            Route::get('/analytics',       [EvaluationHistoryController::class, 'analytics'])    ->name('admin.evaluation-history.analytics');
            Route::get('/export',          [EvaluationHistoryController::class, 'export'])       ->name('admin.evaluation-history.export');
            Route::get('/export-summary',  [EvaluationHistoryController::class, 'exportSummary'])->name('admin.evaluation-history.export-summary');
        });

        // Evaluation notification routes
        Route::prefix('evaluation-notifications')->group(function () {
            Route::get('/getAll',  [EvaluationNotificationController::class, 'getAll']) ->name('admin.evaluation-notifications.getAll');
            Route::post('/preview',[EvaluationNotificationController::class, 'preview'])->name('admin.evaluation-notifications.preview');
            Route::post('/send',   [EvaluationNotificationController::class, 'send'])   ->name('admin.evaluation-notifications.send');
        });

        // Feedback routes
        Route::prefix('feedback')->group(function () {
            Route::get('/getAll',       [AdminFeedbackController::class, 'getAll'])  ->name('admin.feedback.getAll');
            Route::get('/getById/{id}', [AdminFeedbackController::class, 'getById']) ->name('admin.feedback.getById');
            Route::put('/respond/{id}', [AdminFeedbackController::class, 'respond']) ->name('admin.feedback.respond');
            Route::put('/status/{id}',  [AdminFeedbackController::class, 'status'])  ->name('admin.feedback.status');
        });

        // Bug report routes
        Route::prefix('bug-reports')->group(function () {
            Route::get('/getAll',         [BugReportController::class, 'getAll'])  ->name('admin.bug-reports.getAll');
            Route::get('/getById/{id}',   [BugReportController::class, 'getById']) ->name('admin.bug-reports.getById');
            Route::post('/create',        [BugReportController::class, 'create'])  ->name('admin.bug-reports.create');
            Route::put('/update/{id}',    [BugReportController::class, 'update'])  ->name('admin.bug-reports.update');
            Route::put('/assign/{id}',    [BugReportController::class, 'assign'])  ->name('admin.bug-reports.assign');
            Route::put('/resolve/{id}',   [BugReportController::class, 'resolve']) ->name('admin.bug-reports.resolve');
            Route::delete('/delete/{id}', [BugReportController::class, 'delete'])  ->name('admin.bug-reports.delete');
        });

        // Activity log routes
        Route::prefix('activity-logs')->group(function () {
            Route::get('/getAll',        [ActivityLogController::class, 'getAll']) ->name('admin.activity-logs.getAll');
            Route::get('/user/{userId}', [ActivityLogController::class, 'user'])   ->name('admin.activity-logs.user');
        });
    });
});

Route::prefix('user')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [UserAuthController::class, 'me'])->name('user.me');

        Route::prefix('audio')->group(function () {
            Route::get('/getAll', [AudioLearningController::class, 'getAll'])->name('user.audio.getAll');
            Route::get('/getById/{id}', [AudioLearningController::class, 'getById'])->name('user.audio.getById');
            Route::get('/stream/{id}', [AudioLearningController::class, 'stream'])->name('user.audio.stream');
            Route::post('/progress/update/{audioId}', [AudioLearningController::class, 'updateProgress'])->name('user.audio.progress.update');
        });

        // Course routes
        Route::prefix('courses')->group(function () {
            Route::get('/getAll', [UserCourseController::class, 'getAll'])->name('user.courses.getAll');
            Route::get('/getById/{id}', [UserCourseController::class, 'getById'])->name('user.courses.getById');
            Route::post('/enroll/{courseId}', [UserCourseController::class, 'enroll'])->name('user.courses.enroll');
            Route::post('/complete/{courseId}', [UserCourseController::class, 'complete'])->name('user.courses.complete');
            Route::post('/submitRating/{courseId}', [UserCourseController::class, 'submitRating'])->name('user.courses.submitRating');
            Route::get('/my-enrollments', [UserCourseController::class, 'myEnrollments'])->name('user.courses.myEnrollments');
        });

        // Clocking routes
        Route::prefix('clocking')->group(function () {
            Route::post('/clockIn', [ClockingController::class, 'clockIn'])->name('user.clocking.clockIn');
            Route::post('/clockOut', [ClockingController::class, 'clockOut'])->name('user.clocking.clockOut');
            Route::get('/history', [ClockingController::class, 'history'])->name('user.clocking.history');
            Route::get('/active', [ClockingController::class, 'active'])->name('user.clocking.active');
        });

        // Quiz routes
        Route::prefix('quizzes')->group(function () {
            Route::get('/getAll', [UserQuizController::class, 'getAll'])->name('user.quizzes.getAll');
            Route::get('/getById/{id}', [UserQuizController::class, 'getById'])->name('user.quizzes.getById');
            Route::post('/{id}/start', [UserQuizController::class, 'start'])->name('user.quizzes.start');
            Route::post('/{id}/submit/{attemptId}', [UserQuizController::class, 'submit'])->name('user.quizzes.submit');
            Route::get('/{id}/result/{attemptId}', [UserQuizController::class, 'result'])->name('user.quizzes.result');
        });

        // User evaluation routes
        Route::prefix('evaluations')->group(function () {
            Route::get('/getAll',       [UserEvaluationController::class, 'getAll'])  ->name('user.evaluations.getAll');
            Route::get('/getById/{id}', [UserEvaluationController::class, 'getById']) ->name('user.evaluations.getById');
        });

        // User feedback routes
        Route::prefix('feedback')->group(function () {
            Route::get('/getAll',       [UserFeedbackController::class, 'getAll'])  ->name('user.feedback.getAll');
            Route::post('/create',      [UserFeedbackController::class, 'create'])  ->name('user.feedback.create');
            Route::get('/getById/{id}', [UserFeedbackController::class, 'getById']) ->name('user.feedback.getById');
        });
    });
});
