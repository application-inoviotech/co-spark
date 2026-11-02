<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;

class VehicleIdentificationNumber implements DataAwareRule, ValidationRule
{
    private array $data = [];

    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $year = Arr::get($this->data, preg_replace('/\.vin$/', '.year', $attribute));
        $classic = is_numeric($year) && (int) $year < 1981;
        $pattern = $classic ? '/^[A-Z0-9]{1,17}$/i' : '/^[A-HJ-NPR-Z0-9]{17}$/i';
        if (! preg_match($pattern, (string) $value)) {
            $fail($classic ? 'Enter a valid vehicle identifier (up to 17 letters and digits).' : 'Enter a 17-character VIN without I, O or Q, or leave it blank.');
        }
    }
}
