<?php

namespace App\Http\Requests;

class AdminBookingUpdateRequest extends BookingStoreRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccessAdmin() ?? false;
    }
}