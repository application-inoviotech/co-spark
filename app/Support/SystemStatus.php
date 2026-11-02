<?php

namespace App\Support;

use App\Services\Klaviyo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * What staff need to see when emails/texts "are not arriving" on hosting without shell access:
 * is the server cron calling the scheduler, is anything stuck in the queue, and why did jobs fail.
 */
class SystemStatus
{
    public const HEARTBEAT = 'scheduler.last_run';

    public static function summary(): array
    {
        $beat = Cache::get(self::HEARTBEAT);
        $queue = config('queue.default');
        $jobs = $queue === 'database' && Schema::hasTable('jobs') ? DB::table('jobs') : null;
        $oldest = $jobs?->min('created_at');
        $failed = Schema::hasTable('failed_jobs') ? DB::table('failed_jobs') : null;
        $lastFailure = $failed?->latest('failed_at')->first();
        $klaviyo = Cache::get(Klaviyo::LAST_ERROR);
        [$kTime, $kMetric, $kMessage] = $klaviyo ? explode('|', $klaviyo, 3) + [null, null, null] : [null, null, null];

        return [
            'cronOk' => $beat && Carbon::parse($beat)->gt(now()->subMinutes(3)),
            'cron' => $beat ? Carbon::parse($beat)->diffForHumans() : 'never — the server cron is not calling schedule:run',
            'queue' => $queue === 'sync' ? 'sync (sent immediately, no worker needed)' : $queue,
            'pending' => $jobs?->count() ?? 0,
            'oldest' => $oldest ? Carbon::createFromTimestamp($oldest)->diffForHumans() : null,
            'failed' => $failed?->count() ?? 0,
            // The message only (with the provider's response body), without the stack trace
            'lastFailure' => $lastFailure ? Carbon::parse($lastFailure->failed_at)->diffForHumans().' · '.Str::limit(preg_replace('/\s+/', ' ', Str::before($lastFailure->exception, 'Stack trace:')), 400) : null,
            'klaviyoError' => $kTime ? Carbon::parse($kTime)->diffForHumans()." · {$kMetric} · {$kMessage}" : null,
            'mail' => config('mail.default').(Klaviyo::enabled() ? ' (texts via Klaviyo)' : ''),
        ];
    }
}
