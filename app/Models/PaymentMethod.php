<?php

namespace App\Models;

use App\Services\Payments\CardDetails;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A saved, tokenized card or bank account (never raw card data). */
class PaymentMethod extends Model
{
    protected $fillable = ['user_id', 'type', 'brand', 'bank_name', 'last4', 'exp_month', 'exp_year', 'gateway_token', 'is_default'];

    protected $hidden = ['gateway_token'];

    protected $casts = ['is_default' => 'boolean'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isBank(): bool
    {
        return $this->type === 'bank';
    }

    public function isExpired(): bool
    {
        return ! $this->isBank() && $this->exp_year
            && now()->startOfMonth()->gt(now()->setDate($this->exp_year, $this->exp_month, 1)->startOfMonth());
    }

    public function expiryLabel(): string
    {
        return $this->isBank() ? '—' : sprintf('%02d/%02d', $this->exp_month, $this->exp_year % 100);
    }

    public function label(): string
    {
        return ($this->isBank() ? 'Bank' : $this->brand).' •'.$this->last4;
    }

    public function toCard(): CardDetails
    {
        return new CardDetails($this->user->name, $this->last4, $this->isBank() ? 'Bank' : $this->brand, $this->gateway_token);
    }
}
