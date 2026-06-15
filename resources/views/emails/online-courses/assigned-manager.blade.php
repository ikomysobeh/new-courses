<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exclusive Team Course Assignment - Manager Notification</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #7c3aed, #6366f1); color: white; padding: 25px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { padding: 30px 25px; background: white; border-radius: 0 0 8px 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .exclusive-notice { background: linear-gradient(135deg, #fef3c7, #fde68a); border-left: 4px solid #f59e0b; padding: 20px; border-radius: 8px; margin: 25px 0; }
        .mandatory-alert { background: #fee2e2; border: 2px solid #dc2626; padding: 20px; border-radius: 8px; margin: 25px 0; }

        .deadline-critical { background: #fee2e2; border: 3px solid #dc2626; padding: 20px; border-radius: 8px; margin: 25px 0; animation: pulse 2s infinite; }
        .deadline-urgent { background: #fef3c7; border: 2px solid #f59e0b; padding: 20px; border-radius: 8px; margin: 25px 0; }
        .deadline-warning { background: #dbeafe; border: 2px solid #3b82f6; padding: 20px; border-radius: 8px; margin: 25px 0; }
        .deadline-normal { background: #f0fdf4; border-left: 4px solid #059669; padding: 20px; border-radius: 8px; margin: 25px 0; }

        @keyframes pulse {
            0% { border-color: #dc2626; }
            50% { border-color: #ef4444; }
            100% { border-color: #dc2626; }
        }

        .course-info { background: #f8fafc; border: 2px solid #e2e8f0; padding: 25px; border-radius: 8px; margin: 25px 0; }
        .team-members { background: #f0f9ff; border: 2px solid #0ea5e9; padding: 25px; border-radius: 8px; margin: 25px 0; }
        .member { padding: 15px; border-left: 4px solid #2563eb; margin: 15px 0; background: white; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }

        .member-overdue { border-left-color: #dc2626; background: #fef2f2; }
        .member-urgent { border-left-color: #f59e0b; background: #fffbeb; }
        .member-warning { border-left-color: #3b82f6; background: #eff6ff; }
        .member-normal { border-left-color: #059669; background: #f0fdf4; }

        .manager-actions { background: #f0fdf4; border-left: 4px solid #059669; padding: 20px; border-radius: 8px; margin: 25px 0; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 14px; background: #f8fafc; margin-top: 20px; border-radius: 8px; }
        .highlight { color: #dc2626; font-weight: bold; }

        .deadline-overdue { color: #dc2626; font-weight: bold; }
        .deadline-urgent { color: #f59e0b; font-weight: bold; }
        .deadline-warning { color: #3b82f6; font-weight: bold; }
        .deadline-normal { color: #059669; font-weight: bold; }

        h1 { margin: 0; font-size: 24px; }
        h2 { color: #1e293b; margin-bottom: 15px; }
        h3 { color: #374151; margin-bottom: 10px; }
        ul { padding-left: 20px; }
        li { margin-bottom: 8px; }
        .emphasis { background: #fef3c7; padding: 2px 6px; border-radius: 4px; font-weight: bold; }

        .time-remaining { font-size: 18px; font-weight: bold; text-align: center; padding: 10px; border-radius: 6px; margin: 15px 0; }
        .time-critical { background: #fee2e2; color: #dc2626; }
        .time-urgent { background: #fef3c7; color: #f59e0b; }
        .time-warning { background: #dbeafe; color: #3b82f6; }
        .time-normal { background: #f0fdf4; color: #059669; }
    </style>
</head>
<body>
@php
    $hasDeadline = $hasDeadline ?? false;
    $deadlineDate = $deadlineDate ?? null;
    $deadlineStatus = $deadlineStatus ?? 'normal';
    $daysUntilDeadline = $daysUntilDeadline ?? null;
    $urgencyMessage = $urgencyMessage ?? null;
    $deadlineType = $deadlineType ?? 'flexible';
    $teamMembers = $teamMembers ?? collect(isset($assignedUser) ? [$assignedUser] : []);
@endphp
<div class="container">
    <div class="header">
        <h1>👥 Exclusive Team Course Assignment</h1>
        <p style="margin: 10px 0 0 0; font-size: 16px; opacity: 0.9;">Manager Notification - Professional Development</p>

        @if($hasDeadline)
            <div style="background: rgba(255,255,255,0.2); padding: 10px; border-radius: 6px; margin-top: 15px;">
                @if($deadlineStatus === 'overdue' || $deadlineStatus === 'due_today')
                    <span style="font-size: 16px;">🚨 URGENT DEADLINE: {{ $deadlineDate ?? 'Not set' }}</span>
                @elseif($deadlineStatus === 'due_soon')
                    <span style="font-size: 16px;">⚠️ Due Soon: {{ $deadlineDate ?? 'Not set' }}</span>
                @else
                    <span style="font-size: 16px;">📅 Deadline: {{ $deadlineDate ?? 'Not set' }}</span>
                @endif
            </div>
        @endif
    </div>

    <div class="content">
        <p>Hello <strong>{{ $manager->name }}</strong>,</p>

        @if($hasDeadline)
            @php
                $deadlineClass = match($deadlineStatus) {
                    'overdue', 'due_today' => 'deadline-critical',
                    'due_soon' => 'deadline-urgent',
                    'due_this_week' => 'deadline-warning',
                    default => 'deadline-normal'
                };

                $timeClass = match($deadlineStatus) {
                    'overdue', 'due_today' => 'time-critical',
                    'due_soon' => 'time-urgent',
                    'due_this_week' => 'time-warning',
                    default => 'time-normal'
                };
            @endphp

            <div class="{{ $deadlineClass }}">
                <h3>
                    @if($deadlineStatus === 'overdue')
                        🚨 CRITICAL - DEADLINE PASSED
                    @elseif($deadlineStatus === 'due_today')
                        🚨 CRITICAL DEADLINE ALERT - DUE TODAY
                    @elseif($deadlineStatus === 'due_soon')
                        ⚠️ URGENT DEADLINE NOTICE
                    @elseif($deadlineStatus === 'due_this_week')
                        📅 DEADLINE REMINDER
                    @else
                        📋 COURSE DEADLINE INFORMATION
                    @endif
                </h3>

                <div class="time-remaining {{ $timeClass }}">
                    @if($daysUntilDeadline !== null)
                        @if($daysUntilDeadline < 0)
                            Overdue by {{ abs($daysUntilDeadline) }} day{{ abs($daysUntilDeadline) !== 1 ? 's' : '' }}
                        @elseif($daysUntilDeadline === 0)
                            Due TODAY!
                        @elseif($daysUntilDeadline === 1)
                            Due TOMORROW!
                        @else
                            {{ $daysUntilDeadline }} days remaining
                        @endif
                    @else
                        Course Deadline: {{ $deadlineDate ?? 'Not set' }}
                    @endif
                </div>

                @if($urgencyMessage)
                    <p style="margin: 15px 0; font-weight: 500;">{{ $urgencyMessage }}</p>
                @endif

                <p><strong>Deadline Type:</strong> {{ ucfirst($deadlineType ?? 'flexible') }}
                    @if(($deadlineType ?? 'flexible') === 'strict')
                        <span style="color: #dc2626;">(Access will be blocked after deadline)</span>
                    @else
                        <span style="color: #059669;">(Late completion allowed but monitored)</span>
                    @endif
                </p>
            </div>
        @endif

        <div class="exclusive-notice">
            <h3>🌟 Exclusive Selection Notification</h3>
            <p>This is to inform you that
                @if($teamMembers->count() === 1)
                    <strong>{{ $teamMembers->first()->name }}</strong> has been selected for an <span class="emphasis">exclusive online course</span>
                @else
                    <strong>{{ $teamMembers->count() }} of your team members</strong> have been selected for an <span class="emphasis">exclusive online course</span>
                @endif
                designed to enhance their skills and support professional growth.
            </p>
        </div>

        <div class="mandatory-alert">
            <h3>⚠️ Important - Mandatory Completion Required</h3>
            <p><span class="highlight">Completing this course is mandatory</span> for
                @if($teamMembers->count() === 1)
                    your team member,
                @else
                    all assigned team members,
                @endif
                and their progress will be <strong>tracked and reflected in their personal development section of the monthly evaluation</strong>.
            </p>
            <p><strong>Please ensure that
                    @if($teamMembers->count() === 1)
                        {{ $teamMembers->first()->name }} has
                    @else
                        all assigned team members have
                    @endif
                    the necessary time and support to complete the course successfully
                    @if($hasDeadline)
                        <span class="highlight">by {{ $deadlineDate ?? 'the deadline' }}</span>
                    @endif
                    .</strong>
            </p>
        </div>

        <p>Course assignment made by <strong>{{ $assignedBy->name }}</strong>.</p>

        <div class="course-info">
            <h2>📚 {{ $course->name }}</h2>

            @if($course->description)
                <p><strong>Description:</strong> {{ $course->description }}</p>
            @endif

            @if($course->difficulty_level)
                <p><strong>Difficulty Level:</strong> {{ ucfirst($course->difficulty_level) }}</p>
            @endif

            @if($course->estimated_duration)
                <p><strong>Estimated Duration:</strong> {{ $course->estimated_duration }} minutes</p>
            @endif

            <p><strong>Assignment Date:</strong> {{ now()->format('M d, Y') }}</p>

            @if($hasDeadline)
                <p><strong>Course Deadline:</strong>
                    <span class="deadline-{{ $deadlineStatus === 'overdue' ? 'overdue' : ($deadlineStatus === 'due_today' || $deadlineStatus === 'due_soon' ? 'urgent' : 'normal') }}">
                        {{ $deadlineDate ?? 'Not set' }}
                    </span>
                </p>
                <p><strong>Time Remaining:</strong>
                    @if($daysUntilDeadline !== null)
                        <span class="deadline-{{ $daysUntilDeadline < 0 ? 'overdue' : ($daysUntilDeadline <= 1 ? 'urgent' : 'normal') }}">
                            @if($daysUntilDeadline < 0)
                                Overdue by {{ abs($daysUntilDeadline) }} day{{ abs($daysUntilDeadline) !== 1 ? 's' : '' }}
                            @elseif($daysUntilDeadline === 0)
                                Due TODAY
                            @else
                                {{ $daysUntilDeadline }} day{{ $daysUntilDeadline !== 1 ? 's' : '' }} remaining
                            @endif
                        </span>
                    @endif
                </p>
            @endif

            <p><strong>Course Type:</strong> Self-paced online learning with mandatory completion</p>
        </div>

        <div class="team-members">
            <h3>🎯 Selected Team Members:</h3>
            @foreach($teamMembers as $member)
                <div class="member">
                    <strong>{{ $member->name }}</strong> - <span class="emphasis">Exclusive Selection</span><br>
                    <small>📧 {{ $member->email }}</small>
                    @if($member->department)
                        <br><small>🏢 {{ $member->department->name }}</small>
                    @endif
                    <br><small style="color: #dc2626; font-weight: bold;">⚠️ Mandatory completion required</small>
                    @if($hasDeadline && $deadlineDate)
                        <br><small style="color: #dc2626; font-weight: bold;">📅 Due: {{ $deadlineDate }}</small>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="manager-actions">
            <h3>📋 Your Role as Manager - Enhanced Support Required
                @if($hasDeadline && ($deadlineStatus === 'overdue' || $deadlineStatus === 'due_today' || $deadlineStatus === 'due_soon'))
                    <span style="color: #dc2626;">(URGENT ACTION NEEDED)</span>
                @endif
            </h3>
            <ul>
                <li><strong>Ensure Time Allocation:</strong> Guarantee adequate time for course completion
                    @if($hasDeadline)
                        <span style="color: #dc2626;">- DEADLINE: {{ $deadlineDate ?? 'Not set' }}</span>
                    @endif
                </li>
                <li><strong>Monitor Progress:</strong> Follow up
                    @if($deadlineStatus === 'overdue' || $deadlineStatus === 'due_today')
                        <strong style="color: #dc2626;">IMMEDIATELY</strong>
                    @elseif($deadlineStatus === 'due_soon')
                        <strong style="color: #f59e0b;">DAILY</strong>
                    @else
                        regularly
                    @endif
                    due to mandatory requirement</li>
                <li><strong>Provide Support:</strong> Assist with any course-related challenges</li>
                <li><strong>Career Integration:</strong> Discuss how learning applies to their role and growth</li>
                <li><strong>Completion Recognition:</strong> Acknowledge successful completion for evaluation purposes</li>
                <li><strong>Evaluation Documentation:</strong> Prepare to include completion in performance reviews</li>
                @if($hasDeadline && ($deadlineType ?? 'flexible') === 'strict')
                    <li><strong style="color: #dc2626;">Critical:</strong> Course access will be blocked after deadline - ensure completion beforehand</li>
                @endif
            </ul>
        </div>

        <p><strong>📊 Course Details:</strong></p>
        <ul>
            <li>Self-paced online learning with tracking</li>
            <li>Progress automatically monitored for evaluation</li>
            <li>Available 24/7 from any device</li>
            <li>Completion certificate provided</li>
            <li><strong>Mandatory completion impacts performance evaluation</strong></li>
            @if($hasDeadline)
                <li><strong style="color: #dc2626;">Deadline: {{ $deadlineDate ?? 'Not set' }}</strong></li>
            @endif
        </ul>

        <p>The selected team
            @if($teamMembers->count() === 1)
                member has
            @else
                members have
            @endif
            been notified directly about this exclusive opportunity and can start immediately. Their progress will be closely tracked for both learning outcomes and evaluation purposes.</p>

        <p><strong>This exclusive selection reflects our investment in their professional development and career advancement.</strong></p>

        <p>If you have any questions about this assignment or need support in facilitating completion, please contact <strong>{{ $assignedBy->name }}</strong>.</p>

        <p>Best regards,<br><strong>Learning & Development Team</strong></p>
    </div>

    <div class="footer">
        <p>🎓 Supporting professional growth through exclusive learning opportunities</p>
        <p>This notification was sent automatically when course assignments were made.</p>
        @if($hasDeadline)
            <p style="color: #dc2626; font-weight: bold;">⏰ Remember: Course deadline is {{ $deadlineDate ?? 'Not set' }}</p>
        @endif
        <p>© {{ date('Y') }} {{ config('app.name') }}</p>
    </div>
</div>
</body>
</html>
