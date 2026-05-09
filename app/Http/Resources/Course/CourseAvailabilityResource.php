<?php

namespace App\Http\Resources\Course;

use App\Models\CourseAssignment;
use App\Models\CourseRegistration;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseAvailabilityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $days = [];
        if ($this->days_of_week) {
            $days = array_values(array_filter(explode(',', $this->days_of_week)));
        }

        $capacity = (int) ($this->capacity ?? 0);
        $assignedCount = CourseAssignment::query()
            ->where('course_availability_id', $this->id)
            ->count();
        $registeredCount = CourseRegistration::query()
            ->where('course_availability_id', $this->id)
            ->count();
        $usedSeats = $assignedCount + $registeredCount;
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
}