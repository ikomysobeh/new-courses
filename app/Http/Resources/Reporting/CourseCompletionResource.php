<?php

namespace App\Http\Resources\Reporting;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class CourseCompletionResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        $daysToComplete = null;
        if ($this->registered_at && $this->completed_at) {
            $daysToComplete = Carbon::parse($this->registered_at)
                ->diffInDays(Carbon::parse($this->completed_at));
        }

        return [
            'id'               => $this->id,
            'user_id'          => $this->user_id,
            'user_name'        => $this->user_name,
            'user_email'       => $this->user_email,
            'department_id'    => $this->department_id,
            'department_name'  => $this->department_name,
            'course_id'        => $this->course_id,
            'course_name'      => $this->course_name,
            'registered_at'    => $this->registered_at,
            'completed_at'     => $this->completed_at,
            'days_to_complete' => $daysToComplete,
            'rating'           => $this->rating !== null ? (int) $this->rating : null,
            'feedback'         => $this->feedback,
        ];
    }
}
