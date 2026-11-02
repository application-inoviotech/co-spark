<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;

/**
 * Runs the scheduler from a secret URL, for hosting where no server cron can be added: an external
 * cron service calls /cron/{CRON_TOKEN} every minute. Same work as `php artisan schedule:run`
 * (heartbeat, queued emails/texts, payment webhooks, billing:daily at 08:00).
 */
class CronController extends Controller
{
    public function __invoke(string $token): Response
    {
        $secret = (string) config('app.cron_token');
        abort_unless($secret !== '' && hash_equals($secret, $token), 404);

        @set_time_limit(58);
        ignore_user_abort(true);
        Artisan::call('schedule:run');

        return response(Artisan::output() ?: 'No scheduled commands are ready to run.', 200, ['Content-Type' => 'text/plain']);
    }
}
