<?php

use App\Http\Controllers\AuthController as UnifiedAuthController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\AudioAssignmentController;
use App\Http\Controllers\Admin\AudioCategoryController;
use App\Http\Controllers\Admin\AudioController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\User\AuthController as UserAuthController;
use App\Http\Controllers\User\AudioLearningController;
use App\Http\Controllers\User\UserDashboardController;
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
use App\Http\Controllers\Admin\UserLevelController;
use App\Http\Controllers\User\FeedbackController as UserFeedbackController;
use App\Http\Controllers\Admin\TranscodeCallbackController;
use App\Http\Controllers\MediaStreamController;
use App\Http\Controllers\User\UserOnlineCourseController;
use App\Http\Controllers\User\LearningSessionController;
use App\Http\Controllers\User\ContentProgressController;
use App\Http\Controllers\Admin\BlogPostController;
use App\Http\Controllers\BlogFeedController;
use App\Http\Controllers\BlogCommentController;
use App\Http\Controllers\BlogLikeController;
use App\Http\Controllers\Api\Admin\Reporting\ReportingKpiController;
use App\Http\Controllers\Api\Admin\Reporting\ReportingDatasetController;
use App\Http\Controllers\Api\Admin\Reporting\ReportingExportController;
use App\Http\Controllers\Api\Admin\Reporting\ReportingRefreshController;
use App\Http\Controllers\Api\Admin\Reporting\LiveCourseReportController;
use App\Http\Controllers\Api\Admin\Reporting\QuizReportController;
use App\Http\Controllers\Api\Admin\Reporting\UserPerformanceReportController;
use App\Http\Controllers\Api\Admin\Reporting\EvaluationReportController;
use App\Http\Controllers\Api\Admin\Reporting\ReportingExtraExportController;
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

// Transcode webhook — no auth required (called by external transcoding service)
Route::post('/transcode/callback', [TranscodeCallbackController::class, 'handle'])->name('transcode.callback');

// Media streaming routes — signed, no auth middleware required
Route::get('/media/video/{content_id}', [MediaStreamController::class, 'streamVideo'])
    ->name('media.video')->middleware('signed');
Route::get('/media/video-quality/{quality_id}', [MediaStreamController::class, 'streamVideoQuality'])
    ->name('media.video-quality')->middleware('signed');
Route::get('/media/pdf/{content_id}', [MediaStreamController::class, 'streamPdf'])
    ->name('media.pdf')->middleware('signed');
Route::get('/media/blog-video/{video_id}', [MediaStreamController::class, 'streamBlogVideo'])
    ->name('media.blog-video')->middleware('signed');
Route::get('/media/blog-audio/{audio_id}', [MediaStreamController::class, 'streamBlogAudio'])
    ->name('media.blog-audio')->middleware('signed');

Route::prefix('admin')->group(function () {
    // Protected admin routes (require authentication)
    Route::middleware(['auth:sanctum', 'admin'])->group(function () {
        Route::get('/me', [AuthController::class, 'me'])->name('admin.me');

        // Admin dashboard overview
        Route::get('/dashboard', AdminDashboardController::class)->name('admin.dashboard');

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
            Route::get('/getById/{id}', [UserController::class, 'getById'])->name('admin.users.getById');
            Route::post('/create', [UserController::class, 'create'])->name('admin.users.create');
            Route::put('/update/{id}', [UserController::class, 'update'])->name('admin.users.update');
            Route::delete('/delete/{id}', [UserController::class, 'delete'])->name('admin.users.delete');
        });

        // User level routes
        Route::prefix('user-levels')->group(function () {
            Route::get('/with-tiers', [UserLevelController::class, 'withTiers'])->name('admin.user-levels.with-tiers');
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
            Route::get('/{id}/stream', [VideoController::class, 'stream'])->name('admin.videos.stream');
            Route::get('/{id}/subtitle', [VideoController::class, 'getSubtitle'])->name('admin.videos.subtitle.get');
            Route::get('/{id}/subtitle/raw', [VideoController::class, 'streamSubtitle'])->name('admin.videos.subtitle.raw');
            Route::post('/{id}/subtitle', [VideoController::class, 'uploadSubtitle'])->name('admin.videos.subtitle.upload');
            Route::put('/{id}/subtitle', [VideoController::class, 'updateSubtitle'])->name('admin.videos.subtitle.update');
            Route::delete('/{id}/subtitle', [VideoController::class, 'deleteSubtitle'])->name('admin.videos.subtitle.delete');
        });

        // Course assignment routes
        Route::prefix('course-assignments')->group(function () {
            Route::get('/getAll', [CourseAssignmentController::class, 'getAll'])->name('admin.course-assignments.getAll');
            Route::get('/expired-links', [CourseAssignmentController::class, 'expiredLinks'])->name('admin.course-assignments.expired-links');
            Route::post('/create', [CourseAssignmentController::class, 'create'])->name('admin.course-assignments.create');
            Route::post('/{id}/resend-link', [CourseAssignmentController::class, 'resendLink'])->name('admin.course-assignments.resend-link');
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
                Route::delete('/grant-retry', [QuizAttemptController::class, 'grantRetry'])->name('admin.quiz-attempts.grant-retry');
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
            Route::put('/modules/reorder', [OnlineCourseController::class, 'reorderModules']) ->name('admin.online-courses.modules.reorder');
            Route::get('/{id}/enrollments', [OnlineCourseController::class, 'enrollments'])   ->name('admin.online-courses.enrollments');
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

        // Blog post routes
        Route::prefix('blog-posts')->group(function () {
            Route::get('/getAll',              [BlogPostController::class, 'getAll'])         ->name('admin.blog-posts.getAll');
            Route::post('/create',             [BlogPostController::class, 'create'])         ->name('admin.blog-posts.create');
            Route::get('/available-videos',    [BlogPostController::class, 'availableVideos'])->name('admin.blog-posts.available-videos');
            Route::get('/available-audios',    [BlogPostController::class, 'availableAudios'])->name('admin.blog-posts.available-audios');
            Route::get('/getById/{id}',        [BlogPostController::class, 'getById'])        ->name('admin.blog-posts.getById');
            Route::put('/update/{id}',         [BlogPostController::class, 'update'])         ->name('admin.blog-posts.update');
            Route::delete('/delete/{id}',      [BlogPostController::class, 'delete'])         ->name('admin.blog-posts.delete');
        });

        // Reporting routes (Phase 7)
        Route::prefix('reporting')->group(function () {
            // KPI
            Route::get('/kpi/overview',  [ReportingKpiController::class, 'overview']) ->name('admin.reporting.kpi.overview');
            Route::get('/kpi/trends',    [ReportingKpiController::class, 'trends'])   ->name('admin.reporting.kpi.trends');

            // Datasets
            Route::get('/datasets/user-course-daily',    [ReportingDatasetController::class, 'userCourseDaily'])    ->name('admin.reporting.datasets.user-course-daily');
            Route::get('/datasets/department-course-daily', [ReportingDatasetController::class, 'departmentCourseDaily'])->name('admin.reporting.datasets.department-course-daily');
            Route::get('/datasets/session-fact',         [ReportingDatasetController::class, 'sessionFact'])        ->name('admin.reporting.datasets.session-fact');

            // Exports
            Route::get('/export/user-course-daily',       [ReportingExportController::class, 'userCourseDaily'])       ->name('admin.reporting.export.user-course-daily');
            Route::get('/export/department-course-daily', [ReportingExportController::class, 'departmentCourseDaily']) ->name('admin.reporting.export.department-course-daily');
            Route::get('/export/session-fact',            [ReportingExportController::class, 'sessionFact'])           ->name('admin.reporting.export.session-fact');
            Route::get('/export/kpi-overview',            [ReportingExportController::class, 'kpiOverview'])           ->name('admin.reporting.export.kpi-overview');

            // Refresh (ETL triggers)
            Route::post('/refresh/daily',  [ReportingRefreshController::class, 'daily']) ->name('admin.reporting.refresh.daily');
            Route::post('/refresh/range',  [ReportingRefreshController::class, 'range']) ->name('admin.reporting.refresh.range');
            Route::post('/refresh/full',   [ReportingRefreshController::class, 'full'])  ->name('admin.reporting.refresh.full');
            Route::get('/refresh/log',     [ReportingRefreshController::class, 'log'])   ->name('admin.reporting.refresh.log');

            // ----------------------------------------------------------------
            // Live / traditional course reports (registrations, attendance, completion)
            // ----------------------------------------------------------------
            Route::get('/live/course-registrations', [LiveCourseReportController::class, 'courseRegistrations'])->name('admin.reporting.live.course-registrations');
            Route::get('/live/attendance',           [LiveCourseReportController::class, 'attendance'])         ->name('admin.reporting.live.attendance');
            Route::get('/live/course-completion',    [LiveCourseReportController::class, 'courseCompletion'])   ->name('admin.reporting.live.course-completion');

            // Quiz reports
            Route::get('/quiz/attempts', [QuizReportController::class, 'attempts'])->name('admin.reporting.quiz.attempts');

            // User performance & compliance
            Route::get('/user-performance',     [UserPerformanceReportController::class, 'performance'])  ->name('admin.reporting.user-performance');
            Route::get('/user-course-progress', [UserPerformanceReportController::class, 'courseProgress'])->name('admin.reporting.user-course-progress');

            // Evaluation-based department performance
            Route::get('/evaluation/department-performance', [EvaluationReportController::class, 'departmentPerformance'])->name('admin.reporting.evaluation.department-performance');

            // CSV exports for the reports above
            Route::get('/export/live/course-registrations', [ReportingExtraExportController::class, 'courseRegistrations'])->name('admin.reporting.export.live.course-registrations');
            Route::get('/export/live/attendance',           [ReportingExtraExportController::class, 'attendance'])         ->name('admin.reporting.export.live.attendance');
            Route::get('/export/live/course-completion',    [ReportingExtraExportController::class, 'courseCompletion'])   ->name('admin.reporting.export.live.course-completion');
            Route::get('/export/quiz/attempts',             [ReportingExtraExportController::class, 'quizAttempts'])       ->name('admin.reporting.export.quiz.attempts');
            Route::get('/export/quiz/detailed',             [ReportingExtraExportController::class, 'quizDetailed'])       ->name('admin.reporting.export.quiz.detailed');
            Route::get('/export/user-performance',          [ReportingExtraExportController::class, 'userPerformance'])    ->name('admin.reporting.export.user-performance');
            Route::get('/export/user-course-progress',      [ReportingExtraExportController::class, 'userCourseProgress']) ->name('admin.reporting.export.user-course-progress');
            Route::get('/export/evaluation/department-performance', [ReportingExtraExportController::class, 'evaluationDepartment'])->name('admin.reporting.export.evaluation.department-performance');
        });
    });
});

Route::prefix('user')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [UserAuthController::class, 'me'])->name('user.me');

        // User dashboard overview
        Route::get('/dashboard', UserDashboardController::class)->name('user.dashboard');

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

        // User Online Courses (Phase 6)
        Route::prefix('online-courses')->group(function () {
            // Course navigation
            Route::get('/getAll',                         [UserOnlineCourseController::class, 'index'])   ->name('user.online-courses.getAll');
            Route::get('/getById/{id}',                   [UserOnlineCourseController::class, 'show'])    ->name('user.online-courses.getById');
            Route::get('/{courseId}/content/{contentId}',            [UserOnlineCourseController::class, 'content'])            ->name('user.online-courses.content');
            Route::get('/{courseId}/content/{contentId}/attachment', [UserOnlineCourseController::class, 'downloadAttachment'])->name('user.online-courses.content.attachment');
            Route::get('/progress/{contentId}/resume',    [UserOnlineCourseController::class, 'resume'])  ->name('user.online-courses.resume');

            // Session tracking
            Route::post('/sessions/start',                [LearningSessionController::class, 'start'])   ->name('user.online-courses.sessions.start');
            Route::post('/sessions/{sessionId}/progress', [LearningSessionController::class, 'progress'])->name('user.online-courses.sessions.progress');
            Route::post('/sessions/{sessionId}/end',      [LearningSessionController::class, 'end'])     ->name('user.online-courses.sessions.end');

            // PDF progress
            Route::post('/progress/pdf',                  [ContentProgressController::class, 'updatePdf'])->name('user.online-courses.progress.pdf');
        });

        // Blog routes
        Route::prefix('blog-posts')->group(function () {
            Route::get('/getAll',                [BlogFeedController::class,    'index'])   ->name('user.blog-posts.getAll');
            Route::get('/getBySlug/{slug}',      [BlogFeedController::class,    'show'])    ->name('user.blog-posts.getBySlug');
            Route::post('/like/{postId}',        [BlogLikeController::class,    'like'])    ->name('user.blog-posts.like');
            Route::delete('/unlike/{postId}',    [BlogLikeController::class,    'unlike'])  ->name('user.blog-posts.unlike');
            Route::post('/comment/{postId}',     [BlogCommentController::class, 'store'])   ->name('user.blog-posts.comment.store');
        });

        Route::prefix('blog-comments')->group(function () {
            Route::delete('/delete/{commentId}', [BlogCommentController::class, 'destroy'])->name('user.blog-comments.delete');
        });
    });
});
