<?php

namespace App\Http\Requests;

use App\Models\Appointment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Appointment popup (SOW: Schedule an Appointment — phone or in person). */
class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(array_keys(Appointment::TYPES))],
            'a_date' => ['required', 'date', 'after:today'],
            'a_time' => ['required', Rule::in(config('cospark.appointment_times'))],
            'a_name' => ['required', 'string', 'max:160'],
            'a_email' => ['required', 'email:filter', 'max:255'],
            'a_phone' => ['required', 'string', 'max:40', new \App\Rules\PhoneNumber],
            'a_reason' => ['nullable', Rule::in(config('cospark.appointment_reasons'))],
            'a_msg' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** Business-hours slots only on office days (SOW: business-hours and after-hours scheduling). */
    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function ($v) {
            if ($v->errors()->hasAny(['a_date', 'a_time'])) {
                return;
            }
            $afterHours = last(config('cospark.appointment_times'));
            $day = \Illuminate\Support\Carbon::parse($this->input('a_date'))->dayOfWeekIso;
            if ($this->input('a_time') !== $afterHours && ! in_array($day, config('cospark.appointment_office_days'), true)) {
                $v->errors()->add('a_time', "Our office is open {$this->officeDays()}. Choose one of those days or “{$afterHours}”.");
            }
        });
    }

    private function officeDays(): string
    {
        $names = array_map(fn ($d) => \Illuminate\Support\Carbon::create()->startOfWeek()->addDays($d - 1)->format('l'), config('cospark.appointment_office_days'));

        return count($names) > 1 ? implode(', ', array_slice($names, 0, -1)).' and '.last($names) : implode('', $names);
    }

    public function messages(): array
    {
        return [
            'a_date.after' => 'Choose a date from tomorrow onward.',
            'a_email.email' => 'Enter a valid email.',
        ];
    }
}
