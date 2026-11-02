<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

/**
 * Dynamic quote engine (SOW: Get a Quote). Baseline contact fields are required for
 * every product; product-specific questionnaire sections are required by type.
 */
class StoreQuoteRequest extends StepFormRequest
{
    protected array $columns = ['type', 'zip', 'first', 'last', 'email', 'phone', 'pref', 'street', 'city', 'st', 'notes', 'decs'];

    /** Questionnaire sections included in each quote flow (mirrors FLOWS in quote.js). */
    private const SECTIONS = [
        'auto' => ['drivers', 'vehicles', 'prior', 'limits'],
        'home' => ['home', 'prior'],
        'renters' => ['rental', 'prior'],
        'auto-home' => ['home', 'drivers', 'vehicles', 'prior', 'limits'],
        'auto-renters' => ['rental', 'drivers', 'vehicles', 'prior', 'limits'],
        'life' => ['life'],
        'business' => ['business'],
        'condo' => ['condo', 'prior'],
    ];

    public function authorize(): bool
    {
        return true;
    }

    /** Quote type slug ("auto-home") from the label the engine posts ("Auto & Home"). */
    public function typeKey(): ?string
    {
        $key = array_search($this->input('type'), config('cospark.quote_types'), true);

        return $key === false ? null : $key;
    }

    public function rules(): array
    {
        $sections = self::SECTIONS[$this->typeKey()] ?? [];
        $text = ['required', 'string', 'max:160'];
        $optional = ['nullable', 'string', 'max:160'];

        $rules = [
            'type' => ['required', Rule::in(config('cospark.quote_types'))],
            'zip' => ['required', 'regex:/^\d{5}(-\d{4})?$/'],
            'first' => ['required', 'string', 'max:80'],
            'last' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email:filter', 'max:255'],
            'phone' => ['required', 'string', 'max:40', new \App\Rules\PhoneNumber],
            'pref' => ['required', Rule::in(['Email', 'Phone', 'Text'])],
            'street' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:120'],
            'st' => ['required', Rule::in(config('cospark.us_states'))],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];

        if (in_array('drivers', $sections)) {
            $rules += [
                'drivers' => ['required', 'array', 'min:1', 'max:10'],
                'drivers.*.name' => $text,
                'drivers.*.dob' => ['required', 'date', 'before:today'],
                'drivers.*.dl' => ['required', 'string', 'max:40'],
                'drivers.*.dlst' => ['required', Rule::in(config('cospark.us_states'))],
                'drivers.*.acc' => ['required', Rule::in(['0', '1', '2', '3+'])],
                'drivers.*.viol' => ['required', Rule::in(['0', '1', '2', '3+'])],
                'drivers.*.claims' => ['required', Rule::in(['0', '1', '2', '3+'])],
            ];
        }

        if (in_array('vehicles', $sections)) {
            $rules += [
                'vehicles' => ['required', 'array', 'min:1', 'max:10'],
                'vehicles.*.year' => ['required', 'integer', 'between:1950,'.(now()->year + 1)],
                'vehicles.*.make' => ['required', 'string', 'max:60'],
                'vehicles.*.model' => ['required', 'string', 'max:60'],
                'vehicles.*.vin' => ['nullable', 'string', 'max:17', new \App\Rules\VehicleIdentificationNumber],
                'vehicles.*.own' => ['required', Rule::in(['Owned', 'Financed', 'Leased'])],
                'vehicles.*.use' => ['required', Rule::in(['Commute', 'Business', 'Pleasure'])],
                'vehicles.*.miles' => ['required', 'string', 'max:40'],
            ];
        }

        if (in_array('prior', $sections)) {
            $rules += [
                'insured' => ['required', Rule::in(['Yes', 'No'])],
                'carrier' => ['required_if:insured,Yes', 'nullable', 'string', 'max:120'],
                'expires' => ['required_if:insured,Yes', 'nullable', 'date'],
                'limits' => $optional,
                'decs' => ['nullable', 'array'],
            ];
            $rules += $this->fileRules(['decs']);
        }

        if (in_array('home', $sections)) {
            $rules += [
                'paddr' => ['required', 'string', 'max:255'],
                'occ' => $text,
                'built' => ['required', 'integer', 'between:1800,'.(now()->year + 1)],
                'sqft' => ['required', 'integer', 'between:100,100000'],
                'beds' => $text, 'baths' => $text, 'constr' => $text, 'roof' => $text,
                'roofage' => ['required', 'integer', 'between:0,200'],
                'garage' => $optional,
                'value' => $text,
            ];
        }

        if (in_array('rental', $sections)) {
            $rules += [
                'raddr' => ['required', 'string', 'max:255'],
                'dwell' => $text, 'occupants' => $text, 'length' => $text, 'ppv' => $text, 'liab' => $text,
            ];
        }

        if (in_array('condo', $sections)) {
            $rules += [
                'caddr' => ['required', 'string', 'max:255'],
                'unit' => ['required', 'string', 'max:40'],
                'built' => ['required', 'integer', 'between:1800,'.(now()->year + 1)],
                'sqft' => ['required', 'integer', 'between:100,100000'],
                'occ' => $text, 'beds' => $text, 'baths' => $text,
                'ppv' => $text, 'addv' => $text, 'liab' => $text,
            ];
        }

        if (in_array('life', $sections)) {
            $rules += [
                'aname' => $text,
                'gender' => $text,
                'adob' => ['required', 'date', 'before:today'],
                'amount' => $text,
                'ptype' => ['required', Rule::in(['Term', 'Whole Life', 'Universal', 'Not sure'])],
                'dur' => ['required_unless:ptype,Whole Life', 'nullable', 'string', 'max:40'],
                'occupation' => $text,
                'hazard' => ['required', Rule::in(['Yes', 'No'])],
                'tobacco' => ['required', Rule::in(['Yes', 'No'])],
                'health' => $text,
                'existing' => ['required', Rule::in(['Yes', 'No'])],
                'existingAmt' => $optional,
            ];
        }

        if (in_array('business', $sections)) {
            $rules += [
                'legal' => $text, 'entity' => $text, 'industry' => $text,
                'baddr' => ['required', 'string', 'max:255'],
                'years' => $text, 'revenue' => $text, 'emp' => $text,
                'activities' => ['required', 'string', 'max:2000'],
                'locations' => $text, 'property' => $text,
                'equip' => $optional, 'vehicles' => $optional,
                'cov' => ['required', 'array', 'min:1'],
                'cov.*' => ['string', 'max:60'],
            ];
        }

        return $rules;
    }

    public function attributes(): array
    {
        // Other codes are named in config/field_names.php; "insured" means something else on the claim form
        return ['insured' => 'current insurance status', 'drivers.*.name' => 'full legal name'];
    }
}
