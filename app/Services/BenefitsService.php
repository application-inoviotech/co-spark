<?php

namespace App\Services;

use App\Models\MemberQrCode;
use App\Models\Partner;
use App\Models\QrRedemption;
use App\Models\TokenTransaction;
use App\Models\TokenTransfer;
use App\Models\User;
use App\Models\VacationRequest;
use App\Notifications\MemberAlert;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Admin-side token and benefit operations (SOW: Token & Redemption Management, Vacation
 * Management, QR Code Management). The ledger stays append-only: reversals are new entries.
 */
class BenefitsService
{
    public function __construct(private TokenLedger $ledger) {}

    public function approveTransfer(TokenTransfer $transfer): void
    {
        DB::transaction(function () use ($transfer) {
            TokenTransfer::whereKey($transfer->id)->lockForUpdate()->firstOrFail();
            $transfer->refresh();
            $this->assertPending($transfer);
            TokenTransaction::where('reference', $transfer->reference)->where('status', 'pending')->update(['status' => 'posted']);
            $this->ledger->credit($transfer->recipient, $transfer->token_type, $transfer->quantity, 'transferred_in',
                "Transfer from {$transfer->sender->email}", reference: $transfer->reference);
            $transfer->update(['status' => 'completed']);
        });
        $transfer->sender->notify(new MemberAlert('Tokens', "Transfer completed · {$transfer->quantity} {$transfer->token_type}", "Sent to {$transfer->recipient->email}.", 'wallet', mail: true));
        $transfer->recipient->notify(new MemberAlert('Tokens', "+{$transfer->quantity} {$transfer->token_type} received", "Transfer from {$transfer->sender->name}.", 'wallet', mail: true));
    }

    public function rejectTransfer(TokenTransfer $transfer, ?string $reason): void
    {
        DB::transaction(function () use ($transfer) {
            TokenTransfer::whereKey($transfer->id)->lockForUpdate()->firstOrFail();
            $transfer->refresh();
            $this->assertPending($transfer);
            TokenTransaction::where('reference', $transfer->reference)->where('status', 'pending')->update(['status' => 'reversed']);
            $this->ledger->credit($transfer->sender, $transfer->token_type, $transfer->quantity, 'adjusted', 'Transfer not approved · tokens returned', reference: $transfer->reference);
            $transfer->update(['status' => 'rejected']);
        });
        $transfer->sender->notify(new MemberAlert('Tokens', 'Token transfer not approved', ($reason ?: 'Your tokens have been returned to your wallet.'), 'wallet', mail: true));
    }

    /** Confirm a stay: the applied DT are deducted now (SOW: deducted once confirmed). */
    public function confirmVacation(VacationRequest $vacation, ?string $instructions): void
    {
        DB::transaction(function () use ($vacation, $instructions) {
            VacationRequest::whereKey($vacation->id)->lockForUpdate()->firstOrFail();
            $vacation->refresh();
            abort_unless($vacation->status === 'pending', 422, 'Only pending requests can be confirmed.');
            User::whereKey($vacation->user_id)->lockForUpdate()->firstOrFail();
            $available = (int) max(0, $this->ledger->balance($vacation->user, TokenTransaction::DUES)); // the first stay confirmed is the one that uses the DT
            $tokens = (int) $vacation->tokens_applied;
            if ($tokens > $available) {
                throw ValidationException::withMessages(['tokens' => 'The available token balance has changed. Review the stay estimate before confirming.']);
            }
            if ($tokens > 0) {
                $this->ledger->debit($vacation->user, TokenTransaction::DUES, $tokens, 'used', "{$vacation->partner->name} stay · {$vacation->reference}", $vacation->reference);
            }
            $vacation->update([
                'status' => 'confirmed', 'tokens_applied' => $tokens, 'instructions' => $instructions,
                'payment_status' => $vacation->payment_amount > 0 ? 'pending' : 'paid',
            ]);
        });
        $vacation->user->notify(new MemberAlert('Vacations', "{$vacation->partner->name} stay confirmed",
            $vacation->check_in->format('M j').' – '.$vacation->check_out->format('M j, Y').($instructions ? ". {$instructions}" : ''), 'vacations', mail: true));
    }

    public function setVacationStatus(VacationRequest $vacation, string $status, ?string $note): void
    {
        DB::transaction(function () use ($vacation, $status) {
            VacationRequest::whereKey($vacation->id)->lockForUpdate()->firstOrFail();
            $vacation->refresh();
            $allowed = $vacation->status === 'pending' ? ['declined', 'cancelled'] : ($vacation->status === 'confirmed' ? ['completed', 'cancelled'] : []);
            abort_unless(in_array($status, $allowed, true), 422, 'This request has already been processed or the status transition is invalid.');
            // Cancelling a confirmed stay returns the deducted tokens
            if ($status === 'cancelled' && $vacation->status === 'confirmed' && $vacation->tokens_applied > 0) {
                $this->ledger->credit($vacation->user, TokenTransaction::DUES, $vacation->tokens_applied, 'adjusted', "Stay cancelled · {$vacation->reference}", reference: $vacation->reference);
            }
            $vacation->update(['status' => $status]);
        });
        $vacation->user->notify(new MemberAlert('Vacations', "Vacation request {$status}", $note ?: "{$vacation->partner->name} · {$vacation->reference}", 'vacations', mail: true));
    }

    public function adjustTokens(User $user, string $type, float $amount, string $reason): void
    {
        DB::transaction(function () use ($user, $type, $amount, $reason) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            if ($amount < 0 && abs($amount) > $this->ledger->balance($user, $type)) {
                throw ValidationException::withMessages(['amount' => 'The adjustment would make the balance negative.']);
            }
            $user->tokenTransactions()->create(['token_type' => $type, 'kind' => 'adjusted', 'amount' => $amount, 'description' => "Adjustment · {$reason}", 'reference' => 'ADMIN']);
        });
    }

    /** Why a member cannot hold/use a partner-benefit QR code, or null when eligible (SOW: verify eligibility first). */
    public function qrIneligibility(User $member): ?string
    {
        $member->loadMissing('membership');
        $owner = $member->isSubAccount() ? $member->parent : $member;
        if (! in_array($member->role, [User::ROLE_MEMBER, User::ROLE_PRODUCER], true)) {
            return 'QR codes are for members only.';
        }
        if ($member->status !== 'active' || $owner?->status !== 'active' || ! ($owner->membership?->isPaid() && $owner->membership->status === 'active')) {
            return 'The membership is not active.';
        }
        if ($member->isSubAccount() && ! $member->hasPermission('Partner benefits')) {
            return 'This sub-account has no partner-benefit permission.';
        }

        return null;
    }

    public function issueQr(User $user): MemberQrCode
    {
        if ($reason = $this->qrIneligibility($user)) {
            throw ValidationException::withMessages(['qr' => $reason]);
        }

        return DB::transaction(function () use ($user) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            MemberQrCode::where('user_id', $user->id)->update(['status' => 'inactive']);

            return MemberQrCode::create(['user_id' => $user->id, 'code' => (string) Str::uuid(), 'status' => 'active']);
        });
    }

    /**
     * Partner scan (SOW: Validate QR → member → eligibility → available credit → redemption).
     *
     * @return array{member: User, redemption: QrRedemption}
     */
    public function redeemQr(string $code, Partner $partner, int $amount, ?string $location): array
    {
        if (! $partner->is_active || $partner->is_vacation) {
            throw ValidationException::withMessages(['partner_id' => 'Choose an active benefit partner.']);
        }
        if ($amount < 1) {
            throw ValidationException::withMessages(['amount' => 'Enter a positive token amount.']);
        }
        $qr = MemberQrCode::where('code', trim($code))->first();
        $checks = [
            'code' => $qr !== null,
            'active' => $qr?->status === 'active',
        ];
        if (in_array(false, $checks, true)) {
            throw ValidationException::withMessages(['code' => $qr ? 'This QR code is not active.' : 'QR code not recognised.']);
        }

        $member = $qr->user;
        $owner = $member->isSubAccount() ? $member->parent : $member;
        if ($reason = $this->qrIneligibility($member)) {
            throw ValidationException::withMessages(['code' => $reason]);
        }
        if ($amount > $this->ledger->spendable($owner, TokenTransaction::DUES)) {
            throw ValidationException::withMessages(['amount' => 'Insufficient token balance.']);
        }

        $redemption = DB::transaction(function () use ($owner, $member, $partner, $amount, $location) {
            User::whereKey($owner->id)->lockForUpdate()->firstOrFail();
            if ($amount > $this->ledger->spendable($owner, TokenTransaction::DUES)) {
                throw ValidationException::withMessages(['amount' => 'Insufficient token balance.']);
            }
            $redemption = QrRedemption::create(['user_id' => $member->id, 'partner_id' => $partner->id, 'location' => $location, 'token_type' => 'DT', 'amount' => $amount]);
            $this->ledger->debit($owner, TokenTransaction::DUES, $amount, 'used', "QR redemption · {$partner->name}", $redemption->reference);

            return $redemption;
        });
        $member->notify(new MemberAlert('Tokens', "Benefit redeemed · {$amount} DT", trim("{$partner->name} {$location}"), 'qr', mail: true));

        return ['member' => $member, 'redemption' => $redemption];
    }

    private function assertPending(TokenTransfer $transfer): void
    {
        abort_unless($transfer->status === 'pending', 422, 'This transfer has already been processed.');
    }
}
