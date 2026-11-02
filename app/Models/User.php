<?php

namespace App\Models;

use App\Services\Klaviyo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_MEMBER = 'member';

    public const ROLE_PRODUCER = 'producer';

    public const ROLE_ADMIN = 'admin';

    protected $fillable = [
        'first_name', 'last_name', 'email', 'phone', 'street', 'street2', 'city', 'state', 'zip',
        'role', 'status', 'sms_opt_in', 'sms_opt_in_at', 'avatar_path', 'parent_id', 'relationship', 'permissions', 'password',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'sms_opt_in' => 'boolean',
        'sms_opt_in_at' => 'datetime',
        'permissions' => 'array',
        'password' => 'hashed',
        'sub_accounts_paid_through' => 'datetime',
    ];

    protected static function booted(): void
    {
        // SMS opt-in (registration or profile) is mirrored to Klaviyo's SMS list; best-effort
        static::saved(function (self $user) {
            if (Klaviyo::enabled() && ($user->wasRecentlyCreated ? $user->sms_opt_in : $user->wasChanged(['sms_opt_in', 'phone']))) {
                try {
                    app(Klaviyo::class)->syncSmsConsent($user);
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        });
    }

    /** Public member number, e.g. CSP-M-100042. */
    public function getMemberNumberAttribute(): string
    {
        return 'CSP-M-'.(100000 + $this->id);
    }

    public function getNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getInitialsAttribute(): string
    {
        return Str::upper(Str::substr($this->first_name, 0, 1).Str::substr($this->last_name, 0, 1));
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    /** The current (most recent) membership. */
    public function membership(): HasOne
    {
        return $this->hasOne(Membership::class)->latestOfMany();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function tokenTransactions(): HasMany
    {
        return $this->hasMany(TokenTransaction::class);
    }

    public function policies(): HasMany
    {
        return $this->hasMany(Policy::class);
    }

    public function claims(): HasMany
    {
        return $this->hasMany(Claim::class);
    }

    public function paymentMethods(): HasMany
    {
        return $this->hasMany(PaymentMethod::class);
    }

    public function vacationTokenLots(): HasMany
    {
        return $this->hasMany(VacationTokenLot::class);
    }

    public function vacationRequests(): HasMany
    {
        return $this->hasMany(VacationRequest::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function producer(): HasOne
    {
        return $this->hasOne(Producer::class);
    }

    public function qrCode(): HasOne
    {
        return $this->hasOne(MemberQrCode::class)->latestOfMany();
    }

    public function qrRedemptions(): HasMany
    {
        return $this->hasMany(QrRedemption::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function subAccounts(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function isSubAccount(): bool
    {
        return $this->parent_id !== null;
    }

    /** Sub-account permission set by the main member; primary members have every permission. */
    public function hasPermission(string $permission): bool
    {
        return ! $this->isSubAccount() || in_array($permission, $this->permissions ?? [], true);
    }

    public function isProducer(): bool
    {
        return $this->role === self::ROLE_PRODUCER;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /** Text messages are only sent to members who opted in during registration. */
    public function routeNotificationForSms(): ?string
    {
        return $this->sms_opt_in ? $this->phone : null;
    }

    /** Where the user lands after signing in. */
    public function homeRoute(): string
    {
        return match ($this->role) {
            self::ROLE_PRODUCER => route('producer.dashboard'),
            self::ROLE_ADMIN => route('admin.dashboard'),
            default => route('dashboard'),
        };
    }

    /** Registration still needs an email code or the first membership payment (sub-accounts are invited instead). */
    public function needsOnboarding(): bool
    {
        return $this->role === self::ROLE_MEMBER && $this->status === 'pending' && ! $this->isSubAccount();
    }
}
