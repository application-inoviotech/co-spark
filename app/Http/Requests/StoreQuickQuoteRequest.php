<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

/** Home page "Get a quote" mini form: coverage, contact and address only. */
class StoreQuickQuoteRequest extends StepFormRequest
{
    public const COVERAGE = ['Home', 'Auto', 'Health', 'Commercial', 'Multiple Policies'];

    protected array $columns = ['cov', 'first', 'last', 'email', 'phone', 'street', 'city', 'st', 'zip'];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cov' => ['required', Rule::in(self::COVERAGE)],
            'first' => ['required', 'string', 'max:80'],
            'last' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email:filter', 'max:255'],
            'phone' => ['required', 'string', 'max:40', new \App\Rules\PhoneNumber],
            'street' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:120'],
            'st' => ['required', Rule::in(config('cospark.us_states'))],
            'zip' => ['required', 'regex:/^\d{5}(-\d{4})?$/'],
        ];
    }
}
