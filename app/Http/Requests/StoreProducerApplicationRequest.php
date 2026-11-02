<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

/** Become a Producer online application (SOW: Producer Application). */
class StoreProducerApplicationRequest extends StepFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $licensed = $this->input('lic') === 'Licensed';

        return [
            'first' => ['required', 'string', 'max:80'],
            'last' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email:filter', 'max:255'],
            'phone' => ['required', 'string', 'max:40', new \App\Rules\PhoneNumber],
            'street' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:120'],
            'st' => ['required', Rule::in(config('cospark.us_states'))],
            'zip' => ['required', 'regex:/^\d{5}(-\d{4})?$/'],
            'occ' => ['required', 'string', 'max:120'],
            'exp' => ['required', Rule::in(['None yet', 'Some', 'Experienced'])],
            'years' => ['required', Rule::in(['0', '1–2', '3–5', '6–10', '10+'])],
            'prev' => ['nullable', 'string', 'max:160'],
            'lines' => ['nullable', 'array'],
            'lines.*' => [Rule::in(['Auto', 'Home', 'Life', 'Health', 'Commercial', 'Medicare', 'Other'])],
            'lic' => ['required', Rule::in(['Licensed', 'In progress', 'Not licensed'])],
            'licno' => [Rule::requiredIf($licensed), 'nullable', 'string', 'max:60'],
            'lictype' => [Rule::requiredIf($licensed), 'nullable', Rule::in(['Property & Casualty', 'Life & Health', 'Both', 'Other'])],
            'licst' => [Rule::requiredIf($licensed), 'nullable', Rule::in(config('cospark.us_states'))],
            'issue' => ['nullable', 'date', 'before_or_equal:today'],
            'exp_d' => ['nullable', 'date', 'after_or_equal:issue'],
            'why' => ['required', 'string', 'max:3000'],
            'comments' => ['nullable', 'string', 'max:3000'],
            'resume' => ['required', 'array'],
            'licfile' => [Rule::requiredIf($licensed), 'nullable', 'array'],
            ...$this->fileRules(['resume', 'licfile', 'appfile', 'other']),
            'files.resume' => ['required', 'array', 'max:1'],
            'files.licfile' => [Rule::requiredIf($licensed), 'nullable', 'array', 'max:1'],
            'files.appfile' => ['nullable', 'array', 'max:1'],
        ];
    }

    public function messages(): array
    {
        return parent::messages() + [
            'resume.required' => 'Upload your resume.',
            'files.resume.required' => 'Upload your resume.',
            'licfile.required' => 'Upload your insurance license.',
            'files.licfile.required' => 'Upload your insurance license.',
        ];
    }

    public function attributes(): array
    {
        return [
            'first' => 'first name', 'last' => 'last name', 'st' => 'state', 'occ' => 'current occupation',
            'exp' => 'insurance experience', 'years' => 'years of experience', 'lic' => 'licensed status',
            'licno' => 'license number', 'lictype' => 'license type', 'licst' => 'license state',
            'issue' => 'issue date', 'exp_d' => 'expiration date', 'why' => 'reason for applying',
            'files.other.*' => 'additional documents',
        ];
    }
}
