<?php

namespace App\Http\Requests;

use App\Models\Policy;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/** File a Claim (SOW: customer/policy → claim type → incident → documents → review). */
class StoreClaimRequest extends StepFormRequest
{
    public const INSURANCE_TYPES = ['Auto', 'Home', 'Renters', 'Condo', 'Commercial', 'Life', 'Other'];

    protected array $columns = ['first', 'last', 'email', 'phone', 'pref', 'policy', 'itype', 'ctype', 'date', 'time', 'loc', 'desc', 'comments', 'photos', 'docs'];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $type = $this->input('itype');
        $choice = ['nullable', Rule::in(['Yes', 'No', 'Not sure', 'Partially'])];
        $text = ['nullable', 'string', 'max:255'];

        return [
            'first' => ['required', 'string', 'max:80'],
            'last' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email:filter', 'max:255'],
            'phone' => ['required', 'string', 'max:40', new \App\Rules\PhoneNumber],
            'pref' => ['required', Rule::in(['Email', 'Phone', 'Text'])],
            'policy' => ['required', 'string', 'max:60'],
            'itype' => ['required', Rule::in(self::INSURANCE_TYPES)],
            'ctype' => ['required', 'string', 'max:60'],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'time' => ['nullable', 'date_format:H:i'],
            'loc' => ['required', 'string', 'max:255'],
            'desc' => ['required', 'string', 'max:5000'],
            'comments' => ['nullable', 'string', 'max:2000'],
            'photos' => ['nullable', 'array'],
            'docs' => ['nullable', 'array'],

            // claim-type specific questions (shown dynamically in the form)
            'vehicle' => [Rule::requiredIf($type === 'Auto'), 'nullable', 'string', 'max:120'],
            'police' => [Rule::requiredIf($type === 'Auto'), ...$choice],
            'report' => $text,
            'other' => [Rule::requiredIf($type === 'Auto'), ...$choice],
            'otherInfo' => $text,
            'drivable' => [Rule::requiredIf($type === 'Auto'), ...$choice],
            'rooms' => [Rule::requiredIf(in_array($type, ['Home', 'Renters', 'Condo'])), 'nullable', 'array'],
            'rooms.*' => ['string', 'max:40'],
            'habitable' => [Rule::requiredIf(in_array($type, ['Home', 'Renters', 'Condo'])), ...$choice],
            'mitig' => $choice,
            'closed' => [Rule::requiredIf($type === 'Commercial'), ...$choice],
            'injured' => [Rule::requiredIf($type === 'Commercial'), ...$choice],
            'insured' => [Rule::requiredIf($type === 'Life'), 'nullable', 'string', 'max:160'],
            'relation' => [Rule::requiredIf($type === 'Life'), 'nullable', 'string', 'max:80'],
            'injury' => [Rule::requiredIf(! in_array($type, ['Life', 'Commercial'])), ...$choice],

            ...$this->fileRules(['photos', 'docs'], 20),
            // "Photos & video" takes pictures and clips only; documents go in "docs"
            'files.photos.*' => ['file', 'max:'.config('cospark.upload_max_kb'), 'mimes:jpg,jpeg,png,heic,mp4,mov,qt'],
        ];
    }

    public function messages(): array
    {
        // Listed first: Laravel uses the first matching message, and the parent has a generic files.*.*.mimes
        return ['files.photos.*.mimes' => 'Photos & video: upload a JPG, PNG, HEIC, MP4 or MOV file.', ...parent::messages()];
    }

    /** The verified policy the claim is filed against (set once validation passes). */
    public ?Policy $policy = null;

    /**
     * Verify the policy (SOW: customer/policy → claim type): the number must exist, belong to the signed-in
     * member (or their parent account), not be cancelled, match the chosen insurance type and cover the incident date.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $data = $this->only(['policy', 'itype', 'date']);
            if ($v->errors()->hasAny(['policy', 'itype']) || blank($data['policy'] ?? null)) {
                return;
            }
            $policy = Policy::whereRaw('upper(policy_number) = ?', [strtoupper(trim($data['policy']))])->first();
            $user = $this->user();
            $owner = $user?->isSubAccount() ? $user->parent : $user;

            if (! $policy || ($owner && $policy->user_id !== $owner->id)) {
                $v->errors()->add('policy', $owner ? 'This policy number is not on your account. Check it against your policy documents.' : 'We couldn’t find this policy number. Check it against your policy documents.');

                return;
            }
            if ($policy->status === 'cancelled') {
                $v->errors()->add('policy', 'This policy is cancelled. Contact COSPARK for help with this claim.');

                return;
            }
            if ($policy->claimType() !== ($data['itype'] ?? null)) {
                $article = preg_match('/^[AEIOU]/i', $policy->type) ? 'an' : 'a';
                $v->errors()->add('itype', "Policy {$policy->policy_number} is {$article} {$policy->type} policy. Choose {$policy->claimType()} as the insurance type.");

                return;
            }
            $date = $data['date'] ?? null;
            if ($date && ! $v->errors()->has('date') && ($date < $policy->starts_on->toDateString() || $date > $policy->expires_on->toDateString())) {
                $v->errors()->add('date', 'The incident date must fall within the policy period ('.$policy->starts_on->format('M j, Y').' – '.$policy->expires_on->format('M j, Y').').');

                return;
            }
            $this->policy = $policy;
        });
    }

    public function attributes(): array
    {
        return [
            'first' => 'first name', 'last' => 'last name', 'pref' => 'preferred contact method',
            'policy' => 'policy number', 'itype' => 'insurance type', 'ctype' => 'claim type',
            'date' => 'incident date', 'time' => 'incident time', 'loc' => 'incident location', 'desc' => 'description',
        ];
    }
}
