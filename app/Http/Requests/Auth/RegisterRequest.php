<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/** SOW: Member Registration — personal, address, password, SMS consent and plan. */
class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['email' => strtolower(trim((string) $this->input('email')))]);
    }

    public function rules(): array
    {
        return [
            'plan' => ['required', Rule::in(array_keys(config('membership.plans')))],
            'first' => ['required', 'string', 'max:80'],
            'last' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email:filter', 'max:255', Rule::unique('users', 'email')],
            'phone' => ['required', 'string', 'max:40', new \App\Rules\PhoneNumber],
            'street' => ['required', 'string', 'max:255'],
            'street2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:120'],
            'stt' => ['required', Rule::in(config('cospark.us_states'))],
            'zip' => ['required', 'regex:/^\d{5}$/'],
            'pw' => ['required', 'confirmed', Password::min(8)->numbers()],
            'sms' => ['boolean'],
            'tos' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.email' => 'Email format is incorrect.',
            'email.unique' => 'An account with this email already exists. Sign in instead.',
            'zip.regex' => 'Enter a 5-digit ZIP code.',
            'pw.confirmed' => "Passwords don't match.",
        ];
    }

    public function attributes(): array
    {
        return ['first' => 'first name', 'last' => 'last name', 'stt' => 'state', 'pw' => 'password'];
    }
}
