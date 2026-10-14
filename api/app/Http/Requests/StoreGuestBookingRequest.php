<?php

namespace App\Http\Requests;

use App\Models\LessonType;
use App\Services\WaiverService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/** Public guest booking. Messages match the old create_guest_booking RPC. */
class StoreGuestBookingRequest extends FormRequest
{
    private const DATETIME = 'date_format:Y-m-d\TH:i:s,Y-m-d H:i:s,Y-m-d\TH:i';

    public function rules(): array
    {
        return [
            'lesson_type_id' => ['required', 'string'],
            'start_time' => ['required', self::DATETIME],
            'end_time' => ['required', self::DATETIME],
            'customer.first_name' => ['required', 'string', 'max:100'],
            'customer.last_name' => ['required', 'string', 'max:100'],
            'customer.email' => ['required', 'email', 'max:255'],
            'customer.phone' => ['nullable', 'string', 'max:20'],
            'riders' => ['required', 'array', 'min:1'],
            'riders.*.id' => ['nullable', 'string', 'max:36'],
            'riders.*.first_name' => ['required', 'string', 'max:100'],
            'riders.*.last_name' => ['required', 'string', 'max:100'],
            'riders.*.date_of_birth' => ['nullable', 'date_format:Y-m-d'],
            ...WaiverService::rules(),
        ];
    }

    public function messages(): array
    {
        return [
            'lesson_type_id.required' => 'Missing required booking parameters',
            'start_time.required' => 'Missing required booking parameters',
            'end_time.required' => 'Missing required booking parameters',
            'customer.email.required' => 'Customer email is required',
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $lesson = LessonType::query()->whereKey($this->input('lesson_type_id'))->where('active', true)->first();
            if (! $lesson) {
                $validator->errors()->add('lesson_type_id', 'Lesson type not found or inactive');

                return;
            }
            $count = count($this->input('riders'));
            if ($count !== $lesson->required_rider_count) {
                $validator->errors()->add('riders', sprintf(
                    'This lesson requires exactly %s rider(s), but %s were provided',
                    $lesson->required_rider_count, $count,
                ));
            }
        }];
    }
}
