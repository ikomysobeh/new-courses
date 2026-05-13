<?php

namespace App\Http\Requests\Admin\OnlineCourse;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreOnlineCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'               => ['required', 'string', 'max:255'],
            'description'        => ['nullable', 'string'],
            'image_path'         => ['nullable', 'string', 'max:500'],
            'level'              => ['nullable', 'in:beginner,intermediate,advanced'],
            'estimated_duration' => ['nullable', 'integer', 'min:1'],
            'status'             => ['nullable', 'in:draft,published,archived'],
            'is_active'          => ['nullable', 'boolean'],
            'deadline'           => ['nullable', 'date'],

            // Modules array
            'modules'                              => ['nullable', 'array'],
            'modules.*.name'                       => ['required_with:modules', 'string', 'max:255'],
            'modules.*.description'                => ['nullable', 'string'],
            'modules.*.order_number'               => ['required_with:modules', 'integer', 'min:1'],
            'modules.*.estimated_duration'         => ['nullable', 'integer', 'min:1'],
            'modules.*.has_quiz'                   => ['nullable', 'boolean'],
            'modules.*.quiz_required'              => ['nullable', 'boolean'],

            // Contents nested inside modules
            'modules.*.contents'                          => ['nullable', 'array'],
            'modules.*.contents.*.title'                  => ['required_with:modules.*.contents', 'string', 'max:255'],
            'modules.*.contents.*.description'            => ['nullable', 'string'],
            'modules.*.contents.*.content_type'           => ['required_with:modules.*.contents', 'in:video,pdf'],
            'modules.*.contents.*.order_number'           => ['required_with:modules.*.contents', 'integer', 'min:1'],
            'modules.*.contents.*.video_id'               => ['nullable', 'integer', 'exists:videos,id'],
            'modules.*.contents.*.duration'               => ['nullable', 'integer', 'min:0'],
            'modules.*.contents.*.thumbnail_path'         => ['nullable', 'string', 'max:500'],
            'modules.*.contents.*.is_required'            => ['nullable', 'boolean'],
            'modules.*.contents.*.is_active'              => ['nullable', 'boolean'],
            'modules.*.contents.*.attachment_path'        => ['nullable', 'string', 'max:500'],
            'modules.*.contents.*.attachment_name'        => ['nullable', 'string', 'max:255'],
            'modules.*.contents.*.attachment_extension'   => ['nullable', 'string', 'max:20'],
            'modules.*.contents.*.pdf'                    => ['nullable', 'array'],
            'modules.*.contents.*.pdf.file_path'          => ['nullable', 'string', 'max:500'],
            'modules.*.contents.*.pdf.pdf_page_count'     => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $modules = $this->input('modules', []);

            $moduleOrders = [];
            foreach ($modules as $mIdx => $module) {
                $mOrder = $module['order_number'] ?? null;
                if ($mOrder !== null) {
                    if (in_array($mOrder, $moduleOrders)) {
                        $v->errors()->add("modules.{$mIdx}.order_number", 'Module order numbers must be unique within the course.');
                    } else {
                        $moduleOrders[] = $mOrder;
                    }
                }

                $contentOrders = [];
                foreach ($module['contents'] ?? [] as $cIdx => $content) {
                    $type = $content['content_type'] ?? null;

                    if ($type === 'video' && empty($content['video_id'])) {
                        $v->errors()->add("modules.{$mIdx}.contents.{$cIdx}.video_id", 'video_id is required when content_type is video.');
                    }

                    if ($type === 'pdf' && empty($content['pdf']['file_path'])) {
                        $v->errors()->add("modules.{$mIdx}.contents.{$cIdx}.pdf.file_path", 'pdf.file_path is required when content_type is pdf.');
                    }

                    $cOrder = $content['order_number'] ?? null;
                    if ($cOrder !== null) {
                        if (in_array($cOrder, $contentOrders)) {
                            $v->errors()->add("modules.{$mIdx}.contents.{$cIdx}.order_number", 'Content order numbers must be unique within the module.');
                        } else {
                            $contentOrders[] = $cOrder;
                        }
                    }
                }
            }
        });
    }
}
