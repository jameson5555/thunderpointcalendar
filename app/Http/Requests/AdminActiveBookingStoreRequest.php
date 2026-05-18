<?php

namespace App\Http\Requests;

class AdminActiveBookingStoreRequest extends BookingStoreRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccessAdmin() ?? false;
    }
}