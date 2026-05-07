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
use App\Http\Controllers\User\CourseController as UserCourseController;
use App\Http\Controllers\User\ClockingController;
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
    });
});
