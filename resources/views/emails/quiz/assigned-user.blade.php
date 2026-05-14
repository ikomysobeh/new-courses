<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Quiz Available - {{ $quiz->title }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8fafc;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        .content {
            padding: 30px;
        }
        .course-info {
            background-color: #f1f5f9;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #3b82f6;
        }
        .quiz-details {
            background-color: #fefefe;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .deadline-section {
            margin: 25px 0;
            padding: 0;
        }
        .deadline-box {
            border-radius: 12px;
            padding: 25px;
            margin: 20px 0;
            text-align: center;
            position: relative;
        }
        .deadline-normal {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: 2px solid #059669;
        }
        .deadline-soon {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
            border: 2px solid #d97706;
        }
        .deadline-urgent {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            border: 2px solid #dc2626;
        }
        .deadline-expired {
            background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
            color: white;
            border: 2px solid #4b5563;
        }
        .no-deadline {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            color: white;
            border: 2px solid #7c3aed;
        }
        .deadline-icon {
            font-size: 48px;
            margin-bottom: 15px;
            display: block;
        }
        .deadline-time {
            font-size: 32px;
            font-weight: bold;
            margin: 15px 0;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .deadline-message {
            font-size: 18px;
            margin: 10px 0;
            font-weight: 500;
        }
        .deadline-details {
            font-size: 16px;
            margin-top: 15px;
            opacity: 0.9;
        }
        .enforcement-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            margin: 10px 5px;
            border: 2px solid rgba(255,255,255,0.3);
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            padding: 16px 32px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 18px;
            margin: 25px 0;
            box-shadow: 0 4px 6px rgba(59, 130, 246, 0.3);
        }
        .tips-section {
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
        }
        .tips-section h4 {
            color: #1e40af;
            margin: 0 0 15px 0;
            font-size: 18px;
        }
        .tips-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .tips-list li {
            padding: 8px 0;
            border-bottom: 1px solid #dbeafe;
        }
        .tips-list li:last-child {
            border-bottom: none;
        }
        .footer {
            background-color: #f8fafc;
            padding: 20px;
            text-align: center;
            color: #6b7280;
            font-size: 14px;
        }
        .course-type-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .course-type-regular {
            background-color: #dbeafe;
            color: #1e40af;
        }
        .course-type-online {
            background-color: #dcfce7;
            color: #166534;
        }
        @media (max-width: 600px) {
            .container { margin: 0 10px; }
            .header, .content { padding: 20px; }
            .deadline-time { font-size: 24px; }
            .button { padding: 14px 28px; font-size: 16px; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>📝 New Quiz Available!</h1>
    </div>

    <div class="content">
        <p style="font-size: 18px; margin-bottom: 25px;">Hello <strong>{{ $user->name }}</strong>,</p>

        <p>A new quiz has been created and is now available for you to complete.</p>

        <div class="course-info">
            <h3 style="margin: 0 0 15px 0; color: #1e40af;">Quiz Information</h3>
            <p><strong>Quiz Title:</strong> {{ $quiz->title }}</p>
            <p><strong>Course:</strong> {{ $course->name }}
                <span class="course-type-badge course-type-{{ $courseType }}">{{ ucfirst($courseType) }} Course</span>
            </p>
            @if($quiz->description)
                <p><strong>Description:</strong> {{ $quiz->description }}</p>
            @endif
            <p><strong>Total Points:</strong> {{ $quiz->total_points }} points</p>
            <p><strong>Pass Threshold:</strong> {{ $quiz->pass_threshold }}%</p>
        </div>

        {{-- DEADLINE SECTION --}}
        @if($hasDeadline && $deadline)
            @php
                $status = $deadlineStatus['status'] ?? 'normal';
                $cssClass = match($status) {
                    'urgent'  => 'deadline-urgent',
                    'soon'    => 'deadline-soon',
                    'expired' => 'deadline-expired',
                    default   => 'deadline-normal',
                };
                $icon = match($status) {
                    'urgent'  => '🚨',
                    'soon'    => '⏰',
                    'expired' => '❌',
                    default   => '📅',
                };
            @endphp

            <div class="deadline-section">
                <div class="deadline-box {{ $cssClass }}">
                    <span class="deadline-icon">{{ $icon }}</span>
                    <h2 style="margin: 0 0 10px 0; font-size: 24px;">Quiz Deadline</h2>

                    <div class="deadline-time">{{ $deadlineFormatted }}</div>

                    <div class="deadline-message">
                        {{ $deadlineStatus['message'] ?? $timeUntilDeadline }}
                    </div>

                    <div class="deadline-details">
                        @if($status === 'expired')
                            <p><strong>⚠️ This quiz deadline has passed.</strong></p>
                            @if(!$enforceDeadline)
                                <p>However, late submissions may still be accepted. Please contact your instructor.</p>
                            @endif
                        @elseif($status === 'urgent')
                            <p><strong>⚡ Less than 24 hours remaining!</strong></p>
                            <p>Complete this quiz as soon as possible.</p>
                        @elseif($status === 'soon')
                            <p><strong>📌 Deadline approaching soon.</strong></p>
                            <p>Make sure to allocate time to complete this quiz.</p>
                        @else
                            <p><strong>✅ You have sufficient time to complete this quiz.</strong></p>
                        @endif
                    </div>

                    <div class="enforcement-badge">
                        @if($enforceDeadline)
                            🔒 Strict Deadline - No late submissions accepted
                        @else
                            📌 Soft Deadline - Late submissions may be accepted
                        @endif
                    </div>
                </div>
            </div>
        @else
            <div class="deadline-section">
                <div class="deadline-box no-deadline">
                    <span class="deadline-icon">♾️</span>
                    <h3 style="margin: 0 0 15px 0;">No Deadline Set</h3>
                    <p style="font-size: 18px; margin: 0;">Take this quiz at your own pace</p>
                </div>
            </div>
        @endif

        {{-- ADDITIONAL QUIZ DETAILS --}}
        <div class="quiz-details">
            @if($timeLimitMinutes)
                <p><strong>⏱️ Time Limit:</strong> {{ $timeLimitMinutes }} minutes per attempt</p>
            @endif

            @if($hasDeadline && $deadline)
                <p><strong>📊 Quiz Statistics:</strong></p>
                <ul style="margin: 10px 0; padding-left: 20px;">
                    <li>Deadline: {{ $deadlineFormatted }}</li>
                    @if($timeLimitMinutes)
                        <li>Time per attempt: {{ $timeLimitMinutes }} minutes</li>
                    @endif
                    <li>Enforcement: {{ $enforceDeadline ? 'Strict deadline' : 'Flexible deadline' }}</li>
                </ul>
            @endif
        </div>

        {{-- ACTION BUTTON --}}
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $quizLink }}" class="button">
                @if($hasDeadline && ($deadlineStatus['status'] ?? '') === 'urgent')
                    🚨 Take Quiz Now (Urgent!)
                @elseif($hasDeadline && ($deadlineStatus['status'] ?? '') === 'expired')
                    View Quiz Details
                @else
                    📝 Take Quiz Now
                @endif
            </a>
        </div>

        {{-- TIPS SECTION --}}
        @if($hasDeadline || $timeLimitMinutes)
            <div class="tips-section">
                <h4>💡 Tips for Success</h4>
                <ul class="tips-list">
                    @if($hasDeadline)
                        <li>🕒 Start early to ensure you have enough time to complete the quiz</li>
                        @if(($deadlineStatus['status'] ?? '') === 'urgent')
                            <li>⚡ This quiz has an urgent deadline - complete it immediately</li>
                        @endif
                    @endif
                    @if($timeLimitMinutes)
                        <li>⏱️ You have {{ $timeLimitMinutes }} minutes per attempt - manage your time wisely</li>
                        <li>📖 Review the course materials before starting the quiz</li>
                    @endif
                    <li>🧘 Stay calm and read each question carefully</li>
                    <li>✅ Double-check your answers before submitting</li>
                    @if($hasDeadline && !$enforceDeadline)
                        <li>📞 Contact your instructor if you need an extension</li>
                    @endif
                </ul>
            </div>
        @endif
    </div>

    <div class="footer">
        <p>This email was sent automatically from the Learning Management System.</p>
        <p>Please do not reply to this email. If you have questions, contact your instructor.</p>
        <p style="margin-top: 15px; font-size: 12px; color: #9ca3af;">
            Quiz ID: {{ $quiz->id }} | Course: {{ $course->name }} ({{ ucfirst($courseType) }})
        </p>
    </div>
</div>
</body>
</html>
