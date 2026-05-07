<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Now Public - {{ $course->name }}</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .email-container {
            max-width: 600px;
            margin: 20px auto;
            background: #ffffff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 300;
        }
        .content {
            padding: 30px;
        }
        .course-info {
            background: #f8f9ff;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        .cta-button {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 25px;
            font-weight: bold;
            margin: 20px 0;
            transition: background-color 0.3s;
        }
        .cta-button:hover {
            background: #5a67d8;
        }
        .highlight {
            background: #fef3c7;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            border-left: 4px solid #f59e0b;
        }
        .footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
<div class="email-container">
    <div class="header">
        <h1>🎉 Great News! Course Now Available</h1>
    </div>

    <div class="content">
        <p>Hi <strong>{{ $user->name }}</strong>,</p>

        <div class="highlight">
            <p><strong>Exciting Update!</strong> A course that was previously private is now available to everyone, including you!</p>
        </div>

        <div class="course-info">
            <h3 style="margin-top: 0; color: #667eea;">{{ $course->name }}</h3>
            <p style="margin-bottom: 0;">{{ $course->description ?: 'Expand your skills with this comprehensive course designed to help you grow professionally.' }}</p>

            @if($course->level)
                <p style="margin: 10px 0 0 0;"><strong>Level:</strong> {{ ucfirst($course->level) }}</p>
            @endif

            @if($course->duration)
                <p style="margin: 5px 0 0 0;"><strong>Duration:</strong> {{ $course->duration }} hours</p>
            @endif
        </div>

        <p>This course was previously available only to selected users, but now <strong>everyone can enroll and participate!</strong></p>

        <p>📚 <strong>What you can expect:</strong></p>
        <ul>
            <li>High-quality learning content</li>
            <li>Interactive sessions and materials</li>
            <li>Professional development opportunities</li>
            <li>Certificate of completion</li>
        </ul>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $loginLink }}" class="cta-button">
                🚀 Enroll Now & Start Learning
            </a>
        </div>

        <p style="font-size: 14px; color: #666;">
            <strong>Note:</strong> This is a one-time notification about this course becoming publicly available.
            You won't receive this type of update again for this course.
        </p>

        <p>Happy learning!</p>

        <p>
            Best regards,<br>
            <strong>The Learning Team</strong>
        </p>
    </div>

    <div class="footer">
        <p>This notification was sent because a private course became public.<br>
            You're receiving this as part of our course availability updates.</p>
    </div>
</div>
</body>
</html>
