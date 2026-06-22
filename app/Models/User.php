<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'department_id',
        'report_to',
        'user_level_tier_id',
        'login_token',
        'login_token_expires_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'login_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => 'string',
            'password' => 'hashed',
            'login_token_expires_at' => 'datetime',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function userLevelTier(): BelongsTo
    {
        return $this->belongsTo(UserLevelTier::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'report_to');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(User::class, 'report_to');
    }

    public function courseRegistrations(): HasMany
    {
        return $this->hasMany(CourseRegistration::class);
    }

    public function courseCompletions(): HasMany
    {
        return $this->hasMany(CourseCompletion::class);
    }

    public function courseAssignments(): HasMany
    {
        return $this->hasMany(CourseAssignment::class);
    }

    public function clockings(): HasMany
    {
        return $this->hasMany(Clocking::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isDirectManager(): bool
    {
        return ! $this->subordinates()->doesntExist();
    }

    public function hasSuperiors(): bool
    {
        return ! is_null($this->report_to);
    }

    public function hasSubordinates(): bool
    {
        return ! $this->subordinates()->doesntExist();
    }

    public function generateAudioLoginLink(int $audioId): string
    {
        $token = Str::random(64);
        $expiresAt = now()->addHours(24);

        $this->update([
            'login_token' => hash('sha256', $token),
            'login_token_expires_at' => $expiresAt,
        ]);

        return URL::temporarySignedRoute(
            'auth.audio-token-login',
            $expiresAt,
            [
                'user' => $this->id,
                'audio' => $audioId,
                'token' => $token,
            ]
        );
    }

    public function generateCourseLoginLink(int $courseId): string
    {
        $token     = Str::random(64);
        $expiresAt = now()->addHours(72);

        $this->update([
            'login_token'            => hash('sha256', $token),
            'login_token_expires_at' => $expiresAt,
        ]);

        return URL::temporarySignedRoute(
            'auth.course-token-login',
            $expiresAt,
            [
                'user'   => $this->id,
                'course' => $courseId,
                'token'  => $token,
            ]
        );
    }

    public function generateOnlineCourseLoginLink(int $courseOnlineId): string
    {
        $token     = Str::random(64);
        $expiresAt = now()->addHours(72);

        $this->update([
            'login_token'            => hash('sha256', $token),
            'login_token_expires_at' => $expiresAt,
        ]);

        return URL::temporarySignedRoute(
            'auth.online-course-token-login',
            $expiresAt,
            [
                'user'          => $this->id,
                'course_online' => $courseOnlineId,
                'token'         => $token,
            ]
        );
    }

    public function loginTokenExpired(): bool
    {
        if ($this->login_token_expires_at === null) {
            return true;
        }

        return now()->greaterThan($this->login_token_expires_at);
    }

    public function hasValidLoginToken(string $token): bool
    {
        if ($this->loginTokenExpired() || ! $this->login_token) {
            return false;
        }

        return hash_equals($this->login_token, hash('sha256', $token));
    }
}
