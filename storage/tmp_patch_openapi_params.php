<?php

$targets = [
    'public/api.json',
    'api.json',
];

$paramMap = [
    'admin.attendance.getAll' => [
        ['name' => 'user_id', 'in' => 'query', 'schema' => ['type' => 'integer']],
        ['name' => 'course_id', 'in' => 'query', 'schema' => ['type' => 'integer']],
        ['name' => 'rating', 'in' => 'query', 'schema' => ['type' => 'integer']],
        ['name' => 'start_date', 'in' => 'query', 'schema' => ['type' => 'string', 'format' => 'date']],
        ['name' => 'end_date', 'in' => 'query', 'schema' => ['type' => 'string', 'format' => 'date']],
        ['name' => 'page', 'in' => 'query', 'schema' => ['type' => 'integer', 'minimum' => 1]],
    ],
    'admin.audio.getAll' => [
        ['name' => 'audio_category_id', 'in' => 'query', 'schema' => ['type' => 'integer']],
        ['name' => 'search', 'in' => 'query', 'schema' => ['type' => 'string']],
        ['name' => 'per_page', 'in' => 'query', 'schema' => ['type' => 'integer', 'minimum' => 1]],
        ['name' => 'page', 'in' => 'query', 'schema' => ['type' => 'integer', 'minimum' => 1]],
    ],
    'admin.audio-assignments.getAll' => [
        ['name' => 'audio_id', 'in' => 'query', 'schema' => ['type' => 'integer']],
        ['name' => 'user_id', 'in' => 'query', 'schema' => ['type' => 'integer']],
        ['name' => 'search', 'in' => 'query', 'schema' => ['type' => 'string']],
        ['name' => 'per_page', 'in' => 'query', 'schema' => ['type' => 'integer', 'minimum' => 1]],
        ['name' => 'page', 'in' => 'query', 'schema' => ['type' => 'integer', 'minimum' => 1]],
    ],
    'admin.courses.getAll' => [
        ['name' => 'status', 'in' => 'query', 'schema' => ['type' => 'string']],
        ['name' => 'privacy', 'in' => 'query', 'schema' => ['type' => 'string']],
        ['name' => 'search', 'in' => 'query', 'schema' => ['type' => 'string']],
        ['name' => 'page', 'in' => 'query', 'schema' => ['type' => 'integer', 'minimum' => 1]],
    ],
    'admin.course-assignments.getAll' => [
        ['name' => 'course_id', 'in' => 'query', 'schema' => ['type' => 'integer']],
        ['name' => 'user_id', 'in' => 'query', 'schema' => ['type' => 'integer']],
        ['name' => 'page', 'in' => 'query', 'schema' => ['type' => 'integer', 'minimum' => 1]],
    ],
    'admin.evaluations.getAll' => [
        ['name' => 'course_type', 'in' => 'query', 'schema' => ['type' => 'string']],
        ['name' => 'department_id', 'in' => 'query', 'schema' => ['type' => 'integer']],
        ['name' => 'user_id', 'in' => 'query', 'schema' => ['type' => 'integer']],
        ['name' => 'performance_level', 'in' => 'query', 'schema' => ['type' => 'integer']],
        ['name' => 'start_date', 'in' => 'query', 'schema' => ['type' => 'string', 'format' => 'date']],
        ['name' => 'end_date', 'in' => 'query', 'schema' => ['type' => 'string', 'format' => 'date']],
        ['name' => 'page', 'in' => 'query', 'schema' => ['type' => 'integer', 'minimum' => 1]],
    ],
    'admin.evaluations.users' => [
        ['name' => 'department_id', 'in' => 'query', 'schema' => ['type' => 'integer']],
        ['name' => 'course_type', 'in' => 'query', 'schema' => ['type' => 'string', 'default' => 'regular']],
    ],
    'admin.evaluations.user-courses' => [
        ['name' => 'user_id', 'in' => 'query', 'schema' => ['type' => 'integer']],
        ['name' => 'course_type', 'in' => 'query', 'schema' => ['type' => 'string', 'default' => 'regular']],
    ],
    'admin.evaluation-history.getAll' => [
        ['name' => 'department_id', 'in' => 'query', 'schema' => ['type' => 'integer']],
        ['name' => 'user_id', 'in' => 'query', 'schema' => ['type' => 'integer']],
        ['name' => 'course_type', 'in' => 'query', 'schema' => ['type' => 'string']],
        ['name' => 'performance_level', 'in' => 'query', 'schema' => ['type' => 'integer']],
        ['name' => 'start_date', 'in' => 'query', 'schema' => ['type' => 'string', 'format' => 'date']],
        ['name' => 'end_date', 'in' => 'query', 'schema' => ['type' => 'string', 'format' => 'date']],
        ['name' => 'page', 'in' => 'query', 'schema' => ['type' => 'integer', 'minimum' => 1]],
    ],
    'admin.evaluation-history.analytics' => [
        ['name' => 'department_id', 'in' => 'query', 'schema' => ['type' => 'integer']],
        ['name' => 'course_type', 'in' => 'query', 'schema' => ['type' => 'string']],
        ['name' => 'start_date', 'in' => 'query', 'schema' => ['type' => 'string', 'format' => 'date']],
        ['name' => 'end_date', 'in' => 'query', 'schema' => ['type' => 'string', 'format' => 'date']],
    ],
    'admin.evaluation-history.export' => [
        ['name' => 'department_id', 'in' => 'query', 'schema' => ['type' => 'integer']],
        ['name' => 'user_id', 'in' => 'query', 'schema' => ['type' => 'integer']],
        ['name' => 'course_type', 'in' => 'query', 'schema' => ['type' => 'string']],
        ['name' => 'performance_level', 'in' => 'query', 'schema' => ['type' => 'integer']],
        ['name' => 'start_date', 'in' => 'query', 'schema' => ['type' => 'string', 'format' => 'date']],
        ['name' => 'end_date', 'in' => 'query', 'schema' => ['type' => 'string', 'format' => 'date']],
    ],
    'admin.evaluation-history.export-summary' => [
        ['name' => 'start_date', 'in' => 'query', 'schema' => ['type' => 'string', 'format' => 'date']],
        ['name' => 'end_date', 'in' => 'query', 'schema' => ['type' => 'string', 'format' => 'date']],
    ],
    'admin.evaluation-notifications.getAll' => [
        ['name' => 'start_date', 'in' => 'query', 'schema' => ['type' => 'string', 'format' => 'date']],
        ['name' => 'end_date', 'in' => 'query', 'schema' => ['type' => 'string', 'format' => 'date']],
        ['name' => 'page', 'in' => 'query', 'schema' => ['type' => 'integer', 'minimum' => 1]],
    ],
    'admin.online-courses.getAll' => [
        ['name' => 'status', 'in' => 'query', 'schema' => ['type' => 'string']],
        ['name' => 'search', 'in' => 'query', 'schema' => ['type' => 'string']],
        ['name' => 'per_page', 'in' => 'query', 'schema' => ['type' => 'integer', 'minimum' => 1]],
        ['name' => 'page', 'in' => 'query', 'schema' => ['type' => 'integer', 'minimum' => 1]],
    ],
    'admin.online-course-assignments.getAll' => [
        ['name' => 'course_online_id', 'in' => 'query', 'schema' => ['type' => 'integer']],
        ['name' => 'user_id', 'in' => 'query', 'schema' => ['type' => 'integer']],
        ['name' => 'is_overdue', 'in' => 'query', 'schema' => ['type' => 'boolean']],
        ['name' => 'per_page', 'in' => 'query', 'schema' => ['type' => 'integer', 'minimum' => 1]],
        ['name' => 'page', 'in' => 'query', 'schema' => ['type' => 'integer', 'minimum' => 1]],
    ],
    'admin.quizzes.getAll' => [
        ['name' => 'status', 'in' => 'query', 'schema' => ['type' => 'string']],
        ['name' => 'course_id', 'in' => 'query', 'schema' => ['type' => 'integer']],
    ],
    'admin.quiz-assignments.getAll' => [
        ['name' => 'quiz_id', 'in' => 'query', 'schema' => ['type' => 'integer']],
        ['name' => 'user_id', 'in' => 'query', 'schema' => ['type' => 'integer']],
        ['name' => 'notification_sent', 'in' => 'query', 'schema' => ['type' => 'boolean']],
    ],
    'admin.users.getAll' => [
        ['name' => 'department_id', 'in' => 'query', 'schema' => ['type' => 'integer']],
        ['name' => 'user_level_tier_id', 'in' => 'query', 'schema' => ['type' => 'integer']],
        ['name' => 'search', 'in' => 'query', 'schema' => ['type' => 'string']],
        ['name' => 'per_page', 'in' => 'query', 'schema' => ['type' => 'integer', 'minimum' => 1]],
        ['name' => 'page', 'in' => 'query', 'schema' => ['type' => 'integer', 'minimum' => 1]],
    ],
    'admin.videos.getAll' => [
        ['name' => 'video_category_id', 'in' => 'query', 'schema' => ['type' => 'integer']],
        ['name' => 'transcode_status', 'in' => 'query', 'schema' => ['type' => 'string']],
        ['name' => 'search', 'in' => 'query', 'schema' => ['type' => 'string']],
        ['name' => 'per_page', 'in' => 'query', 'schema' => ['type' => 'integer', 'minimum' => 1]],
        ['name' => 'page', 'in' => 'query', 'schema' => ['type' => 'integer', 'minimum' => 1]],
    ],
    'admin.video-categories.getAll' => [
        ['name' => 'search', 'in' => 'query', 'schema' => ['type' => 'string']],
        ['name' => 'per_page', 'in' => 'query', 'schema' => ['type' => 'integer', 'minimum' => 1]],
        ['name' => 'page', 'in' => 'query', 'schema' => ['type' => 'integer', 'minimum' => 1]],
    ],
    'user.audio.getAll' => [
        ['name' => 'audio_category_id', 'in' => 'query', 'schema' => ['type' => 'integer']],
        ['name' => 'search', 'in' => 'query', 'schema' => ['type' => 'string']],
        ['name' => 'per_page', 'in' => 'query', 'schema' => ['type' => 'integer', 'minimum' => 1]],
        ['name' => 'page', 'in' => 'query', 'schema' => ['type' => 'integer', 'minimum' => 1]],
    ],
    'user.clocking.history' => [
        ['name' => 'page', 'in' => 'query', 'schema' => ['type' => 'integer', 'minimum' => 1]],
    ],
    'user.courses.getAll' => [
        ['name' => 'search', 'in' => 'query', 'schema' => ['type' => 'string']],
        ['name' => 'page', 'in' => 'query', 'schema' => ['type' => 'integer', 'minimum' => 1]],
    ],
    'user.evaluations.getAll' => [
        ['name' => 'page', 'in' => 'query', 'schema' => ['type' => 'integer', 'minimum' => 1]],
    ],
];

foreach ($targets as $target) {
    $json = json_decode(file_get_contents($target), true);
    if (!is_array($json)) {
        fwrite(STDERR, "Failed to decode {$target}\n");
        exit(1);
    }

    foreach ($json['paths'] as &$pathItem) {
        foreach (['get', 'post', 'put', 'patch', 'delete'] as $method) {
            if (!isset($pathItem[$method]['operationId'])) {
                continue;
            }

            $operationId = $pathItem[$method]['operationId'];
            if (!isset($paramMap[$operationId])) {
                continue;
            }

            $pathItem[$method]['parameters'] = $paramMap[$operationId];
        }
    }
    unset($pathItem);

    file_put_contents($target, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
    echo "patched {$target}\n";
}
