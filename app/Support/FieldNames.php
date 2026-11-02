<?php

namespace App\Support;

use Illuminate\Contracts\Translation\Translator;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;

/**
 * Validator with readable field names (registered for every validation in AppServiceProvider):
 * form codes use config/field_names.php and array fields read "vehicle 2 VIN" or
 * "areas affected #1" instead of "vehicles.1.vin" / "rooms.0".
 */
class FieldNames
{
    public static function validator(Translator $translator, array $data, array $rules, array $messages, array $attributes): Validator
    {
        $names = config('field_names');

        // A request's wildcard names ("vehicles.*.vin") label the field inside each numbered item
        $itemNames = array_filter($attributes, fn ($key) => str_contains($key, '*'), ARRAY_FILTER_USE_KEY);

        $validator = new Validator($translator, $data, $rules, $messages, array_merge($names, array_diff_key($attributes, $itemNames)));
        $validator->setImplicitAttributesFormatter(fn (string $attribute) => self::arrayField($attribute, $itemNames, $names));

        return $validator;
    }

    /** "drivers.0.dlst" → "driver 1 license issuing state", "rooms.2" → "areas affected #3", "files.photos.0" → "photos #1". */
    public static function arrayField(string $attribute, array $itemNames, array $names): string
    {
        $parts = explode('.', $attribute);
        $label = fn (string $key) => $names[$key] ?? str_replace('_', ' ', Str::snake($key));
        $last = count($parts) - 1;
        $words = [];

        foreach ($parts as $i => $part) {
            if (! ctype_digit($part) || $i === 0) {
                continue;
            }
            $number = (int) $part + 1;
            if ($i === $last) {
                // A plain list of values or files: name the list, then the position
                $wildcard = preg_replace('/(?<=\.)\d+(?=\.|$)/', '*', $attribute);
                $words[] = ($itemNames[$wildcard] ?? $label($parts[$i - 1])).' #'.$number;
            } else {
                $words[] = Str::singular($label($parts[$i - 1])).' '.$number;
            }
        }

        if (! ctype_digit($parts[$last])) {
            $wildcard = preg_replace('/(?<=\.)\d+(?=\.|$)/', '*', $attribute);
            $words[] = $itemNames[$wildcard] ?? $label($parts[$last]);
        }

        return implode(' ', $words);
    }
}
