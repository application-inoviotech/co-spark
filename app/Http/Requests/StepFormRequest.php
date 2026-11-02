<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;

/**
 * Base request for forms built with the StepForm engine (public/assets/js/forms.js).
 * The browser posts the whole form state as a JSON `payload` plus `files[field][]`;
 * the payload is decoded into the request so normal validation rules apply to it,
 * and error keys match the engine's field paths (e.g. "drivers.0.name").
 */
abstract class StepFormRequest extends FormRequest
{
    /** Fields stored in their own columns; everything else is kept as JSON details. */
    protected array $columns = [];

    protected function prepareForValidation(): void
    {
        $payload = json_decode((string) $this->input('payload'), true);

        $this->merge(is_array($payload) ? $this->clean($payload) : []);
    }

    /** Trim strings and drop empty values the UI leaves behind for hidden fields. */
    private function clean(array $data): array
    {
        $out = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = $this->clean($value);
            } elseif (is_string($value)) {
                $value = trim($value);
            }
            if ($value !== '' && $value !== null && $value !== []) {
                $out[$key] = $value;
            }
        }

        return $out;
    }

    /** @return array<string, string|array> */
    protected function fileRules(array $fields, int $maxFiles = 10): array
    {
        $rule = ['file', 'max:'.config('cospark.upload_max_kb'), 'mimes:'.config('cospark.upload_mimes')];
        $rules = ['files' => ['nullable', 'array']];
        foreach ($fields as $field) {
            $rules["files.{$field}"] = ['nullable', 'array', "max:{$maxFiles}"];
            $rules["files.{$field}.*"] = $rule;
        }

        return $rules;
    }

    /** @return array<string, UploadedFile[]> */
    public function uploadedFiles(): array
    {
        return array_filter((array) $this->file('files', []));
    }

    /** Validated data that has no dedicated column. */
    public function details(): array
    {
        return Arr::except($this->validated(), [...$this->columns, 'files', 'payload']);
    }

    public function messages(): array
    {
        return [
            'zip.regex' => 'Enter a 5-digit ZIP code.',
            'email.email' => 'Enter a valid email address.',
            'files.*.*.max' => 'Each file must be 10 MB or smaller.',
            'files.*.*.mimes' => 'Upload a PDF, JPG, PNG, HEIC or Word document.',
        ];
    }
}
