<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Audio;
use App\Models\AudioAssignment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AudioTokenLoginController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        abort_unless($request->hasValidSignature(), 403, 'Invalid or expired signed URL.');

        $userId = (int) $request->query('user');
        $audioId = (int) $request->query('audio');
        $token = (string) $request->query('token', '');

        $user = User::query()->findOrFail($userId);
        Audio::query()->findOrFail($audioId);

        abort_unless($token !== '' && $user->hasValidLoginToken($token), 403, 'Invalid or expired login token.');

        $isAssigned = AudioAssignment::query()
            ->where('user_id', $user->id)
            ->where('audio_id', $audioId)
            ->exists();

        abort_unless($user->isAdmin() || $isAssigned, 403, 'Audio is not assigned to this user.');

        $plainToken = $user->createToken('audio-email-login')->plainTextToken;

        $user->update([
            'login_token'            => null,
            'login_token_expires_at' => null,
            'last_login_at'          => now(),
        ]);

        $frontendBase = rtrim((string) config('app.frontend_url'), '/');
        $redirectUrl = $frontendBase . '/audio/' . $audioId
            . '?token=' . urlencode($plainToken)
            . '&audio=' . $audioId;

        return redirect()->away($redirectUrl);
    }
}
