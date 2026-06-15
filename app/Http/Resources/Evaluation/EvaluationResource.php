<?php

namespace App\Http\Resources\Evaluation;

use App\Enums\PerformanceLevel;
use Illuminate\Http\Request;
use App\Http\Resources\BaseResource;

class EvaluationResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        $level = $this->performance_level;

        // Unified course field — resolved from course_type
        $course = null;
        if ($this->course_type === 'online') {
            $courseModel = $this->whenLoaded('courseOnline');
            if ($courseModel) {
                $course = ['id' => $courseModel->id, 'name' => $courseModel->name, 'type' => 'online'];
            }
        } else {
            $courseModel = $this->whenLoaded('course');
            if ($courseModel) {
                $course = ['id' => $courseModel->id, 'name' => $courseModel->name, 'type' => 'regular'];
            }
        }

        return [
            'id'          => $this->id,
            'course_type' => $this->course_type,
            'total_score' => $this->total_score,
            'user'        => $this->when($this->relationLoaded('user') && $this->user !== null, fn() => [
                'id'         => $this->user->id,
                'name'       => $this->user->name,
                'department' => $this->when($this->user->relationLoaded('department'), fn() => [
                    'id'   => optional($this->user->department)->id,
                    'name' => optional($this->user->department)->name,
                ]),
            ]),
            'department' => $this->when($this->relationLoaded('department'), fn() => [
                'id'   => $this->department?->id,
                'name' => $this->department?->name,
            ]),
            'course'            => $course,
            'performance_level' => $level ? PerformanceLevel::getMetaByLevel($level) : null,
            'history'           => EvaluationHistoryResource::collection($this->whenLoaded('histories')),
            'created_at'        => $this->created_at?->toISOString(),
            'updated_at'        => $this->updated_at?->toISOString(),
        ];
    }
}
