<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CandidateProfileRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:120'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20', 'regex:/^[+]?[0-9][0-9\s-]{7,18}$/'],
            'date_of_birth' => ['sometimes', 'nullable', 'date', 'before:today'],
            'gender' => ['sometimes', 'nullable', 'in:male,female,other'],
            'location' => ['sometimes', 'nullable', 'string', 'max:190'],
            'city' => ['sometimes', 'nullable', 'string', 'max:100'],
            'state' => ['sometimes', 'nullable', 'string', 'max:100'],
            'bio' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'current_job_title' => ['sometimes', 'nullable', 'string', 'max:190'],
            'total_experience' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:60'],
            'profile_data' => ['sometimes', 'nullable', 'array'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $pd = $this->input('profile_data');
            if (is_array($pd) && strlen(json_encode($pd)) > 200000) {
                $v->errors()->add('profile_data', 'Profile data is too large.');
            }
        });
    }

    public function messages(): array
    {
        return ['phone.regex' => 'Enter a valid phone number (digits, optional + prefix).'];
    }
}
