<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Admin-editable site settings. Keys are config paths under "cospark." (e.g. "office.phone",
 * "legal.privacy"); saved values override config/cospark.php at boot.
 */
class Setting extends Model
{
    public const EDITABLE = [
        'office.name' => 'Business name', 'office.phone' => 'Phone', 'office.phone_tel' => 'Phone (digits for tel: links)',
        'office.email' => 'Email', 'office.address_line1' => 'Address line 1', 'office.address_line2' => 'Address line 2',
        'office.hours' => 'Business hours', 'office.roadside_phone' => 'Roadside phone', 'office.roadside_tel' => 'Roadside phone (digits)',
        'office.token_transfer_email' => 'Token transfer email', 'admin_notify_email' => 'Office notification email',
        'social.facebook' => 'Facebook URL', 'social.instagram' => 'Instagram URL', 'social.linkedin' => 'LinkedIn URL',
        'legal.privacy' => 'Privacy policy', 'legal.terms' => 'Terms & conditions', 'legal.disclaimers' => 'Disclaimers', 'legal.sms' => 'SMS terms',
        'appointment_times' => 'Appointment times', 'appointment_reasons' => 'Appointment reasons',
        'sms_enabled' => 'Send SMS notifications', 'billing_grace_days' => 'Days after a missed renewal before membership expires',
    ];

    /** Field type per key; anything not listed is a one-line text field. */
    public const TYPES = [
        'legal.privacy' => 'textarea', 'legal.terms' => 'textarea', 'legal.disclaimers' => 'textarea', 'legal.sms' => 'textarea',
        'appointment_times' => 'lines', 'appointment_reasons' => 'lines', 'sms_enabled' => 'checkbox', 'billing_grace_days' => 'number',
    ];

    public static function type(string $key): string
    {
        return self::TYPES[$key] ?? 'text';
    }

    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    /** Apply saved settings on top of config('cospark'). */
    public static function applyToConfig(): void
    {
        $values = Cache::rememberForever('cospark.settings', fn () => Schema::hasTable('settings') ? self::pluck('value', 'key')->all() : []);
        foreach ($values as $key => $value) {
            if ($value !== null && $value !== '') {
                config(["cospark.{$key}" => match (self::type($key)) {
                    'lines' => json_decode($value, true),
                    'checkbox' => (bool) $value,
                    'number' => (int) $value,
                    default => $value,
                }]);
            }
        }
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('cospark.settings'));
    }
}
