<?php

namespace App\Http\Resources\User\OnlineCourse;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserCourseDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data    = $this->resource;
        $course  = $data['course'];
        $modules = $data['modules'];
        $progress = $data['progress'];

        return [
            'id'              => $course->id,
            'title'           => $course->name,
            'description'     => $course->description,
            'thumbnail_url'   => $course->image_path
                ? asset('storage/' . ltrim(str_replace('\\', '/', $course->image_path), '/'))
                : null,
            'has_certificate' => false,
            'progress'        => $progress ? [
                'progress_percentage'     => $progress->progress_percentage,
                'status'                  => $progress->status,
                'completed_content_items' => $progress->completed_content_items,
                'total_content_items'     => $progress->total_content_items,
                'started_at'              => $progress->started_at,
                'completed_at'            => $progress->completed_at,
                'last_accessed_at'        => $progress->last_accessed_at,
            ] : null,
            'modules' => collect($modules)->map(function ($mod) {
                return [
                    'id'           => $mod['module']->id,
                    'title'        => $mod['module']->name,
                    'description'  => $mod['module']->description,
                    'order_number' => $mod['module']->order_number,
                    'has_quiz'     => $mod['module']->has_quiz,
                    'is_required'  => true,
                    'is_unlocked'  => $mod['is_unlocked'],
                    'is_completed' => $mod['is_completed'],
                    'quiz_status'  => $mod['quiz_status'],
                    'content'      => collect($mod['content'])->map(function ($c) {
                        return [
                            'id'               => $c['item']->id,
                            'title'            => $c['item']->title,
                            'content_type'     => $c['item']->content_type,
                            'duration_seconds' => $c['item']->duration,
                            'order_number'     => $c['item']->order_number,
                            'is_required'      => $c['item']->is_required,
                            'is_unlocked'      => $c['is_unlocked'],
                            'progress'         => $c['progress'] ? [
                                'playback_position'     => $c['progress']->playback_position,
                                'completion_percentage' => $c['progress']->completion_percentage,
                                'is_completed'          => $c['progress']->is_completed,
                            ] : null,
                        ];
                    })->values()->all(),
                ];
            })->values()->all(),
        ];
    }
}
