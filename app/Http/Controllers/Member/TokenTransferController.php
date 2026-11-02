<?php

namespace App\Http\Controllers\Member;

use App\Models\TokenTransaction;
use App\Models\TokenTransfer;
use App\Models\User;
use App\Notifications\MemberAlert;
use App\Notifications\NewSubmission;
use App\Services\TokenLedger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * SOW: Token Transfer — to another registered member, recorded with sender, recipient, type,
 * quantity, date and status; held as "pending review" until COSPARK completes it.
 */
class TokenTransferController extends MemberController
{
    public function __construct(private TokenLedger $ledger) {}

    public function store(Request $request): JsonResponse
    {
        $sender = $this->paidMember($request);
        $data = $request->validate([
            'email' => ['required', 'email:filter'],
            'token_type' => ['required', Rule::in([TokenTransaction::DUES, TokenTransaction::INSURANCE])],
            'quantity' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:255'],
            'indemnity' => ['accepted'],
        ]);

        $recipient = User::where('email', strtolower($data['email']))->where('role', User::ROLE_MEMBER)->where('status', 'active')->first();
        if (! $recipient || $recipient->is($sender)) {
            throw ValidationException::withMessages(['email' => "Enter another registered member's email."]);
        }

        $transfer = DB::transaction(function () use ($sender, $recipient, $data) {
            // lock the sender's ledger rows so two requests can't overspend the balance
            User::whereKey($sender->id)->lockForUpdate()->firstOrFail();
            if ($data['quantity'] > $this->ledger->spendable($sender, $data['token_type'])) {
                throw ValidationException::withMessages(['quantity' => 'Enter a quantity within your balance.']);
            }

            $transfer = TokenTransfer::create([
                'sender_id' => $sender->id,
                'recipient_id' => $recipient->id,
                'token_type' => $data['token_type'],
                'quantity' => $data['quantity'],
                'note' => $data['note'] ?? null,
                'indemnity_accepted_at' => now(),
            ]);
            $this->ledger->debit($sender, $data['token_type'], $data['quantity'], 'transferred_out',
                "Transfer to {$recipient->email} · pending review", $transfer->reference, 'pending');

            return $transfer;
        });

        $sender->notify(new MemberAlert('Tokens', "Transfer requested · {$transfer->quantity} {$transfer->token_type}", "To {$recipient->email} · pending review", 'wallet'));
        // SOW: token transfer requests are handled through the configured transfer mailbox
        if ($mailbox = config('cospark.office.token_transfer_email')) {
            Notification::route('mail', $mailbox)->notify(new NewSubmission('token transfer', $transfer->reference, [
                'From' => "{$sender->name} · {$sender->email}", 'To' => "{$recipient->name} · {$recipient->email}",
                'Tokens' => "{$transfer->quantity} {$transfer->token_type}", 'Note' => $transfer->note ?: '—',
            ]));
        }

        return response()->json(['reference' => $transfer->reference], 201);
    }
}
