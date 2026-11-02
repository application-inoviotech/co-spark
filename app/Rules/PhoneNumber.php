<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PhoneNumber implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $phone = trim((string) $value);
        $digits = preg_replace('/\D/', '', $phone);
        if (! preg_match('/^\+?[0-9 ().-]+$/', $phone)
            || strlen($digits) < 10 || strlen($digits) > 15 || preg_match('/^0+$/', $digits)) {
            $fail('Enter a valid phone number with 10 to 15 digits.');
        }
    }
}
