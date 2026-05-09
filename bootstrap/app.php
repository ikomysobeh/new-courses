<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\App\Exceptions\Quiz\QuizNotAvailableException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        });
        $exceptions->render(function (\App\Exceptions\Quiz\QuizNotAssignedException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        });
        $exceptions->render(function (\App\Exceptions\Quiz\AlreadyPassedException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        });
        $exceptions->render(function (\App\Exceptions\Quiz\MaxAttemptsReachedException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        });
        $exceptions->render(function (\App\Exceptions\Quiz\RetryDelayActiveException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        });
        $exceptions->render(function (\App\Exceptions\Quiz\DeadlinePassedException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        });
    })->create();
