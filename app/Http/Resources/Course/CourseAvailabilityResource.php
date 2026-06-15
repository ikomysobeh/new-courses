<?php

namespace App\Http\Resources\Course;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Resources\BaseResource;

class CourseAvailabilityResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        $days = [];
        if ($this->days_of_week) {
            $days = array_values(array_filter(explode(',', $this->days_of_week)));
        }

        $capacity = (int) ($this->capacity ?? 0);
        $usedSeats = $this->countUniqueOccupantsForAvailability();
        $availableSpots = $capacity > 0 ? max(0, $capacity - $usedSeats) : 0;

        return [
            'id'                       => $this->id,
            'course_id'                => $this->course_id,
            'start_date'               => $this->start_date?->toIso8601String(),
            'end_date'                 => $this->end_date?->toIso8601String(),
            'capacity'                 => $this->capacity,
            'sessions'                 => $this->sessions,
            'available_spots'          => $availableSpots,
            'is_full'                  => $capacity > 0 ? $availableSpots <= 0 : false,
            'duration_weeks'           => $this->duration_weeks,
            'status'                   => $this->status,
            'notes'                    => $this->notes,
            'days_of_week'             => $days,
            'session_time_shift_1'     => $this->session_time_shift_1,
            'session_time_shift_2'     => $this->session_time_shift_2,
            'session_time_shift_3'     => $this->session_time_shift_3,
            'session_duration_minutes' => $this->session_duration_minutes,
            'created_at'               => $this->created_at?->toIso8601String(),
            'updated_at'               => $this->updated_at?->toIso8601String(),
        ];
    }

    private function countUniqueOccupantsForAvailability(): int
    {
        $assignedUserIds = DB::table('course_assignments')
            ->where('course_availability_id', $this->id)
            ->pluck('user_id');

        $registeredUserIds = DB::table('course_registrations')
            ->where('course_availability_id', $this->id)
            ->pluck('user_id');

        return $assignedUserIds
            ->concat($registeredUserIds)
            ->unique()
            ->count();
    }
}