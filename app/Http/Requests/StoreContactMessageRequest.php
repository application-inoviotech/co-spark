<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Contact Us form (SOW: First Name, Last Name, Email, Contact Number, Message). */
class StoreContactMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email:filter', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40', new \App\Rules\PhoneNumber],
            'message' => ['required', 'string', 'max:5000'],
        ];
    }
}
