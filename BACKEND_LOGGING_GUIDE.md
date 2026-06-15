# Backend Logging Guide for Learning Session APIs

## Overview

This guide shows you where to add logging in the 3 main learning session APIs so you can understand when and why each endpoint is called.

## The 3 API Endpoints

| Endpoint | Method | Route | Controller Method |
|----------|--------|-------|-------------------|
| **Start Session** | POST | `/user/online-courses/sessions/start` | `LearningSessionController@start` |
| **Update Progress** | POST | `/user/online-courses/sessions/{sessionId}/progress` | `LearningSessionController@progress` |
| **End Session** | POST | `/user/online-courses/sessions/{sessionId}/end` | `LearningSessionController@end` |

---

## Where to Add Logs

### 1. Start Session API

**File:** `app/Http/Controllers/User/LearningSessionController.php`

**Method:** `start()`

**Add logging at the beginning:**

```php
public function start(StartSessionRequest $request)
{
    // ADD THIS LOG
    \Log::info('LEARNING_SESSION_START_CALLED', [
        'user_id' => auth()->id(),
        'course_online_id' => $request->course_online_id,
        'content_id' => $request->content_id,
        'content_type' => $request->content_type,
        'timestamp' => now()->toIso8601String(),
        'ip' => $request->ip(),
        'user_agent' => $request->header('User-Agent'),
    ]);

    $result = $this->service->startSession(
        auth()->id(),
        $request->course_online_id,
        $request->content_id,
        $request->content_type
    );

    // ADD THIS LOG (success)
    \Log::info('LEARNING_SESSION_START_SUCCESS', [
        'user_id' => auth()->id(),
        'session_id' => $result['session_id'],
        'resume_position' => $result['resume_position'],
        'is_completed' => $result['is_completed'],
    ]);

    return new SessionStartResource($result);
}
```

---

### 2. Update Progress API

**File:** `app/Http/Controllers/User/LearningSessionController.php`

**Method:** `progress()`

**Add logging:**

```php
public function progress(UpdateSessionProgressRequest $request, int $sessionId)
{
    // ADD THIS LOG
    \Log::info('LEARNING_SESSION_PROGRESS_CALLED', [
        'user_id' => auth()->id(),
        'session_id' => $sessionId,
        'payload' => $request->validated(),
        'timestamp' => now()->toIso8601String(),
        'ip' => $request->ip(),
    ]);

    $this->service->updateProgress($sessionId, auth()->id(), $request->validated());

    // ADD THIS LOG
    \Log::info('LEARNING_SESSION_PROGRESS_SUCCESS', [
        'user_id' => auth()->id(),
        'session_id' => $sessionId,
    ]);

    return response()->json(['ok' => true]);
}
```

---

### 3. End Session API

**File:** `app/Http/Controllers/User/LearningSessionController.php`

**Method:** `end()`

**Add logging:**

```php
public function end(EndSessionRequest $request, int $sessionId)
{
    // ADD THIS LOG
    \Log::info('LEARNING_SESSION_END_CALLED', [
        'user_id' => auth()->id(),
        'session_id' => $sessionId,
        'payload' => $request->validated(),
        'timestamp' => now()->toIso8601String(),
        'ip' => $request->ip(),
    ]);

    $result = $this->service->endSession($sessionId, auth()->id(), $request->validated());

    // ADD THIS LOG
    \Log::info('LEARNING_SESSION_END_SUCCESS', [
        'user_id' => auth()->id(),
        'session_id' => $result['session_id'],
        'attention_score' => $result['attention_score'],
        'content_completed' => $result['content_completed'],
        'course_progress_percentage' => $result['course_progress_percentage'],
    ]);

    return new SessionEndResource($result);
}
```

---

## How to View the Logs

### Option 1: Real-time Log Watching

Run this command in your terminal:

```bash
# Tail the Laravel log file
tail -f storage/logs/laravel.log | grep "LEARNING_SESSION"
```

### Option 2: Filter Specific API

```bash
# Only show START calls
tail -f storage/logs/laravel.log | grep "LEARNING_SESSION_START"

# Only show PROGRESS calls
tail -f storage/logs/laravel.log | grep "LEARNING_SESSION_PROGRESS"

# Only show END calls
tail -f storage/logs/laravel.log | grep "LEARNING_SESSION_END"
```

### Option 3: Windows PowerShell

If you're on Windows with PowerShell:

```powershell
# Real-time monitoring
Get-Content -Path "storage\logs\laravel.log" -Wait | Select-String "LEARNING_SESSION"
```

---

## Understanding the Log Output

When you look at the logs, you'll see patterns like this:

```
[2024-01-15 10:30:45] production.INFO: LEARNING_SESSION_START_CALLED {"user_id":123,"course_online_id":5,"content_id":10,"content_type":"video"...
[2024-01-15 10:30:45] production.INFO: LEARNING_SESSION_START_SUCCESS {"user_id":123,"session_id":456...

[2024-01-15 10:32:45] production.INFO: LEARNING_SESSION_PROGRESS_CALLED {"user_id":123,"session_id":456...  ← 2 minutes later (ticker)
[2024-01-15 10:32:45] production.INFO: LEARNING_SESSION_PROGRESS_SUCCESS {"user_id":123,"session_id":456...

[2024-01-15 10:34:12] production.INFO: LEARNING_SESSION_PROGRESS_CALLED {"user_id":123,"session_id":456...  ← User paused (immediate)
[2024-01-15 10:35:45] production.INFO: LEARNING_SESSION_PROGRESS_CALLED {"user_id":123,"session_id":456...  ← 2 min ticker (BUG: should not fire while paused!)

[2024-01-15 10:40:00] production.INFO: LEARNING_SESSION_END_CALLED {"user_id":123,"session_id":456...  ← Video ended or user left
```

### What to Look For (The Bug)

**The Problem:** You should see `LEARNING_SESSION_PROGRESS_CALLED` logs even when the video is paused (user not watching).

**Expected After Fix:** The progress logs should only appear when:
1. Video is actively playing (every 2 minutes)
2. User performs an action (pause, seek, etc.)
3. Not when video is paused and user is away

---

## Next Steps

1. **Add the logging code** to the 3 controller methods (copy/paste from above)
2. **Clear/backup your current log file:**
   ```bash
   mv storage/logs/laravel.log storage/logs/laravel-backup.log
   touch storage/logs/laravel.log
   ```
3. **Start watching logs:**
   ```bash
   tail -f storage/logs/laravel.log | grep "LEARNING_SESSION"
   ```
4. **Open a video in your app** and test:
   - Play for 2+ minutes
   - Pause and wait 2+ minutes
   - Check if progress API is called while paused

This will give you clear evidence of when the bug occurs and help verify when it's fixed.
