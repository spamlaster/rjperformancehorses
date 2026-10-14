<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string', 'current_password:web'],
            'password' => ['required', 'string', 'min:12', 'max:72', 'confirmed', 'different:current_password'],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.current_password' => 'Your current password is incorrect.',
            'password.min' => 'Use at least 12 characters for your new password.',
            'password.max' => 'Use no more than 72 characters for your new password.',
            'password.confirmed' => 'The new passwords do not match.',
            'password.different' => 'Choose a new password different from your current password.',
        ];
    }
}
