<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BookingStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'living_area_ids' => ['required', 'array', 'min:1'],
            'living_area_ids.*' => ['required', 'integer', 'distinct', 'exists:living_areas,id'],
            'guest_name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'note' => ['nullable', 'string', 'max:2000'],
            'payment_method' => ['nullable', Rule::in(array_keys(config('thunderpoint.payment_methods')))],
            'payment_reference' => ['nullable', 'string', 'max:255'],
            'book_as_draft' => ['nullable', 'boolean'],
            'return_month' => ['nullable', 'date_format:Y-m'],
            'form_context' => ['nullable', Rule::in(['calendar-create', 'calendar-edit'])],
            'editing_group' => ['nullable', 'string', 'max:255'],
        ];
    }
}
