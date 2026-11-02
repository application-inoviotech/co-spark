<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * First membership dues payment. The card is tokenized by Accept.js in the browser;
 * only the resulting PaymentMethod id reaches the server.
 */
class MembershipPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'freq' => ['required', Rule::in(array_keys(config('membership.frequencies')))],
            'payment_method' => ['required', 'string', 'max:8192'], // Accept.js opaque token
            'payment_descriptor' => ['required', 'string', 'in:COMMON.ACCEPT.INAPP.PAYMENT'],
            'auto_renew' => ['boolean'],
        ];
    }
}
