<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Course Assigned to You</title></head>
<body>
    <p>Hello {{ $assignedUser->name }},</p>
    <p>You have been assigned the course <strong>{{ $course->name }}</strong> by <strong>{{ $assignedBy->name }}</strong>.</p>
    @if($course->description)
        <p>{{ $course->description }}</p>
    @endif
    <p>Please log in to the platform to view your assigned course.</p>
</body>
</html>
