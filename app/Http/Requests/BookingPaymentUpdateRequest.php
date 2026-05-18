<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BookingPaymentUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'payment_method' => ['required', Rule::in(array_keys(config('thunderpoint.payment_methods')))],
            'payment_reference' => ['nullable', 'string', 'max:255'],
        ];
    }
}