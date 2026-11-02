<?php

namespace App\Providers;

use App\Admin\Registry;
use App\Models\MembershipPlan;
use App\Models\Setting;
use App\Services\Billing\AuthorizeNet;
use App\Services\Klaviyo;
use App\Services\Payments\AuthorizeNetPaymentGateway;
use App\Services\Payments\PaymentGateway;
use App\Services\Sms\KlaviyoSmsSender;
use App\Services\Sms\LogSmsSender;
use App\Services\Sms\SmsSender;
use App\Support\Content;
use App\Support\FieldNames;
use App\Support\Navigation;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Navigation::class);
        $this->app->singleton(Content::class);
        $this->app->singleton(Registry::class);
        // Text messages go through Klaviyo once its key is set; until then they are only logged.
        // Emails always use Laravel's own mail channel (SMTP, MAIL_* settings), not Klaviyo.
        $this->app->singleton(SmsSender::class, Klaviyo::enabled() ? KlaviyoSmsSender::class : LogSmsSender::class);
        $this->app->singleton(AuthorizeNet::class);
        $this->app->singleton(PaymentGateway::class, AuthorizeNetPaymentGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Setting::applyToConfig();
        MembershipPlan::applyToConfig();

        // Readable field names in every validation message ("vehicle 2 VIN", not "vehicles.1.vin")
        Validator::resolver(fn ($translator, $data, $rules, $messages, $attributes) => FieldNames::validator($translator, $data, $rules, $messages, $attributes));

        View::composer(['partials.header', 'partials.footer'], function ($view) {
            $view->with('nav', $this->app->make(Navigation::class))
                ->with('office', config('cospark.office'));
        });
        View::composer('partials.appointment-modal', function ($view) {
            $view->with('times', config('cospark.appointment_times'))
                ->with('reasons', config('cospark.appointment_reasons'));
        });
    }
}
