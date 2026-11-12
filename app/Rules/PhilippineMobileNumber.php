<?php

namespace App\Rules;

use App\Support\PhoneNumber;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** An optional field: empty passes (pair it with "nullable"), anything typed must be a real Philippine mobile number. */
class PhilippineMobileNumber implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (PhoneNumber::isBlank(is_string($value) ? $value : null)) {
            return;
        }

        $normalized = PhoneNumber::normalize(is_string($value) ? $value : null);

        if ($normalized === null) {
            $fail('Enter a valid mobile number, like '.PhoneNumber::EXAMPLE.' or +639171234567.');

            return;
        }

        if (! PhoneNumber::hasKnownPrefix($normalized)) {
            $fail('That number does not start like a Philippine mobile number. Check the first four digits.');

            return;
        }

        if (PhoneNumber::looksMadeUp($normalized)) {
            $fail('That number does not look real. Please enter your own mobile number.');
        }
    }
}
