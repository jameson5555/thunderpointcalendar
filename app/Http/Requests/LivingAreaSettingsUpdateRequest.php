<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LivingAreaSettingsUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'booking_message' => ['nullable', 'string', 'max:2000'],
        ];
    }
}