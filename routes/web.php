<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\ClaimController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\CronController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InsuranceController;
use App\Http\Controllers\LegacyRedirectController;
use App\Http\Controllers\Member;
use App\Http\Controllers\PageController;
use App\Http\Controllers\Producer;
use App\Http\Controllers\ProducerApplicationController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\QuoteDraftController;
use App\Http\Controllers\ResourceController;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Support\Facades\Route;

// Authenticated by the raw-body Authorize.net signature, not a browser session/CSRF token.
Route::post('/webhooks/authorizenet', \App\Http\Controllers\AuthorizeNetWebhookController::class)
    ->name('webhooks.authorizenet');

/*
|--------------------------------------------------------------------------
| Public website
|--------------------------------------------------------------------------
*/

// External cron fallback (no server cron on shared hosting): see CronController
Route::get('/cron/{token}', CronController::class)->middleware('throttle:10,1')->name('cron');
Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/membership', [PageController::class, 'membership'])->name('membership');
Route::get('/payments', [PageController::class, 'payments'])->name('payments');
Route::get('/ecosystem', [PageController::class, 'ecosystem'])->name('ecosystem');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');
Route::get('/legal', [PageController::class, 'legal'])->name('legal');
Route::get('/producer', [PageController::class, 'producer'])->name('producer');

Route::get('/insurance', [InsuranceController::class, 'index'])->name('insurance.index');
Route::get('/insurance/{product}', [InsuranceController::class, 'show'])->name('insurance.show');

Route::get('/quote', [QuoteController::class, 'create'])->name('quote');
Route::get('/quote/retrieve', [QuoteDraftController::class, 'create'])->name('quote.retrieve.form');

Route::get('/claims', [ClaimController::class, 'index'])->name('claims.index');
Route::get('/claims/file', [ClaimController::class, 'create'])->name('claims.create');

Route::get('/resources', [ResourceController::class, 'index'])->name('resources.index');
Route::get('/resources/{resource}', [ResourceController::class, 'show'])->name('resources.show');

// Public form submissions (JSON), rate limited per IP
Route::middleware('throttle:forms')->group(function () {
    Route::post('/quote', [QuoteController::class, 'store'])->name('quote.store');
    Route::post('/quote/quick', [QuoteController::class, 'quick'])->name('quote.quick');
    Route::post('/quote/save', [QuoteDraftController::class, 'store'])->name('quote.save');
    Route::post('/quote/retrieve', [QuoteDraftController::class, 'retrieve'])->name('quote.retrieve');
    Route::post('/claims', [ClaimController::class, 'store'])->name('claims.store');
    Route::post('/producer/apply', [ProducerApplicationController::class, 'store'])->name('producer.apply');
    Route::post('/appointments', [AppointmentController::class, 'store'])->name('appointments.store');
    Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');
});

/*
|--------------------------------------------------------------------------
| Authentication (members and producers)
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:auth')->name('login.store');

    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:auth')->name('register.store');

    Route::get('/forgot-password', [PasswordResetController::class, 'create'])->name('password.request');
    Route::middleware('throttle:auth')->group(function () {
        Route::post('/forgot-password', [PasswordResetController::class, 'sendCode'])->name('password.email');
        Route::post('/forgot-password/verify', [PasswordResetController::class, 'verifyCode'])->name('password.verify');
        Route::post('/reset-password', [PasswordResetController::class, 'update'])->name('password.update');
    });
});

// Open to guests (new sign-up) and to signed-in members who still have to finish onboarding
Route::get('/register', [RegisterController::class, 'create'])->name('register');

// Registration continues after the account exists: email OTP, then first payment for paid plans
Route::middleware(['auth', 'throttle:auth'])->group(function () {
    Route::post('/register/verify', [RegisterController::class, 'verify'])->name('register.verify');
    Route::post('/register/resend', [RegisterController::class, 'resend'])->name('register.resend');
    Route::post('/register/payment', [RegisterController::class, 'payment'])->name('register.payment');
});

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| Member & producer portals
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'onboarded'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'member'])->name('dashboard');
    Route::get('/producer/dashboard', [DashboardController::class, 'producer'])->name('producer.dashboard');

    // Producer portal data and actions (JSON)
    Route::prefix('producer')->name('producer.')->controller(Producer\PortalController::class)->group(function () {
        Route::get('/dashboard/data', 'data')->name('data');
        Route::get('/documents/{document}', 'showDocument')->name('documents.show');
        Route::get('/commissions.csv', 'exportCommissions')->name('commissions.export');

        Route::middleware('throttle:dashboard')->group(function () {
            Route::post('/leads/{lead}', 'updateLead')->name('leads.update');
            Route::post('/licenses', 'storeLicense')->name('licenses.store');
            Route::post('/documents', 'uploadDocument')->name('documents.store');
            Route::post('/profile', 'updateProfile')->name('profile');
            Route::post('/vacations', 'requestVacation')->name('vacations');
            Route::post('/bonus/redeem', 'redeemBonus')->name('bonus.redeem');
        });
    });

    // Member dashboard data and actions (JSON)
    Route::prefix('dashboard')->name('dashboard.')->group(function () {
        Route::get('/data', Member\DashboardDataController::class)->name('data');
        Route::get('/payments/{payment}/receipt', [Member\PaymentController::class, 'receipt'])->name('payments.receipt');
        Route::get('/policies/{policy}/documents/{document}', Member\PolicyDocumentController::class)->name('policies.documents');

        Route::middleware('throttle:dashboard')->group(function () {
            Route::post('/payments/dues', [Member\PaymentController::class, 'dues'])->name('payments.dues');
            Route::post('/payments/premium', [Member\PaymentController::class, 'premium'])->name('payments.premium');
            Route::post('/payments/vacation-tokens', [Member\PaymentController::class, 'vacationTokens'])->name('payments.vt');
            Route::post('/payments/upgrade', [Member\PaymentController::class, 'upgrade'])->name('payments.upgrade');
            Route::post('/payments/{payment}/retry', [Member\PaymentController::class, 'retry'])->name('payments.retry');

            Route::post('/payment-methods', [Member\PaymentMethodController::class, 'store'])->name('methods.store');
            Route::post('/payment-methods/{method}/default', [Member\PaymentMethodController::class, 'makeDefault'])->name('methods.default');
            Route::delete('/payment-methods/{method}', [Member\PaymentMethodController::class, 'destroy'])->name('methods.destroy');

            Route::post('/membership/frequency', [Member\MembershipController::class, 'frequency'])->name('membership.frequency');
            Route::post('/membership/switch', [Member\MembershipController::class, 'switchPlan'])->name('membership.switch');
            Route::post('/membership/cancel', [Member\MembershipController::class, 'cancel'])->name('membership.cancel');

            Route::post('/token-transfers', [Member\TokenTransferController::class, 'store'])->name('transfers.store');
            Route::post('/vacations', [Member\VacationRequestController::class, 'store'])->name('vacations.store');
            Route::post('/vacations/{vacation}/cancel', [Member\VacationRequestController::class, 'cancel'])->name('vacations.cancel');

            Route::post('/claims/{claim}/messages', [Member\ClaimController::class, 'message'])->name('claims.message');
            Route::post('/claims/{claim}/documents', [Member\ClaimController::class, 'upload'])->name('claims.upload');

            Route::post('/notifications/read-all', [Member\NotificationController::class, 'readAll'])->name('notifications.readAll');
            Route::post('/notifications/{id}/read', [Member\NotificationController::class, 'read'])->name('notifications.read');

            Route::post('/sub-accounts', [Member\SubAccountController::class, 'store'])->name('subs.store');
            Route::post('/sub-accounts/{sub}/status', [Member\SubAccountController::class, 'setStatus'])->name('subs.status');

            Route::post('/profile', [Member\ProfileController::class, 'update'])->name('profile.update');
            Route::post('/profile/avatar', [Member\ProfileController::class, 'avatar'])->name('profile.avatar');
            Route::post('/profile/password', [Member\ProfileController::class, 'password'])->name('profile.password');

            Route::post('/qr/send', [Member\QrCodeController::class, 'send'])->name('qr.send');
        });
    });
});

/*
|--------------------------------------------------------------------------
| Admin portal (Inertia + Vue)
|--------------------------------------------------------------------------
*/

Route::middleware(HandleInertiaRequests::class)->prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest:admin')->group(function () {
        Route::get('/login', [Admin\AuthController::class, 'create'])->name('login');
        Route::post('/login', [Admin\AuthController::class, 'store'])->middleware('throttle:auth')->name('login.store');
    });
    Route::post('/logout', [Admin\AuthController::class, 'destroy'])->middleware('auth:admin')->name('logout');
});

Route::middleware(['auth:admin', 'admin', HandleInertiaRequests::class])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [Admin\AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/qr-scan', [Admin\AdminController::class, 'qrScan'])->name('qr.scan');
    Route::post('/qr-scan', [Admin\AdminController::class, 'qrRedeem'])->name('qr.redeem');
    Route::get('/reports', [Admin\AdminController::class, 'reports'])->name('reports');
    Route::get('/reports/{report}.csv', [Admin\AdminController::class, 'export'])->name('reports.export');
    Route::get('/settings', [Admin\AdminController::class, 'settings'])->name('settings');
    Route::post('/settings', [Admin\AdminController::class, 'saveSettings'])->name('settings.save');
    Route::post('/system/queue', [Admin\AdminController::class, 'processQueue'])->name('system.queue');
    Route::post('/system/retry', [Admin\AdminController::class, 'retryFailed'])->name('system.retry');
    Route::get('/files/{attachment}', [Admin\AdminController::class, 'file'])->name('files');

    Route::controller(Admin\ResourceController::class)->name('resources.')->group(function () {
        Route::get('/{resource}', 'index')->name('index');
        Route::get('/{resource}/create', 'create')->name('create');
        Route::post('/{resource}', 'store')->name('store');
        Route::get('/{resource}/{id}', 'show')->name('show');
        Route::get('/{resource}/{id}/edit', 'edit')->name('edit');
        Route::put('/{resource}/{id}', 'update')->name('update');
        Route::delete('/{resource}/{id}', 'destroy')->name('destroy');
        Route::post('/{resource}/{id}/actions/{action}', 'action')->name('action');
    });
});

/*
|--------------------------------------------------------------------------
| Approved prototype links (*.html) → permanent redirects
|--------------------------------------------------------------------------
*/

Route::get('/{page}.html', LegacyRedirectController::class)->where('page', '[a-z-]+');
