<!DOCTYPE html>
<html>
<head>
    <title>{{ $emailSubject ?? 'Personal Development Evaluation Results' }}</title>
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
            max-width: 800px;
            margin: 20px auto;
            background: #ffffff;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
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
        .greeting {
            margin-bottom: 20px;
            font-size: 16px;
        }
        .intro-text {
            background: #f8f9ff;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #667eea;
            font-style: italic;
        }
        .custom-message {
            background: #e7f3ff;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #2196F3;
            border-radius: 4px;
        }
        .data-section {
            margin: 30px 0;
        }
        .data-section h2 {
            color: #667eea;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        .employee-section {
            margin: 30px 0;
            padding: 20px;
            border: 1px solid #eee;
            border-radius: 8px;
            background: #f9f9f9;
        }
        .employee-header {
            background: #667eea;
            color: white;
            padding: 15px;
            margin: -20px -20px 20px -20px;
            border-radius: 8px 8px 0 0;
        }
        .employee-header h3 {
            margin: 0;
            font-size: 18px;
        }
        .employee-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }
        .employee-info div {
            margin: 5px 0;
        }
        .overall-summary {
            background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
            border: 2px solid #667eea;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .overall-summary h4 {
            color: #667eea;
            margin: 0 0 15px 0;
            font-size: 16px;
        }
        .stats-grid {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        .stat-card {
            flex: 1;
            min-width: 180px;
            background: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stat-value {
            font-size: 28px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }
        .stat-label {
            color: #666;
            font-size: 13px;
        }
        .evaluation-details {
            margin: 20px 0;
            background: white;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #667eea;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        th {
            background: #667eea;
            color: white;
            padding: 12px 8px;
            text-align: left;
            font-weight: 500;
            font-size: 14px;
        }
        td {
            padding: 10px 8px;
            border-bottom: 1px solid #eee;
            font-size: 13px;
        }
        tr:hover {
            background-color: #f9f9f9;
        }
        .summary-table {
            background: #f8f9ff;
            border-radius: 5px;
            padding: 15px;
            margin: 15px 0;
        }
        .summary-table h4 {
            margin: 0 0 10px 0;
            color: #667eea;
        }
        .score-highlight {
            font-weight: bold;
            color: #667eea;
        }
        .performance-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 12px;
            text-align: center;
        }
        .outstanding-badge {
            background-color: #d1fae5;
            color: #047857;
            border: 1px solid #10b981;
        }
        .reliable-badge {
            background-color: #dbeafe;
            color: #1e40af;
            border: 1px solid #3b82f6;
        }
        .developing-badge {
            background-color: #fef3c7;
            color: #92400e;
            border: 1px solid #f59e0b;
        }
        .underperforming-badge {
            background-color: #fee2e2;
            color: #b91c1c;
            border: 1px solid #ef4444;
        }
        .signature {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9ff;
            border-radius: 5px;
        }
        .signature-name {
            font-weight: bold;
            color: #667eea;
        }
        .signature-title {
            color: #666;
            font-size: 14px;
        }
        .footer {
            background: #f8f9fa;
            padding: 25px;
            text-align: center;
            color: #666;
            border-top: 3px solid #667eea;
        }
    </style>
</head>
<body>
<div class="email-container">
    <div class="header">
        <h1>Personal Development Evaluation Results for Your Team</h1>
    </div>

    <div class="content">
        <div class="greeting">
            <strong>Dear {{ $manager['name'] }},</strong>
        </div>

        <p>I hope this message finds you well.</p>

        <div class="intro-text">
            As part of our ongoing commitment to supporting employee growth, I am sharing with you the detailed evaluation results for your team members' Personal Development section. This comprehensive report includes both overall scores and detailed breakdowns by evaluation category and type.
        </div>

        @if($customMessage)
            <div class="custom-message">
                <h3 style="margin-top: 0; color: #2196F3;">Additional Message:</h3>
                <p style="margin-bottom: 0;">{{ $customMessage }}</p>
            </div>
        @endif

        <div class="data-section">
            <h2>Team Member Evaluation Summary - {{ $reportMonth ?? now()->format('F Y') }}</h2>

            <table>
                <thead>
                <tr>
                    <th>Employee Name</th>
                    <th>Department</th>
                    <th>Course</th>
                    <th>Score</th>
                    <th>Performance Level</th>
                    <th>Date</th>
                </tr>
                </thead>
                <tbody>
                @if(isset($detailedEvaluations) && !empty($detailedEvaluations))
                    @foreach($detailedEvaluations as $employeeData)
                        @foreach($employeeData['evaluations'] as $evaluation)
                            <tr>
                                <td><strong>{{ $employeeData['employee']['name'] }}</strong></td>
                                <td>{{ $employeeData['employee']['department'] }}</td>
                                <td>{{ $evaluation['course'] }}</td>
                                <td class="score-highlight">{{ $evaluation['total_score'] }}</td>
                                <td>
                                    @php
                                        $score = $evaluation['total_score'];
                                        if ($score >= 13) {
                                            $performanceClass = 'outstanding-badge';
                                            $performanceLabel = 'Outstanding';
                                        } elseif ($score >= 10) {
                                            $performanceClass = 'reliable-badge';
                                            $performanceLabel = 'Reliable';
                                        } elseif ($score >= 7) {
                                            $performanceClass = 'developing-badge';
                                            $performanceLabel = 'Developing';
                                        } elseif ($score >= 0) {
                                            $performanceClass = 'underperforming-badge';
                                            $performanceLabel = 'Underperforming';
                                        } else {
                                            $performanceClass = '';
                                            $performanceLabel = 'Not Rated';
                                        }
                                    @endphp
                                    <span class="performance-badge {{ $performanceClass }}">{{ $performanceLabel }}</span>
                                </td>
                                <td>{{ $evaluation['created_at'] }}</td>
                            </tr>
                        @endforeach
                    @endforeach
                @else
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 20px; color: #666;">
                            No evaluations found for courses assigned in {{ $reportMonth ?? now()->format('F Y') }}
                        </td>
                    </tr>
                @endif
                </tbody>
            </table>
        </div>

        @if(isset($detailedEvaluations) && !empty($detailedEvaluations))
            <div class="data-section">
                <h2>Detailed Evaluation Breakdown</h2>

                @foreach($detailedEvaluations as $employeeData)
                    <div class="employee-section">
                        <div class="employee-header">
                            <h3>{{ $employeeData['employee']['name'] }}</h3>
                        </div>

                        <div class="employee-info">
                            <div><strong>Department:</strong> {{ $employeeData['employee']['department'] }}</div>
                            <div><strong>Level:</strong> {{ $employeeData['employee']['level'] }}</div>
                            <div><strong>Email:</strong> {{ $employeeData['employee']['email'] }}</div>
                        </div>

                        <div class="overall-summary">
                            <h4>&#128202; Overall Performance Summary</h4>
                            <div class="stats-grid">
                                <div class="stat-card">
                                    <div class="stat-value">{{ $employeeData['overall_average'] }}</div>
                                    <div class="stat-label">Overall Average Score</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-value">{{ $employeeData['total_evaluations'] }}</div>
                                    <div class="stat-label">Total Courses</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-value">{{ $employeeData['total_evaluations'] }}</div>
                                    <div class="stat-label">Total Evaluations</div>
                                </div>
                            </div>
                        </div>

                        @foreach($employeeData['evaluations'] as $index => $evaluation)
                            <div class="evaluation-details">
                                <div class="summary-table">
                                    <h4>{{ $evaluation['course'] }} - Evaluation Summary</h4>
                                    <div style="display: flex; justify-content: space-between; flex-wrap: wrap;">
                                        <div><strong>Course Average:</strong> <span class="score-highlight">{{ $employeeData['course_averages'][$index] ?? 'N/A' }}%</span></div>
                                        <div><strong>Total Score:</strong> <span class="score-highlight">{{ $evaluation['total_score'] }}</span></div>
                                        <div>
                                            <strong>Performance Level:</strong>
                                            @php
                                                $score = $evaluation['total_score'];
                                                if ($score >= 13) {
                                                    $performanceClass = 'outstanding-badge';
                                                    $performanceLabel = 'Outstanding';
                                                } elseif ($score >= 10) {
                                                    $performanceClass = 'reliable-badge';
                                                    $performanceLabel = 'Reliable';
                                                } elseif ($score >= 7) {
                                                    $performanceClass = 'developing-badge';
                                                    $performanceLabel = 'Developing';
                                                } elseif ($score >= 0) {
                                                    $performanceClass = 'underperforming-badge';
                                                    $performanceLabel = 'Underperforming';
                                                } else {
                                                    $performanceClass = '';
                                                    $performanceLabel = 'Not Rated';
                                                }
                                            @endphp
                                            <span class="performance-badge {{ $performanceClass }}">{{ $performanceLabel }}</span>
                                        </div>
                                        <div><strong>Date:</strong> {{ $evaluation['created_at'] }}</div>
                                    </div>
                                </div>

                                @if(!empty($evaluation['detailed_scores']))
                                    <h4 style="color: #667eea; margin: 15px 0 10px 0;">Detailed Score Breakdown:</h4>
                                    <table>
                                        <thead>
                                        <tr>
                                            <th>Category</th>
                                            <th>Evaluation Type</th>
                                            <th>Score</th>
                                            <th>Comments</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($evaluation['detailed_scores'] as $detail)
                                            <tr>
                                                <td><strong>{{ $detail['category_name'] }}</strong></td>
                                                <td>{{ $detail['type_name'] }}</td>
                                                <td class="score-highlight">{{ $detail['score'] }}</td>
                                                <td>{{ $detail['comments'] }}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        @endif

        <div style="background: #f8f9ff; padding: 20px; border-radius: 5px; margin: 25px 0;">
            <p style="margin: 0;"><strong>Your Role in Development:</strong></p>
            <p style="margin: 10px 0 0 0;">We greatly appreciate your dedication to fostering the professional development of your team. Your role as a direct manager is essential in creating an environment where employees feel supported, motivated, and empowered to grow. Use this detailed breakdown to have meaningful conversations with your team members about their strengths and areas for improvement.</p>
        </div>

        <p>If you have any questions about the results, or would like guidance on how best to use this information during your discussions, please don't hesitate to reach out.</p>

        <p>Thank you as always for your leadership and commitment.</p>

        <div class="signature">
            <div class="signature-name">Best regards,</div>
            <div class="signature-name">Harry Prescott</div>
            <div class="signature-title">Instructor</div>
            <div class="signature-title">The Training And Development Department</div>
            <div style="margin-top: 10px; font-size: 12px; color: #999;">
                This report was generated automatically on {{ now()->format('F j, Y') }}
            </div>
        </div>
    </div>

    <div class="footer">
        <p style="margin: 0; font-size: 14px;">
            This is an automated message from the Employee Development System.<br>
            For technical support, please contact the IT department.
        </p>
    </div>
</div>
</body>
</html>
