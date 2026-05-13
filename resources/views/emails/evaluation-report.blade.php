<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $mailSubject }}</title>
    <style>
        body { font-family: Arial, sans-serif; color: #333; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { padding: 8px 12px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #f5f5f5; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; color: #fff; }
    </style>
</head>
<body>
    <p>Hello {{ $manager->name }},</p>

    <p>{{ $mailMessage }}</p>

    @if($startDate || $endDate)
        <p><strong>Report Period:</strong>
            {{ $startDate ?? 'N/A' }} — {{ $endDate ?? 'N/A' }}
        </p>
    @endif

    @if($evaluations->isNotEmpty())
        <table>
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Course</th>
                    <th>Course Type</th>
                    <th>Total Score</th>
                    <th>Performance Level</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($evaluations as $eval)
                    @php
                        $level = \App\Enums\PerformanceLevel::getMetaByLevel($eval->performance_level ?? 4);
                        $courseName = $eval->course_type === 'online'
                            ? optional($eval->courseOnline)->name
                            : optional($eval->course)->name;
                    @endphp
                    <tr>
                        <td>{{ optional($eval->user)->name }}</td>
                        <td>{{ $courseName }}</td>
                        <td>{{ ucfirst($eval->course_type) }}</td>
                        <td>{{ $eval->total_score }}</td>
                        <td>
                            <span class="badge" style="background-color: {{ $level['color'] }};">
                                {{ $level['label'] }}
                            </span>
                        </td>
                        <td>{{ $eval->created_at?->format('Y-m-d') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p>No evaluations found for this period.</p>
    @endif

    <p style="margin-top: 24px; color: #888; font-size: 12px;">
        This is an automated message. Please do not reply.
    </p>
</body>
</html>
