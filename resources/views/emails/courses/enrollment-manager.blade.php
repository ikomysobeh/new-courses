<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Team Member Enrolled</title></head>
<body>
    <p>Hello {{ $manager->name }},</p>
    <p>Your team member <strong>{{ $enrolledUser->name }}</strong> has enrolled in the course: <strong>{{ $course->name }}</strong>.</p>
    <p>You are receiving this notification as their direct manager.</p>
</body>
</html>
