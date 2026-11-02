<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;

/**
 * Klaviyo delivers COSPARK's text messages (emails go over SMTP). The app records a "COSPARK SMS"
 * event per message with the text as a property; the Klaviyo flow on that metric sends it.
 */
class Klaviyo
{
    public const EMAIL_METRIC = 'COSPARK Email';

    public const SMS_METRIC = 'COSPARK SMS';

    /** Cache key holding the latest delivery failure (time|metric|message) for the admin System status card. */
    public const LAST_ERROR = 'klaviyo.last_error';

    public static function enabled(): bool
    {
        return filled(config('services.klaviyo.key'));
    }

    /** Record an event for a profile (by email and/or phone); Klaviyo flows act on it. */
    public function event(string $metric, ?string $email, ?string $phone, array $properties, ?string $uniqueId = null): void
    {
        $profile = array_filter(['email' => $email, 'phone_number' => self::e164($phone)]);
        // Klaviyo needs someone to send to; an empty profile is also encoded as [] and rejected with a vague 400
        if ($profile === []) {
            throw new \InvalidArgumentException($email === null && filled($phone)
                ? 'The phone number can\'t receive texts: use a 10-digit US number or include the + country code.'
                : 'No email address or usable phone number to send to.');
        }

        $this->post('events', ['data' => ['type' => 'event', 'attributes' => array_filter([
            'properties' => $properties,
            'metric' => ['data' => ['type' => 'metric', 'attributes' => ['name' => $metric]]],
            'profile' => ['data' => ['type' => 'profile', 'attributes' => (object) $profile]],
            'unique_id' => $uniqueId,
        ])]]);
    }

    /** Mirror a member's SMS opt-in (registration or profile) to the Klaviyo SMS list. */
    public function syncSmsConsent(User $user): void
    {
        $list = config('services.klaviyo.sms_list');
        $phone = self::e164($user->phone);
        if (! $list || ! $phone) {
            return;
        }

        $profile = ['type' => 'profile', 'attributes' => ['email' => $user->email, 'phone_number' => $phone,
            'subscriptions' => ['sms' => ['marketing' => ['consent' => $user->sms_opt_in ? 'SUBSCRIBED' : 'UNSUBSCRIBED']]]]];
        $job = $user->sms_opt_in ? 'profile-subscription-bulk-create-jobs' : 'profile-subscription-bulk-delete-jobs';

        $this->post($job, ['data' => ['type' => $user->sms_opt_in ? 'profile-subscription-bulk-create-job' : 'profile-subscription-bulk-delete-job', 'attributes' => [
            'profiles' => ['data' => [$profile]],
        ], 'relationships' => ['list' => ['data' => ['type' => 'list', 'id' => $list]]]]]);
    }

    /** US numbers as +1XXXXXXXXXX; anything else is passed through when it already has a country code. */
    public static function e164(?string $phone): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $phone);

        return match (true) {
            strlen($digits) === 10 => '+1'.$digits,
            strlen($digits) === 11 && $digits[0] === '1' => '+'.$digits,
            str_starts_with(trim((string) $phone), '+') && strlen($digits) >= 8 => '+'.$digits,
            default => null,
        };
    }

    private function post(string $endpoint, array $body): void
    {
        Http::connectTimeout(5)->timeout(10)
            ->withHeaders(['Authorization' => 'Klaviyo-API-Key '.config('services.klaviyo.key'), 'revision' => config('services.klaviyo.revision'), 'Accept' => 'application/vnd.api+json'])
            ->withBody(json_encode($body), 'application/vnd.api+json')
            ->post("https://a.klaviyo.com/api/{$endpoint}/")
            ->throw();
    }

    /** For notification channels: a Klaviyo outage is reported (logged), never turned into a failed form or admin action. */
    public function eventOrReport(string $metric, ?string $email, ?string $phone, array $properties, ?string $uniqueId = null): void
    {
        try {
            $this->event($metric, $email, $phone, $properties, $uniqueId);
        } catch (\Throwable $e) {
            report($e);
            // Shown in Admin → Dashboard → System status, since staff cannot read the server log.
            // Klaviyo's own reason (e.g. "Invalid phone number") instead of Laravel's truncated message.
            $error = $e instanceof \Illuminate\Http\Client\RequestException ? $e->response->json('errors.0') : null;
            $reason = $error
                ? trim(($error['detail'] ?? $error['title'] ?? 'Rejected').(isset($error['source']['pointer']) ? " ({$error['source']['pointer']})" : '').' · HTTP '.$e->response->status())
                : $e->getMessage();
            $to = $email ?: ($phone ? '•••'.substr((string) self::e164($phone), -4) : '');
            \Illuminate\Support\Facades\Cache::put(self::LAST_ERROR, now()->toIso8601String().'|'.$metric.($to ? " to {$to}" : '').'|'.\Illuminate\Support\Str::limit(preg_replace('/\s+/', ' ', $reason), 400), now()->addDays(7));
        }
    }
}
