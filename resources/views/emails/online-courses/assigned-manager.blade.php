<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Team Online Course Assignment Notification</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
<h2>Team Member Course Assignment</h2>
<p>Hello <strong>{{ $manager->name }}</strong>,</p>

<p>Your team member <strong>{{ $assignedUser->name }}</strong> has been assigned an online course by <strong>{{ $assignedBy->name }}</strong>.</p>

<ul>
    <li><strong>Course:</strong> {{ $course->name }}</li>
    <li><strong>Assigned to:</strong> {{ $assignedUser->name }}</li>
    <li><strong>Assigned by:</strong> {{ $assignedBy->name }}</li>
    <li><strong>Date:</strong> {{ now()->format('F j, Y') }}</li>
</ul>

<p>This is an automated notification.</p>
</body>
</html>
