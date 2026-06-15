<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Team Course Enrollment Notification</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            line-height: 1.6;
            color: #374151;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f9fafb;
        }
        .email-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #1f2937 0%, #374151 100%);
            color: white;
            padding: 25px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 22px;
            font-weight: 600;
        }
        .content {
            padding: 30px;
        }
        .greeting {
            font-size: 16px;
            color: #1f2937;
            margin-bottom: 20px;
        }
        .notification-badge {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            border-left: 4px solid #3b82f6;
            padding: 20px;
            margin: 20px 0;
            border-radius: 6px;
        }
        .notification-badge h3 {
            color: #1e40af;
            margin: 0 0 10px 0;
            font-size: 16px;
        }
        .employee-details {
            background-color: #f8fafc;
            padding: 20px;
            border-radius: 6px;
            margin: 20px 0;
            border: 1px solid #e2e8f0;
        }
        .employee-details h4 {
            color: #1e40af;
            margin: 0 0 15px 0;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .employee-item {
            background-color: white;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 15px;
            margin: 10px 0;
        }
        .employee-item strong {
            color: #374151;
        }
        .employee-item small {
            color: #6b7280;
        }
        .course-info {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border: 1px solid #0ea5e9;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }
        .course-info h4 {
            color: #0c4a6e;
            margin: 0 0 15px 0;
            font-size: 16px;
        }
        .schedule-section {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border: 2px solid #f59e0b;
            border-radius: 8px;
            padding: 25px;
            margin: 25px 0;
        }
        .schedule-section h4 {
            color: #92400e;
            margin: 0 0 20px 0;
            font-size: 18px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .schedule-options {
            display: grid;
            gap: 20px;
        }
        .schedule-option {
            background-color: white;
            border: 2px solid #fbbf24;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(251, 191, 36, 0.1);
        }
        .schedule-header {
            color: #92400e;
            font-weight: 700;
            font-size: 16px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .schedule-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin: 15px 0;
        }
        .schedule-detail {
            background-color: #fffbeb;
            padding: 12px;
            border-radius: 6px;
            border-left: 3px solid #f59e0b;
        }
        .schedule-label {
            font-size: 12px;
            font-weight: 600;
            color: #92400e;
            text-transform: uppercase;
            margin-bottom: 5px;
            letter-spacing: 0.5px;
        }
        .schedule-value {
            font-size: 14px;
            font-weight: 600;
            color: #451a03;
        }
        .days-container {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 5px;
        }
        .day-badge {
            background: #f59e0b;
            color: white;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
        .availability-badge {
            background: #10b981;
            color: white;
            padding: 4px 12px;
            border-radius: 16px;
            font-size: 12px;
            font-weight: 600;
        }
        .availability-low {
            background: #f59e0b;
        }
        .availability-full {
            background: #ef4444;
        }
        .important-note {
            background: linear-gradient(135deg, #fecaca 0%, #fca5a5 100%);
            border: 1px solid #ef4444;
            border-radius: 6px;
            padding: 20px;
            margin: 25px 0;
        }
        .important-note h4 {
            color: #dc2626;
            margin: 0 0 10px 0;
            font-size: 16px;
            font-weight: 600;
        }
        .signature {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
        }
        .signature .name {
            font-weight: 600;
            color: #1f2937;
            font-size: 16px;
        }
        .signature .title {
            color: #6b7280;
            margin-top: 5px;
            font-size: 14px;
        }
        .footer {
            background-color: #f9fafb;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
        }
        @media (max-width: 600px) {
            .content {
                padding: 20px 15px;
            }
            .schedule-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<div class="email-container">
    <div class="header">
        <h1>📋 Team Course Enrollment</h1>
    </div>

    <div class="content">
        <div class="greeting">
            Dear {{ $manager->name }},
        </div>

        <div class="notification-badge">
            <h3>📚 Course Enrollment Notification</h3>
            <p>I'm writing to inform you that member{{ $userCount > 1 ? 's' : '' }} of your team ha{{ $userCount > 1 ? 've' : 's' }} enrolled in a professional development course designed to enhance their skills and support career growth.</p>
        </div>

        <div class="employee-details">
            <h4>👥 Enrolled Team Member{{ $userCount > 1 ? 's' : '' }}</h4>
            @foreach($assignedUsers as $user)
                <div class="employee-item">
                    <strong>{{ $user->name }}</strong><br>
                    <small>{{ $user->email }}
                        @if($user->department)
                            • {{ $user->department->name }} Department
                        @endif
                    </small>
                </div>
            @endforeach
        </div>

        <div class="course-info">
            <h4>📚 Course Information</h4>
            <p><strong>Course:</strong> {{ $course->name }}</p>
            <p><strong>Enrolled by:</strong> {{ $enrolledUser->name }}</p>
            <p><strong>Enrollment Date:</strong> {{ $assignmentDate }}</p>
            @if($course->description)
                <p style="margin-top: 15px;"><strong>Course Description:</strong></p>
                <div style="margin-left: 15px; color: #4b5563; font-size: 14px;">{!! $course->description !!}</div>
            @endif
            @if($course->level)
                <p><strong>Skill Level:</strong> {{ ucfirst($course->level) }}</p>
            @endif
            @if($course->duration)
                <p><strong>Total Duration:</strong> {{ $course->duration }} hours</p>
            @endif
        </div>

        @if($availabilities && $availabilities->count() > 0)
            <div class="schedule-section">
                <h4>📅 Available Schedule Options</h4>
                <p style="color: #92400e; margin-bottom: 20px;">Your team member{{ $userCount > 1 ? 's' : '' }} can choose from the following session schedules:</p>

                <div class="schedule-options">
                    @foreach($availabilities->take(3) as $index => $availability)
                        <div class="schedule-option">
                            <div class="schedule-header">
                                <span>📋 Schedule Option {{ $index + 1 }}</span>
                                <span class="availability-badge {{ $availability['available_spots'] <= 5 ? ($availability['available_spots'] == 0 ? 'availability-full' : 'availability-low') : '' }}">
                                    {{ $availability['available_spots'] ?? $availability['sessions'] }} spots left
                                </span>
                            </div>

                            <div class="schedule-grid">
                                @if($availability['formatted_days'] && $availability['formatted_days'] !== 'TBD')
                                    <div class="schedule-detail">
                                        <div class="schedule-label">📆 Days</div>
                                        <div class="days-container">
                                            @foreach(explode(', ', $availability['formatted_days']) as $day)
                                                <span class="day-badge">{{ $day }}</span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                @if($availability['duration_weeks'])
                                    <div class="schedule-detail">
                                        <div class="schedule-label">⏱️ Duration</div>
                                        <div class="schedule-value">{{ $availability['duration_weeks'] }} weeks</div>
                                    </div>
                                @endif

                                @if($availability['formatted_session_time'])
                                    <div class="schedule-detail">
                                        <div class="schedule-label">🕐 Start Time</div>
                                        <div class="schedule-value">{{ $availability['formatted_session_time'] }}</div>
                                    </div>
                                @endif

                                @if($availability['formatted_session_duration'])
                                    <div class="schedule-detail">
                                        <div class="schedule-label">⏰ Session Length</div>
                                        <div class="schedule-value">{{ $availability['formatted_session_duration'] }}</div>
                                    </div>
                                @endif
                            </div>

                            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #fbbf24;">
                                <div style="color: #92400e; font-weight: 600; margin-bottom: 5px;">
                                    📅 {{ $availability['formatted_date_range'] }}
                                </div>
                                @if($availability['start_date'] && $availability['end_date'])
                                    <div style="color: #78716c; font-size: 13px;">
                                        {{ \Carbon\Carbon::parse($availability['start_date'])->format('l, F j, Y \a\t g:i A') }}
                                        @if($availability['start_date'] != $availability['end_date'])
                                            <br>through {{ \Carbon\Carbon::parse($availability['end_date'])->format('l, F j, Y \a\t g:i A') }}
                                        @endif
                                    </div>
                                @endif
                            </div>

                            @if($availability['notes'])
                                <div style="margin-top: 15px; padding: 12px; background-color: #f8fafc; border-radius: 6px; border-left: 3px solid #64748b;">
                                    <div style="color: #64748b; font-size: 11px; font-weight: 600; margin-bottom: 5px;">📝 SCHEDULE NOTES</div>
                                    <div style="color: #475569; font-size: 13px;">{{ $availability['notes'] }}</div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                <div style="margin-top: 20px; padding: 15px; background-color: #fefce8; border: 1px solid #eab308; border-radius: 6px;">
                    <p style="margin: 0; color: #a16207; font-size: 14px; text-align: center;">
                        <strong>ℹ️ Note:</strong> {{ $userCount > 1 ? 'Each team member' : 'Your team member' }} selected {{ $userCount > 1 ? 'their' : 'a' }} preferred schedule during enrollment.
                    </p>
                </div>
            </div>
        @endif

        <p>This course contributes to their personal development and to our team's continued success.</p>

        <div class="important-note">
            <h4>📌 Important Information</h4>
            <p><strong>Please note that participation in this course is expected</strong> for the enrolled team member{{ $userCount > 1 ? 's' : '' }}, and progress may be reflected in the <strong>personal development section</strong> of monthly evaluations.</p>
        </div>

        <p>The enrolled team member{{ $userCount > 1 ? 's' : '' }} will be able to:</p>

        <ul style="margin: 15px 0; padding-left: 25px; color: #374151;">
            <li>Track their completion status and progress</li>
            <li>Access course materials and resources</li>
            <li>Participate in learning sessions</li>
            <li>Complete assessments and track development</li>
        </ul>

        <p>If you have any questions about this enrollment, please feel free to reach out.</p>

        <div class="signature">
            <p class="name">Best regards,<br>Harry Prescott</p>
            <p class="title">Director Of Training And Development<br>The Training And Development Department</p>
            <p style="color: #3b82f6; margin-top: 8px; font-size: 14px;">📧 thedevelopmentzone@onepne.com</p>
        </div>
    </div>

    <div class="footer">
        <p>© {{ date('Y') }} The Training And Development Department</p>
        <p>This notification was sent regarding course enrollment for your team member{{ $userCount > 1 ? 's' : '' }}.</p>
    </div>
</div>
</body>
</html>
