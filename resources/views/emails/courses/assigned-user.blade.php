<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Course Assigned to You</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #2563eb, #1d4ed8); color: white; padding: 25px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { padding: 30px 25px; background: white; border-radius: 0 0 8px 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .course-info { background: #f8fafc; border: 2px solid #e2e8f0; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .cta-button { display: inline-block; background: linear-gradient(135deg, #2563eb, #1d4ed8); color: white !important; padding: 14px 28px; text-decoration: none !important; border-radius: 8px; font-weight: bold; box-shadow: 0 2px 4px rgba(0,0,0,0.2); }
        .link-notice { background: #fef9c3; border-left: 4px solid #ca8a04; padding: 12px 16px; border-radius: 6px; margin: 20px 0; font-size: 13px; color: #713f12; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 13px; margin-top: 10px; }
        h1 { margin: 0; font-size: 22px; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>📚 New Course Assignment</h1>
    </div>
    <div class="content">
        <p>Hello <strong>{{ $assignedUser->name }}</strong>,</p>
        <p>You have been assigned the course <strong>{{ $course->name }}</strong> by <strong>{{ $assignedBy->name }}</strong>.</p>

        <div class="course-info">
            <strong>Course:</strong> {{ $course->name }}<br>
            @if($course->description)
                <p style="margin:8px 0 0;">{{ $course->description }}</p>
            @endif
        </div>

        @if($loginLink)
            <p style="text-align:center; margin: 28px 0;">
                <a href="{{ $loginLink }}" class="cta-button">Access Your Course</a>
            </p>
            <div class="link-notice">
                This link is valid for <strong>72 hours</strong>. If it expires, contact your administrator to resend the link.
            </div>
        @else
            <p>Please log in to the platform to view your assigned course.</p>
        @endif
    </div>
    <div class="footer">This is an automated notification. Please do not reply to this email.</div>
</div>
</body>
</html>
