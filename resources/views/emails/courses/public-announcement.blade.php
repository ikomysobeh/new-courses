<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Course Now Available: {{ $courseName }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            line-height: 1.7;
            color: #374151;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f9fafb;
        }
        .email-container {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 30px 25px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
        }
        .header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
            font-size: 16px;
        }
        .content {
            padding: 40px 30px;
        }
        .greeting {
            font-size: 18px;
            color: #1f2937;
            margin-bottom: 25px;
        }
        .welcome-message {
            background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
            border-left: 4px solid #10b981;
            padding: 25px;
            margin: 25px 0;
            border-radius: 8px;
        }
        .welcome-message h3 {
            color: #059669;
            margin: 0 0 15px 0;
            font-size: 18px;
        }
        .public-notice {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            border: 2px solid #3b82f6;
            border-radius: 10px;
            padding: 20px;
            margin: 25px 0;
            text-align: center;
        }
        .public-notice h4 {
            color: #1e40af;
            margin: 0 0 10px 0;
            font-size: 16px;
        }
        .public-notice p {
            color: #1e40af;
            margin: 0;
            font-weight: 600;
        }
        .course-details {
            background-color: #f8fafc;
            padding: 25px;
            border-radius: 10px;
            margin: 25px 0;
            border: 1px solid #e2e8f0;
        }
        .course-details h3 {
            color: #1e40af;
            margin: 0 0 20px 0;
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .date-section {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border: 1px solid #f59e0b;
            border-radius: 10px;
            padding: 25px;
            margin: 25px 0;
        }
        .date-section h4 {
            color: #b45309;
            margin: 0 0 15px 0;
            font-size: 18px;
        }
        .schedule-option {
            background-color: white;
            border: 2px solid #fbbf24;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 2px 10px rgba(251, 191, 36, 0.1);
        }
        .schedule-header {
            color: #92400e;
            font-weight: 700;
            font-size: 16px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .schedule-details {
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
        .schedule-detail-label {
            font-size: 12px;
            font-weight: 600;
            color: #92400e;
            text-transform: uppercase;
            margin-bottom: 5px;
            letter-spacing: 0.5px;
        }
        .schedule-detail-value {
            font-size: 14px;
            font-weight: 600;
            color: #451a03;
        }
        .days-badge {
            display: inline-block;
            background: #10b981;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin: 2px;
        }
        .availability-info {
            background-color: #f0f9ff;
            border: 1px solid #0ea5e9;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
            text-align: center;
        }
        .availability-info .spots {
            font-size: 18px;
            font-weight: 700;
            color: #0369a1;
        }
        .login-section {
            background: linear-gradient(135deg, #ede9fe 0%, #ddd6fe 100%);
            border: 1px solid #8b5cf6;
            border-radius: 10px;
            padding: 25px;
            margin: 30px 0;
            text-align: center;
        }
        .login-section h4 {
            color: #7c3aed;
            margin: 0 0 20px 0;
            font-size: 18px;
        }
        .login-button {
            display: inline-block;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white !important;
            padding: 18px 35px;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 16px;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
            transition: transform 0.2s, box-shadow 0.2s;
            margin: 15px 0;
        }
        .login-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
        }
        .security-note {
            background-color: white;
            border: 1px solid #c4b5fd;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
            font-size: 12px;
            color: #4c1d95;
            text-align: left;
        }
        .help-section {
            background-color: #f1f5f9;
            padding: 20px;
            border-radius: 8px;
            margin: 25px 0;
            text-align: center;
        }
        .signature {
            margin-top: 40px;
            padding-top: 25px;
            border-top: 2px solid #e2e8f0;
            text-align: center;
        }
        .signature .name {
            font-weight: 700;
            color: #1e40af;
            font-size: 18px;
        }
        .signature .title {
            color: #64748b;
            margin-top: 5px;
            font-style: italic;
        }
        .footer {
            background-color: #f8fafc;
            padding: 25px;
            text-align: center;
            font-size: 13px;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
        }
        .footer a {
            color: #3b82f6;
            text-decoration: none;
        }
        @media (max-width: 600px) {
            .content {
                padding: 20px 15px;
            }
            .login-button {
                padding: 15px 25px;
                font-size: 14px;
            }
            .schedule-details {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<div class="email-container">
    <div class="header">
        <h1>🌍 Course Now Public!</h1>
        <p>Open enrollment is now available for everyone</p>
    </div>

    <div class="content">
        <div class="greeting">
            Hello {{ $userName }}, 👋
        </div>

        <div class="public-notice">
            <h4>🎉 Great News - Course is Now Open to All!</h4>
            <p>This course is now available for public enrollment. Join your colleagues in this learning opportunity!</p>
        </div>

        <div class="welcome-message">
            <h3>✨ Course Now Available to Everyone!</h3>
            <p>We're excited to announce that this course is now open for public enrollment! This learning opportunity has been designed to enhance your skills and support your career growth. Participation is optional; however, if you choose to enroll, your progress will be recognized and reflected in the personal development section of your monthly evaluation.</p>
        </div>

        <div class="course-details">
            <h3>📚 Course Information</h3>
            <p><strong>Course:</strong> {{ $courseName }}</p>

            @if($description)
                <p><strong>What you'll learn:</strong></p>
                <div style="margin-left: 15px; color: #4b5563;">{!! $description !!}</div>
            @endif

            @if($course && $course->level)
                <p><strong>Skill Level:</strong> {{ ucfirst($course->level) }}</p>
            @endif

            @if($course && $course->duration)
                <p><strong>Time Investment:</strong> {{ $course->duration }} hours</p>
            @endif

            <p><strong>Enrollment Status:</strong> ✅ Open to all team members</p>
            <p><strong>Course Type:</strong> 🌍 Public Course</p>
        </div>

        @if($availabilities && $availabilities->count() > 0)
            <div class="date-section">
                <h4>📅 Available Session Schedules</h4>
                <p>Multiple flexible session schedules are available. Choose the one that fits your calendar best:</p>

                @foreach($availabilities->take(4) as $index => $availability)
                    <div class="schedule-option">
                        <div class="schedule-header">
                            📋 Schedule Option {{ $index + 1 }}
                            @if($availability['available_spots'] <= 5 && $availability['available_spots'] > 0)
                                <span style="color: #dc2626; font-size: 12px;">(Only {{ $availability['available_spots'] }} spots left!)</span>
                            @endif
                        </div>

                        <!-- ✅ NEW: Enhanced Scheduling Details -->
                        <div class="schedule-details">
                            <!-- Days of Week -->
                            @if($availability['formatted_days'] && $availability['formatted_days'] !== 'TBD')
                                <div class="schedule-detail">
                                    <div class="schedule-detail-label">📆 Days</div>
                                    <div class="schedule-detail-value">
                                        @foreach(explode(', ', $availability['formatted_days']) as $day)
                                            <span class="days-badge">{{ $day }}</span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <!-- Duration -->
                            @if($availability['duration_weeks'])
                                <div class="schedule-detail">
                                    <div class="schedule-detail-label">⏱️ Duration</div>
                                    <div class="schedule-detail-value">{{ $availability['duration_weeks'] }} weeks</div>
                                </div>
                            @endif

                            <!-- Session Time -->
                            @if($availability['formatted_session_time'])
                                <div class="schedule-detail">
                                    <div class="schedule-detail-label">🕐 Time</div>
                                    <div class="schedule-detail-value">{{ $availability['formatted_session_time'] }}</div>
                                </div>
                            @endif

                            <!-- Session Length -->
                            @if($availability['formatted_session_duration'])
                                <div class="schedule-detail">
                                    <div class="schedule-detail-label">⏰ Length</div>
                                    <div class="schedule-detail-value">{{ $availability['formatted_session_duration'] }}</div>
                                </div>
                            @endif
                        </div>

                        <!-- Date Range -->
                        <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #fbbf24;">
                            <div style="color: #92400e; font-weight: 600; margin-bottom: 5px;">
                                📅 {{ $availability['formatted_date_range'] }}
                            </div>
                            @if($availability['start_date'] && $availability['end_date'])
                                <div style="color: #78716c; font-size: 14px;">
                                    {{ \Carbon\Carbon::parse($availability['start_date'])->format('l, F j, Y \a\t g:i A') }}
                                    @if($availability['start_date'] != $availability['end_date'])
                                        <br>through {{ \Carbon\Carbon::parse($availability['end_date'])->format('l, F j, Y \a\t g:i A') }}
                                    @endif
                                </div>
                            @endif
                        </div>

                        <!-- Availability Info -->
                        <div class="availability-info">
                            <div class="spots">{{ $availability['available_spots'] ?? $availability['sessions'] }} seats available</div>
                            @if($availability['capacity'])
                                <div style="font-size: 12px; color: #64748b; margin-top: 5px;">
                                    Total capacity: {{ $availability['capacity'] }} seats
                                </div>
                            @endif
                        </div>

                        <!-- Notes -->
                        @if($availability['notes'])
                            <div style="margin-top: 15px; padding: 12px; background-color: #f8fafc; border-radius: 6px; border-left: 3px solid #64748b;">
                                <div style="color: #64748b; font-size: 12px; font-weight: 600; margin-bottom: 5px;">📝 NOTES</div>
                                <div style="color: #475569; font-size: 14px;">{{ $availability['notes'] }}</div>
                            </div>
                        @endif
                    </div>
                @endforeach

                <div style="margin-top: 25px; padding: 20px; background-color: #ecfdf5; border: 1px solid #10b981; border-radius: 10px;">
                    <p style="margin: 0; color: #059669; font-weight: 600; text-align: center;">
                        🚀 <strong>Ready to Join?</strong> Enrollment is now open to everyone - secure your spot in your preferred schedule!
                    </p>
                </div>
            </div>
        @endif

        <div class="login-section">
            <h4>🔐 Quick Access to Enroll</h4>
            <p>Ready to join this course? Use the secure link below to access the course page and enroll in your preferred schedule:</p>

            <a href="{{ $loginLink ?? url('/courses/' . ($course->id ?? '')) }}" class="login-button">
                🚀 View Course & Select Schedule
            </a>

            @if($loginLink)
                <div class="security-note">
                    <strong>🛡️ Security Information:</strong><br>
                    • This is a secure, personalized access link<br>
                    • The link expires after 24 hours for your protection<br>
                    • It can only be used once and will log you in automatically<br>
                    • No password required - just click and start exploring!
                </div>
            @endif

            <p style="color: #6366f1; font-size: 14px; margin-top: 20px;">
                <strong>Your Email:</strong> {{ $userEmail }}<br>
                <strong>Course Platform:</strong> {{ config('app.url') }}
            </p>
        </div>

        <div class="help-section">
            <p><strong>Questions about this course? 🤝</strong></p>
            <p>Our team is here to help! If you have any questions about the course content, schedule, or enrollment process, please reach out:</p>
            <p><a href="mailto:thedevelopmentzone@onepne.com" style="color: #3b82f6; font-weight: 600;">thedevelopmentzone@onepne.com</a></p>
            <p><small>We typically respond within 2 hours during business hours</small></p>
        </div>

        <p style="color: #374151; font-size: 16px; line-height: 1.6;">
            This is a fantastic opportunity to join your colleagues in developing new skills! We encourage you to explore the course details and enroll in the schedule that works best for you. 🌟
        </p>

        <div class="signature">
            <p class="name">Harry Prescott</p>
            <p class="title">Director Of Training And Development<br>
               The Training And Development Department
</p>
            <p style="color: #3b82f6; margin-top: 10px;">📧 thedevelopmentzone@onepne.com</p>
        </div>
    </div>

    <div class="footer">
        <p>© {{ date('Y') }} The Training And Development Department
 | Empowering Growth Through Learning</p>
        <p>This public course announcement was sent to <a href="mailto:{{ $userEmail }}">{{ $userEmail }}</a></p>
        <p style="margin-top: 15px;">
            <a href="{{ config('app.url') }}">Visit our Learning Portal</a> |
            <a href="mailto:thedevelopmentzone@onepne.com">Contact Support</a>
        </p>
    </div>
</div>
</body>
</html>
