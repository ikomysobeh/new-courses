# Backend API & Architecture Reference — `new-courses`

This file exists so any AI (or new developer) can get oriented fast without
reading the whole codebase. It covers what the project is, how auth works,
what each domain does, and a full list of every API endpoint. Generated from
the actual running route table (`php artisan route:list`), not hand-typed —
if it drifts from the code, regenerate it the same way rather than trusting
this file blindly for exact behavior; for precise request/response shapes,
the live Scramble docs (below) are authoritative.

## What this is

A Laravel API backend for a corporate LMS (Learning Management System):
traditional (scheduled/in-person) courses, self-paced online courses with
video/pdf content and per-content progress tracking, quizzes, employee
performance evaluations, KPI/reporting dashboards, plus smaller features
(blog/podcast, feedback, bug tracking, attendance clocking).

This is a **schema-and-architecture redesign** of an older sibling project
(`nvt-courses`, DB name `cours`). If you're comparing the two or migrating
data between them, see `doc/LEGACY_DATA_MIGRATION_GUIDE.md` and
`doc/LEGACY_DATA_MIGRATION_PLAN.md` — they document every old-vs-new schema
difference found while migrating real production data across, table by
table, which is a faster way to understand *why* the new schema looks the
way it does than reading migrations cold.

## Stack

- **Laravel 13**, **PHP 8.3**, MySQL
- **`laravel/sanctum`** — token-based API auth (no sessions/cookies for the API)
- **`dedoc/scramble`** — auto-generates OpenAPI 3.1 docs from the actual
  routes/controllers/requests (see "Live API docs" below)
- **`phpoffice/phpspreadsheet`** — Excel exports (reporting)

## Live, authoritative API docs

Don't rely solely on this file for exact request/response schemas — it's a
map, not a contract. The real, always-current source is:

- `GET /docs/api` — interactive docs (Scalar UI), generated from the live
  code, with "Try it" support
- `GET /docs/api.json` — the raw OpenAPI 3.1 spec (or regenerate to
  `public/api.json` via `php artisan scramble:export --path=public/api.json`
  for a file you can hand to a tool or another AI directly)
- See `doc/HOW_SCRAMBLE_WORKS.md` for more on this

## Auth model

- **One unified login endpoint** for both regular users and admins:
  `POST /api/login` (email + password) → Sanctum bearer token + user payload.
  `POST /api/logout` revokes it.
- Every other endpoint requires `Authorization: Bearer <token>`, **except**
  the `api/media/*` streaming endpoints, which use Laravel's signed-URL
  validation instead (`ValidateSignature` middleware) — these are meant to be
  dropped straight into `<video src>`/`<audio src>`/iframe tags without
  exposing a bearer token in markup.
- Two authorization tiers, enforced by middleware, matching the two URL
  prefixes:
  - **`api/user/*`** — any authenticated user (`auth:sanctum`)
  - **`api/admin/*`** — authenticated **and** `EnsureUserIsAdmin` (the
    user's `role` column must be `admin`) — this is the management/back-office API
- `api/transcode/callback` is a webhook (video transcoding service calling
  back into the app) — no user auth, presumably secured some other way
  (check `TranscodeCallbackController` before assuming it's open).

## Domain map

One-liner per area, so you can jump straight to what you need instead of
reading all ~210 endpoints:

| Domain | What it covers | Admin routes | User routes |
|---|---|---|---|
| **Users & Departments** | Accounts, department hierarchy, user levels/tiers | `admin/users`, `admin/departments`, `admin/user-levels` | `user/me` |
| **Traditional courses** | Scheduled/in-person courses, assignments, registrations, completions | `admin/courses`, `admin/course-assignments` | `user/courses` |
| **Attendance / clocking** | Clock in/out for traditional course sessions | `admin/attendance` | `user/clocking` |
| **Online courses** | Self-paced e-learning: modules, content (video/pdf), assignments, per-user progress, learning sessions with engagement/attention tracking | `admin/online-courses`, `admin/online-course-assignments` | `user/online-courses` |
| **Video & audio library** | Media assets, categories, multi-quality transcoded renditions, chunked upload, subtitles, streaming | `admin/videos`, `admin/video-categories`, `admin/audio`, `admin/audio-categories`, `admin/audio-assignments` | `user/audio`, `api/media/*` (streaming) |
| **Quizzes** | Standalone or module-embedded quizzes, questions, assignments, attempts, manual grading | `admin/quizzes`, `admin/quiz-assignments`, `admin/quiz-answers` | `user/quizzes` |
| **Evaluations** | Performance evaluation configs/types, per-user evaluations with a computed performance level, notification emails to managers | `admin/evaluations`, `admin/evaluation-configs`, `admin/evaluation-types`, `admin/evaluation-history`, `admin/evaluation-notifications` | `user/evaluations` |
| **Reporting / KPI** | The largest admin surface — live + cached (refreshable) reporting datasets, KPI overview/trends/monthly comparisons, CSV/Excel exports | `admin/reporting/*` (33 routes) | — |
| **Blog / podcast** | Podcast-style posts (with a polymorphic video-or-audio attachment), comments, likes | `admin/blog-posts` | `user/blog-posts`, `user/blog-comments` |
| **Feedback & bugs** | Employee suggestions/feedback and an internal bug tracker | `admin/feedback`, `admin/bug-reports` | `user/feedback` |
| **Dashboard** | Summary/landing-page data | `admin/dashboard` | `user/dashboard` |
| **Activity logs** | Audit trail of user actions | `admin/activity-logs` | — |

## Full endpoint reference

All paths below are relative to `/api` unless noted otherwise (e.g. `login`
means `POST /api/login`). **Auth** column: `Admin` = `auth:sanctum` +
`EnsureUserIsAdmin`; `User` = `auth:sanctum` only; `Signed` = signed-URL
validation, no bearer token; `Public` = no auth at all.

### Auth

| Method | Path | Auth |
|---|---|---|
| POST | `login` | Public |
| POST | `logout` | User |

### Admin — Users, Departments, User Levels

| Method | Path | Controller@Action |
|---|---|---|
| GET | `admin/users/getAll` | `Admin\UserController@getAll` |
| GET | `admin/users/getById/{id}` | `Admin\UserController@getById` |
| POST | `admin/users/create` | `Admin\UserController@create` |
| PUT | `admin/users/update/{id}` | `Admin\UserController@update` |
| DELETE | `admin/users/delete/{id}` | `Admin\UserController@delete` |
| GET | `admin/departments/getAll` | `Admin\DepartmentController@getAll` |
| POST | `admin/departments/create` | `Admin\DepartmentController@create` |
| PUT | `admin/departments/update/{id}` | `Admin\DepartmentController@update` |
| DELETE | `admin/departments/delete/{id}` | `Admin\DepartmentController@delete` |
| GET | `admin/user-levels/with-tiers` | `Admin\UserLevelController@withTiers` |
| GET | `admin/me` | `Admin\AuthController@me` |
| GET | `admin/dashboard` | `Admin\AdminDashboardController` |
| GET | `admin/activity-logs/getAll` | `Admin\ActivityLogController@getAll` |
| GET | `admin/activity-logs/user/{userId}` | `Admin\ActivityLogController@user` |

(All rows in this table are `Admin` auth tier.)

### Admin — Traditional Courses, Assignments, Attendance

| Method | Path | Controller@Action |
|---|---|---|
| GET | `admin/courses/getAll` | `Admin\CourseController@getAll` |
| GET | `admin/courses/getById/{id}` | `Admin\CourseController@getById` |
| POST | `admin/courses/create` | `Admin\CourseController@create` |
| PUT | `admin/courses/update/{id}` | `Admin\CourseController@update` |
| DELETE | `admin/courses/delete/{id}` | `Admin\CourseController@delete` |
| GET | `admin/course-assignments/getAll` | `Admin\CourseAssignmentController@getAll` |
| POST | `admin/course-assignments/create` | `Admin\CourseAssignmentController@create` |
| DELETE | `admin/course-assignments/delete/{id}` | `Admin\CourseAssignmentController@delete` |
| GET | `admin/course-assignments/expired-links` | `Admin\CourseAssignmentController@expiredLinks` |
| POST | `admin/course-assignments/{id}/resend-link` | `Admin\CourseAssignmentController@resendLink` |
| GET | `admin/attendance/getAll` | `Admin\AttendanceController@getAll` |
| PUT | `admin/attendance/update/{id}` | `Admin\AttendanceController@update` |
| DELETE | `admin/attendance/delete/{id}` | `Admin\AttendanceController@delete` |

(All rows in this table are `Admin` auth tier.)

### Admin — Online Courses

| Method | Path | Controller@Action |
|---|---|---|
| GET | `admin/online-courses/getAll` | `OnlineCourse\OnlineCourseController@getAll` |
| GET | `admin/online-courses/getById/{id}` | `OnlineCourse\OnlineCourseController@getById` |
| POST | `admin/online-courses/create` | `OnlineCourse\OnlineCourseController@create` |
| PUT | `admin/online-courses/update/{id}` | `OnlineCourse\OnlineCourseController@update` |
| DELETE | `admin/online-courses/delete/{id}` | `OnlineCourse\OnlineCourseController@delete` |
| PUT | `admin/online-courses/modules/reorder` | `OnlineCourse\OnlineCourseController@reorderModules` |
| GET | `admin/online-courses/{id}/enrollments` | `OnlineCourse\OnlineCourseController@enrollments` |
| GET | `admin/online-course-assignments/getAll` | `OnlineCourse\OnlineCourseAssignmentController@getAll` |
| POST | `admin/online-course-assignments/create` | `OnlineCourse\OnlineCourseAssignmentController@create` |
| DELETE | `admin/online-course-assignments/delete/{id}` | `OnlineCourse\OnlineCourseAssignmentController@delete` |

(Controllers are namespaced `App\Http\Controllers\Admin\...`. All rows `Admin` auth tier.)

### Admin — Video & Audio Library

| Method | Path | Controller@Action |
|---|---|---|
| GET | `admin/videos/getAll` | `Admin\VideoController@getAll` |
| GET | `admin/videos/getById/{id}` | `Admin\VideoController@getById` |
| POST | `admin/videos/create` | `Admin\VideoController@create` |
| PUT | `admin/videos/update/{id}` | `Admin\VideoController@update` |
| DELETE | `admin/videos/delete/{id}` | `Admin\VideoController@delete` |
| POST | `admin/videos/upload-chunk` | `Admin\VideoController@uploadChunk` |
| DELETE | `admin/videos/upload-chunk/revert` | `Admin\VideoController@revertChunk` |
| POST | `admin/videos/{id}/retry-transcode` | `Admin\VideoController@retryTranscode` |
| GET | `admin/videos/{id}/stream` | `Admin\VideoController@stream` |
| GET | `admin/videos/{id}/subtitle` | `Admin\VideoController@getSubtitle` |
| POST | `admin/videos/{id}/subtitle` | `Admin\VideoController@uploadSubtitle` |
| PUT | `admin/videos/{id}/subtitle` | `Admin\VideoController@updateSubtitle` |
| DELETE | `admin/videos/{id}/subtitle` | `Admin\VideoController@deleteSubtitle` |
| GET | `admin/videos/{id}/subtitle/raw` | `Admin\VideoController@streamSubtitle` |
| GET | `admin/video-categories/getAll` | `Admin\VideoCategoryController@getAll` |
| GET | `admin/video-categories/getById/{id}` | `Admin\VideoCategoryController@getById` |
| POST | `admin/video-categories/create` | `Admin\VideoCategoryController@create` |
| PUT | `admin/video-categories/update/{id}` | `Admin\VideoCategoryController@update` |
| DELETE | `admin/video-categories/delete/{id}` | `Admin\VideoCategoryController@delete` |
| GET | `admin/audio/getAll` | `Admin\AudioController@getAll` |
| GET | `admin/audio/getById/{id}` | `Admin\AudioController@getById` |
| POST | `admin/audio/create` | `Admin\AudioController@create` |
| PUT | `admin/audio/update/{id}` | `Admin\AudioController@update` |
| DELETE | `admin/audio/delete/{id}` | `Admin\AudioController@delete` |
| GET | `admin/audio/stream/{id}` | `Admin\AudioController@stream` |
| GET | `admin/audio-categories/getAll` | `Admin\AudioCategoryController@getAll` |
| POST | `admin/audio-categories/create` | `Admin\AudioCategoryController@create` |
| PUT | `admin/audio-categories/update/{id}` | `Admin\AudioCategoryController@update` |
| DELETE | `admin/audio-categories/delete/{id}` | `Admin\AudioCategoryController@delete` |
| GET | `admin/audio-assignments/getAll` | `Admin\AudioAssignmentController@getAll` |
| POST | `admin/audio-assignments/create` | `Admin\AudioAssignmentController@create` |
| DELETE | `admin/audio-assignments/delete/{id}` | `Admin\AudioAssignmentController@delete` |

(All rows `Admin` auth tier.)

### Admin — Quizzes

| Method | Path | Controller@Action |
|---|---|---|
| GET | `admin/quizzes/getAll` | `Admin\QuizController@getAll` |
| GET | `admin/quizzes/getById/{id}` | `Admin\QuizController@getById` |
| POST | `admin/quizzes/create` | `Admin\QuizController@create` |
| PUT | `admin/quizzes/update/{id}` | `Admin\QuizController@update` |
| DELETE | `admin/quizzes/delete/{id}` | `Admin\QuizController@delete` |
| POST | `admin/quizzes/{quizId}/questions/create` | `Admin\QuizQuestionController@create` |
| PUT | `admin/quizzes/{quizId}/questions/update/{questionId}` | `Admin\QuizQuestionController@update` |
| DELETE | `admin/quizzes/{quizId}/questions/delete/{questionId}` | `Admin\QuizQuestionController@delete` |
| GET | `admin/quizzes/{quizId}/attempts/getAll` | `Admin\QuizAttemptController@getAll` |
| GET | `admin/quizzes/{quizId}/attempts/getById/{attemptId}` | `Admin\QuizAttemptController@getById` |
| DELETE | `admin/quizzes/{quizId}/attempts/grant-retry` | `Admin\QuizAttemptController@grantRetry` |
| GET | `admin/quiz-assignments/getAll` | `Admin\QuizAssignmentController@getAll` |
| POST | `admin/quiz-assignments/create` | `Admin\QuizAssignmentController@create` |
| DELETE | `admin/quiz-assignments/delete/{id}` | `Admin\QuizAssignmentController@delete` |
| POST | `admin/quiz-answers/grade/{answerId}` | `Admin\QuizAnswerController@grade` |

(All rows `Admin` auth tier. Note `grant-retry` is registered as `DELETE`, not `POST` — that's in the live route table, not a typo here.)

### Admin — Evaluations

| Method | Path | Controller@Action |
|---|---|---|
| GET | `admin/evaluations/getAll` | `Admin\EvaluationController@getAll` |
| GET | `admin/evaluations/getById/{id}` | `Admin\EvaluationController@getById` |
| POST | `admin/evaluations/create` | `Admin\EvaluationController@create` |
| POST | `admin/evaluations/bulk-create` | `Admin\EvaluationController@bulkCreate` |
| PUT | `admin/evaluations/update/{id}` | `Admin\EvaluationController@update` |
| DELETE | `admin/evaluations/delete/{id}` | `Admin\EvaluationController@delete` |
| GET | `admin/evaluations/users` | `Admin\EvaluationController@users` |
| GET | `admin/evaluations/user-courses` | `Admin\EvaluationController@userCourses` |
| GET | `admin/evaluation-configs/getAll` | `Admin\EvaluationConfigController@getAll` |
| POST | `admin/evaluation-configs/create` | `Admin\EvaluationConfigController@create` |
| PUT | `admin/evaluation-configs/update/{id}` | `Admin\EvaluationConfigController@update` |
| DELETE | `admin/evaluation-configs/delete/{id}` | `Admin\EvaluationConfigController@delete` |
| POST | `admin/evaluation-configs/{id}/types/create` | `Admin\EvaluationConfigController@createType` |
| PUT | `admin/evaluation-types/update/{id}` | `Admin\EvaluationTypeController@update` |
| DELETE | `admin/evaluation-types/delete/{id}` | `Admin\EvaluationTypeController@delete` |
| GET | `admin/evaluation-history/getAll` | `Admin\EvaluationHistoryController@getAll` |
| GET | `admin/evaluation-history/getById/{id}` | `Admin\EvaluationHistoryController@getById` |
| GET | `admin/evaluation-history/analytics` | `Admin\EvaluationHistoryController@analytics` |
| GET | `admin/evaluation-history/export` | `Admin\EvaluationHistoryController@export` |
| GET | `admin/evaluation-history/export-summary` | `Admin\EvaluationHistoryController@exportSummary` |
| GET | `admin/evaluation-notifications/getAll` | `Admin\EvaluationNotificationController@getAll` |
| POST | `admin/evaluation-notifications/preview` | `Admin\EvaluationNotificationController@preview` |
| POST | `admin/evaluation-notifications/send` | `Admin\EvaluationNotificationController@send` |

(All rows `Admin` auth tier. See `doc/LEGACY_DATA_MIGRATION_GUIDE.md`'s
evaluation-domain section for how `total_score`/`performance_level` are
computed — `App\Enums\PerformanceLevel::getLevelByScore()`.)

### Admin — Reporting / KPI (33 routes — the largest surface)

| Method | Path | Controller@Action |
|---|---|---|
| GET | `admin/reporting/kpi/overview` | `Reporting\ReportingKpiController@overview` |
| GET | `admin/reporting/kpi/trends` | `Reporting\ReportingKpiController@trends` |
| GET | `admin/reporting/kpi/monthly` | `Reporting\ReportingKpiController@monthly` |
| GET | `admin/reporting/kpi/monthly-comparison` | `Reporting\ReportingKpiController@monthlyComparison` |
| GET | `admin/reporting/user-performance` | `Reporting\UserPerformanceReportController@performance` |
| GET | `admin/reporting/user-performance/{id}` | `Reporting\UserPerformanceReportController@performanceShow` |
| GET | `admin/reporting/user-course-progress` | `Reporting\UserPerformanceReportController@courseProgress` |
| GET | `admin/reporting/quiz/attempts` | `Reporting\QuizReportController@attempts` |
| GET | `admin/reporting/evaluation/department-performance` | `Reporting\EvaluationReportController@departmentPerformance` |
| GET | `admin/reporting/live/attendance` | `Reporting\LiveCourseReportController@attendance` |
| GET | `admin/reporting/live/course-completion` | `Reporting\LiveCourseReportController@courseCompletion` |
| GET | `admin/reporting/live/course-registrations` | `Reporting\LiveCourseReportController@courseRegistrations` |
| GET | `admin/reporting/datasets/session-fact` | `Reporting\ReportingDatasetController@sessionFact` |
| GET | `admin/reporting/datasets/session-fact/{id}` | `Reporting\ReportingDatasetController@sessionFactShow` |
| GET | `admin/reporting/datasets/user-course-daily` | `Reporting\ReportingDatasetController@userCourseDaily` |
| GET | `admin/reporting/datasets/department-course-daily` | `Reporting\ReportingDatasetController@departmentCourseDaily` |
| POST | `admin/reporting/refresh/daily` | `Reporting\ReportingRefreshController@daily` |
| POST | `admin/reporting/refresh/full` | `Reporting\ReportingRefreshController@full` |
| POST | `admin/reporting/refresh/range` | `Reporting\ReportingRefreshController@range` |
| GET | `admin/reporting/refresh/log` | `Reporting\ReportingRefreshController@log` |
| GET | `admin/reporting/export/kpi-overview` | `Reporting\ReportingExportController@kpiOverview` |
| GET | `admin/reporting/export/session-fact` | `Reporting\ReportingExportController@sessionFact` |
| GET | `admin/reporting/export/user-course-daily` | `Reporting\ReportingExportController@userCourseDaily` |
| GET | `admin/reporting/export/department-course-daily` | `Reporting\ReportingExportController@departmentCourseDaily` |
| GET | `admin/reporting/export/user-course-progress-excel` | `Reporting\UserCourseProgressExcelController@export` |
| GET | `admin/reporting/export/user-performance` | `Reporting\ReportingExtraExportController@userPerformance` |
| GET | `admin/reporting/export/user-course-progress` | `Reporting\ReportingExtraExportController@userCourseProgress` |
| GET | `admin/reporting/export/live/attendance` | `Reporting\ReportingExtraExportController@attendance` |
| GET | `admin/reporting/export/live/course-completion` | `Reporting\ReportingExtraExportController@courseCompletion` |
| GET | `admin/reporting/export/live/course-registrations` | `Reporting\ReportingExtraExportController@courseRegistrations` |
| GET | `admin/reporting/export/quiz/attempts` | `Reporting\ReportingExtraExportController@quizAttempts` |
| GET | `admin/reporting/export/quiz/detailed` | `Reporting\ReportingExtraExportController@quizDetailed` |
| GET | `admin/reporting/export/evaluation/department-performance` | `Reporting\ReportingExtraExportController@evaluationDepartment` |

(Controllers namespaced `App\Http\Controllers\Api\Admin\Reporting\...`. All
rows `Admin` auth tier. There are two flavors of reporting: **cached**
(`datasets/*`, `kpi/*`, most exports — backed by `reporting_*` tables, kept
current via `refresh/*`) and **live** (`live/*` — computed on the fly from
the operational tables, no refresh needed but slower). `refresh/*` runs
`ReportingRefreshService` — see `doc/NEW_PROJECT_PHASE_7_REPORTING_BOOTSTRAP.md`
if it exists, or the service class directly, for what each refresh mode does.)

### Admin — Blog/Podcast, Feedback, Bug Reports

| Method | Path | Controller@Action |
|---|---|---|
| GET | `admin/blog-posts/getAll` | `Admin\BlogPostController@getAll` |
| GET | `admin/blog-posts/getById/{id}` | `Admin\BlogPostController@getById` |
| POST | `admin/blog-posts/create` | `Admin\BlogPostController@create` |
| PUT | `admin/blog-posts/update/{id}` | `Admin\BlogPostController@update` |
| DELETE | `admin/blog-posts/delete/{id}` | `Admin\BlogPostController@delete` |
| GET | `admin/blog-posts/available-videos` | `Admin\BlogPostController@availableVideos` |
| GET | `admin/blog-posts/available-audios` | `Admin\BlogPostController@availableAudios` |
| GET | `admin/feedback/getAll` | `Admin\FeedbackController@getAll` |
| GET | `admin/feedback/getById/{id}` | `Admin\FeedbackController@getById` |
| PUT | `admin/feedback/status/{id}` | `Admin\FeedbackController@status` |
| PUT | `admin/feedback/respond/{id}` | `Admin\FeedbackController@respond` |
| GET | `admin/bug-reports/getAll` | `Admin\BugReportController@getAll` |
| GET | `admin/bug-reports/getById/{id}` | `Admin\BugReportController@getById` |
| POST | `admin/bug-reports/create` | `Admin\BugReportController@create` |
| PUT | `admin/bug-reports/update/{id}` | `Admin\BugReportController@update` |
| DELETE | `admin/bug-reports/delete/{id}` | `Admin\BugReportController@delete` |
| PUT | `admin/bug-reports/assign/{id}` | `Admin\BugReportController@assign` |
| PUT | `admin/bug-reports/resolve/{id}` | `Admin\BugReportController@resolve` |

(All rows `Admin` auth tier. `blog_posts` is `podcast_posts` in the
database — see the model, `PodcastPost`, for why the table name and the
feature name differ.)

### User — Traditional Courses, Attendance

| Method | Path | Controller@Action |
|---|---|---|
| GET | `user/courses/getAll` | `User\CourseController@getAll` |
| GET | `user/courses/getById/{id}` | `User\CourseController@getById` |
| GET | `user/courses/my-enrollments` | `User\CourseController@myEnrollments` |
| POST | `user/courses/enroll/{courseId}` | `User\CourseController@enroll` |
| POST | `user/courses/complete/{courseId}` | `User\CourseController@complete` |
| POST | `user/courses/submitRating/{courseId}` | `User\CourseController@submitRating` |
| GET | `user/clocking/active` | `User\ClockingController@active` |
| POST | `user/clocking/clockIn` | `User\ClockingController@clockIn` |
| POST | `user/clocking/clockOut` | `User\ClockingController@clockOut` |
| GET | `user/clocking/history` | `User\ClockingController@history` |

(All rows `User` auth tier.)

### User — Online Courses (content, progress, learning sessions)

| Method | Path | Controller@Action |
|---|---|---|
| GET | `user/online-courses/getAll` | `User\UserOnlineCourseController@index` |
| GET | `user/online-courses/getById/{id}` | `User\UserOnlineCourseController@show` |
| GET | `user/online-courses/{courseId}/content/{contentId}` | `User\UserOnlineCourseController@content` |
| GET | `user/online-courses/{courseId}/content/{contentId}/attachment` | `User\UserOnlineCourseController@downloadAttachment` |
| GET | `user/online-courses/progress/{contentId}/resume` | `User\UserOnlineCourseController@resume` |
| POST | `user/online-courses/progress/pdf` | `User\ContentProgressController@updatePdf` |
| POST | `user/online-courses/sessions/start` | `User\LearningSessionController@start` |
| POST | `user/online-courses/sessions/{sessionId}/progress` | `User\LearningSessionController@progress` |
| POST | `user/online-courses/sessions/{sessionId}/end` | `User\LearningSessionController@end` |

(All rows `User` auth tier. The `sessions/*` triplet — `start`/`progress`/
`end` — is the video/pdf engagement-tracking flow that produces
`learning_sessions` rows and the `attention_score`/`is_suspicious` fields;
see `App\Services\OnlineCourse\User\LearningSessionService` for the exact
scoring formula, and `doc/LEGACY_DATA_MIGRATION_GUIDE.md`'s Phase 4 section
for how that formula compares to the old system's.)

### User — Video/Audio Streaming, Quizzes

| Method | Path | Controller@Action |
|---|---|---|
| GET | `user/audio/getAll` | `User\AudioLearningController@getAll` |
| GET | `user/audio/getById/{id}` | `User\AudioLearningController@getById` |
| GET | `user/audio/stream/{id}` | `User\AudioLearningController@stream` |
| POST | `user/audio/progress/update/{audioId}` | `User\AudioLearningController@updateProgress` |
| GET | `user/quizzes/getAll` | `User\QuizController@getAll` |
| GET | `user/quizzes/getById/{id}` | `User\QuizController@getById` |
| POST | `user/quizzes/{id}/start` | `User\QuizController@start` |
| POST | `user/quizzes/{id}/submit/{attemptId}` | `User\QuizController@submit` |
| GET | `user/quizzes/{id}/result/{attemptId}` | `User\QuizController@result` |

(All rows `User` auth tier.)

### User — Evaluations, Feedback, Blog, Dashboard, Profile

| Method | Path | Controller@Action |
|---|---|---|
| GET | `user/evaluations/getAll` | `User\UserEvaluationController@getAll` |
| GET | `user/evaluations/getById/{id}` | `User\UserEvaluationController@getById` |
| GET | `user/feedback/getAll` | `User\FeedbackController@getAll` |
| GET | `user/feedback/getById/{id}` | `User\FeedbackController@getById` |
| POST | `user/feedback/create` | `User\FeedbackController@create` |
| GET | `user/blog-posts/getAll` | `BlogFeedController@index` |
| GET | `user/blog-posts/getBySlug/{slug}` | `BlogFeedController@show` |
| POST | `user/blog-posts/like/{postId}` | `BlogLikeController@like` |
| DELETE | `user/blog-posts/unlike/{postId}` | `BlogLikeController@unlike` |
| POST | `user/blog-posts/comment/{postId}` | `BlogCommentController@store` |
| DELETE | `user/blog-comments/delete/{commentId}` | `BlogCommentController@destroy` |
| GET | `user/dashboard` | `User\UserDashboardController` |
| GET | `user/me` | `User\AuthController@me` |

(All rows `User` auth tier. `BlogFeedController`/`BlogLikeController`/
`BlogCommentController` are namespaced directly under `App\Http\Controllers`,
not under `User\`.)

### Media Streaming (signed URLs, no bearer token)

| Method | Path | Controller@Action |
|---|---|---|
| GET | `media/video/{content_id}` | `MediaStreamController@streamVideo` |
| GET | `media/video-quality/{quality_id}` | `MediaStreamController@streamVideoQuality` |
| GET | `media/video-direct/{video_id}` | `MediaStreamController@streamVideoForTranscode` |
| GET | `media/subtitle/{content_id}` | `MediaStreamController@streamSubtitle` |
| GET | `media/pdf/{content_id}` | `MediaStreamController@streamPdf` |
| GET | `media/blog-video/{video_id}` | `MediaStreamController@streamBlogVideo` |
| GET | `media/blog-video-subtitle/{video_id}` | `MediaStreamController@streamBlogVideoSubtitle` |
| GET | `media/blog-audio/{audio_id}` | `MediaStreamController@streamBlogAudio` |

(All rows `Signed` — `ValidateSignature` middleware, not `auth:sanctum`.
These URLs must be generated server-side with `URL::temporarySignedRoute()`
or similar; hitting them directly without a valid signature will fail.)

### Webhook

| Method | Path | Auth |
|---|---|---|
| POST | `transcode/callback` | No Sanctum middleware — secured instead by a shared `project_key` field in the request body, checked against `VpsApiClient::getProjectKey()` inside `Admin\TranscodeCallbackController@handle`. |

## Database schema

This file intentionally doesn't re-document the schema — the migration docs
already cover every table, every column, and (for the tables that came from
the old system) exactly how old data maps to new columns:

- `doc/LEGACY_DATA_MIGRATION_GUIDE.md` — mechanics + a full table-by-table
  "what changed and why" reference, including dropped/renamed/derived
  columns for every migrated table
- `doc/LEGACY_DATA_MIGRATION_PLAN.md` — phase-by-phase migration history and
  what's intentionally not migrated (tables with no destination, out-of-scope
  infrastructure tables)
- `php artisan migrate:status` / the `database/migrations/` folder — the
  actual, current source of truth for schema shape
- `app/Models/*.php` — every model lists its real `$fillable` and relations;
  faster to read than the migrations for understanding relationships

## Regenerating this file

Route list (grouped by feature) came from:
```bash
php artisan route:list --path=api --json
```
filtered to drop the Scramble docs routes (`docs/api`, `docs/api.json`) and
the `laramint/laravel-brain` dev-tool routes (`_laravel-brain/*`) — neither
is part of this application's actual API surface. (Laravel Telescope was
removed from the project entirely — see below — so there's no longer a
`telescope/*` route group to filter out either.)

## Removed: Laravel Telescope

Telescope (the request/query/exception inspector previously at `/telescope`)
was fully removed from this project — package, service provider, config,
migration, and its 3 database tables (`telescope_entries`,
`telescope_entries_tags`, `telescope_monitoring`). If you see references to
it in older docs or commit history, they're stale.
