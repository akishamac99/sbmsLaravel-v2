<?php

namespace App\Http\Requests\Portal;

use App\Support\TimeSlots;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('staff') !== null;
    }

    public function rules(): array
    {
        return [
            'booking_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'booking_time' => ['required', TimeSlots::openOn($this->input('booking_date'))],
            'staff_id' => ['nullable', 'integer', 'exists:salon_staff,staffID'],
            'special_request' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return ['booking_date' => 'date', 'booking_time' => 'time', 'staff_id' => 'staff member', 'special_request' => 'notes'];
    }
}
