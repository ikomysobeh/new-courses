<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audio Assignment - Professional Development</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #7c3aed, #6d28d9); color: white; padding: 25px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { padding: 30px 25px; background: white; border-radius: 0 0 8px 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .audio-notice { background: linear-gradient(135deg, #fef3c7, #fde68a); border-left: 4px solid #f59e0b; padding: 20px; border-radius: 8px; margin: 25px 0; }
        .audio-info { background: #f8fafc; border: 2px solid #e2e8f0; padding: 25px; border-radius: 8px; margin: 25px 0; }
        .cta-button { display: inline-block; background: linear-gradient(135deg, #7c3aed, #6d28d9); color: white !important; padding: 15px 30px; text-decoration: none !important; border-radius: 8px; font-weight: bold; box-shadow: 0 2px 4px rgba(0,0,0,0.2); transition: transform 0.2s; }
        .cta-button:hover { transform: translateY(-2px); color: white !important; }
        .benefits-box { background: #f0f9ff; border-left: 4px solid #0ea5e9; padding: 20px; border-radius: 8px; margin: 25px 0; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 14px; background: #f8fafc; margin-top: 20px; border-radius: 8px; }
        h1 { margin: 0; font-size: 24px; }
        h2 { color: #1e293b; margin-bottom: 15px; }
        h3 { color: #374151; margin-bottom: 10px; }
        ul { padding-left: 20px; }
        li { margin-bottom: 8px; }
        .duration-badge { display: inline-block; background: #7c3aed; color: white; padding: 5px 12px; border-radius: 20px; font-size: 14px; margin-top: 10px; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>🎧 New Audio Assignment</h1>
        <p style="margin: 10px 0 0 0; font-size: 16px; opacity: 0.9;">Professional Development Content</p>
    </div>

    <div class="content">
        <p>Hi <strong>{{ $user->name }}</strong>,</p>

        <div class="audio-notice">
            <h3>🎵 You've Been Assigned New Audio Content!</h3>
            <p>A new audio learning material has been assigned to you. Please listen to it at your earliest convenience to enhance your professional development.</p>
        </div>

        <p>You have been assigned this audio by <strong>{{ $assignedBy->name }}</strong>.</p>

        <div class="audio-info">
            <h2>🎧 {{ $audioName }}</h2>

            @if($audioDescription)
                <p><strong>Description:</strong> {{ $audioDescription }}</p>
            @endif

            @if($audioDuration)
                <p><strong>Duration:</strong> <span class="duration-badge">{{ $audioDuration }}</span></p>
            @endif

            <p><strong>Assignment Date:</strong> {{ now()->format('M d, Y') }}</p>
        </div>

        <div class="benefits-box">
            <h3>💡 Why This Audio Matters</h3>
            <ul>
                <li><strong>Professional Growth:</strong> Enhances your knowledge and skills</li>
                <li><strong>Flexible Learning:</strong> Listen anytime, anywhere</li>
                <li><strong>Progress Tracking:</strong> Your listening progress is monitored</li>
            </ul>
        </div>

        <div style="text-align: center; margin: 30px 0;">
            @if($loginLink)
                <a href="{{ $loginLink }}" class="cta-button" style="color: #ffffff !important; text-decoration: none !important;">🎧 Listen Now</a>
            @else
                <a href="{{ url('/') }}" class="cta-button" style="color: #ffffff !important; text-decoration: none !important;">🎧 Listen Now</a>
            @endif
        </div>

        <p><strong>📋 Getting Started:</strong></p>
        <ul>
            <li>Click the button above to access the audio</li>
            <li>Your listening progress is automatically tracked</li>
            <li>You can pause and resume anytime</li>
        </ul>

        <p>If you have any questions about this assignment, please contact <strong>{{ $assignedBy->name }}</strong> or your direct manager.</p>

        <p>Happy listening!</p>
    </div>

    <div class="footer">
        <p>🎧 Enhance your skills through audio learning!</p>
        <p>This email was sent automatically by the Learning Management System.</p>
        <p>© {{ date('Y') }} {{ config('app.name') }}</p>
    </div>
</div>
</body>
</html>
