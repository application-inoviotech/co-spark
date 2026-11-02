<?php

namespace App\Http\Controllers\Member;

use App\Models\Partner;
use App\Models\Producer;
use App\Models\TokenTransaction;
use App\Models\User;
use App\Models\VacationRequest;
use App\Notifications\MemberAlert;
use App\Services\BenefitsService;
use App\Services\TokenLedger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * SOW: Vacations Planning — request a stay at a COSPARK property with at least 90 days'
 * notice; tokens are deducted only once COSPARK confirms the booking.
 */
class VacationRequestController extends MemberController
{
    public function __construct(private TokenLedger $ledger) {}

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $owner = $user->isSubAccount() ? $user->parent : $user;
        abort_unless($owner?->status === 'active' && $owner->membership?->isPaid() && $owner->membership->status === 'active' && $user->hasPermission('Vacations'), 403, 'Vacation planning needs an active paid membership and permission.');

        $request->validate(['check_in' => ['required', 'date']]);

        $earliest = today()->addDays(VacationRequest::MIN_NOTICE_DAYS);
        $data = $request->validate([
            'property_id' => ['required', 'integer'],
            'check_in' => ['required', 'date', 'after_or_equal:'.$earliest->toDateString()],
            'check_out' => ['required', 'date', 'after:check_in', 'before:'.Carbon::parse($request->input('check_in'))->addDays(31)->toDateString()],
            'guests' => ['required', 'integer', 'between:1,'.config('membership.vacations.max_guests')],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'check_in.after_or_equal' => 'Choose a date at least 90 days from today ('.$earliest->format('M j, Y').').',
            'check_out.after' => 'Check-out must be after check-in.',
            'check_out.before' => 'Stays can be up to 30 nights.',
        ]);

        $property = Partner::active()->where('is_vacation', true)->findOrFail($data['property_id']);
        $nights = Carbon::parse($data['check_in'])->diffInDays($data['check_out']);
        $rate = (float) ($property->nightly_rate ?? config('membership.vacations.default_nightly_rate'));
        $perNight = (int) ($property->tokens_per_night ?? config('membership.vacations.default_tokens_per_night'));
        // SOW: DT are deducted only when a stay is confirmed, so other pending requests do not reduce this estimate
        $tokens = (int) min(max(0, $this->ledger->balance($user, TokenTransaction::DUES)), $nights * $perNight);
        // SOW: an approved producer gets 10% off stays here too, not only from the producer portal
        $discount = $user->producer?->isActive() ? Producer::VACATION_DISCOUNT : 0;
        $cost = round($nights * $rate * (1 - $discount), 2);

        // Locked per member so a double click can't create the same request twice
        $vacation = DB::transaction(function () use ($user, $property, $data, $tokens, $discount, $cost) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();

            $clash = $user->vacationRequests()->where('partner_id', $property->id)->whereIn('status', ['pending', 'confirmed'])
                ->whereDate('check_in', '<', $data['check_out'])->whereDate('check_out', '>', $data['check_in'])
                ->value('reference');
            if ($clash) {
                throw ValidationException::withMessages(['check_in' => "You already have a request for {$property->name} on these dates ({$clash})."]);
            }

            return $user->vacationRequests()->create([
                'partner_id' => $property->id,
                'check_in' => $data['check_in'],
                'check_out' => $data['check_out'],
                'guests' => $data['guests'],
                'notes' => $data['notes'] ?? null,
                'tokens_applied' => $tokens,
                'discount_rate' => $discount,
                'payment_amount' => max(0, $cost - $tokens),
            ]);
        });

        $user->notify(new MemberAlert('Vacations', 'Vacation request received', "{$property->name} · ".$vacation->check_in->format('M j, Y'), 'vacations', mail: true));

        return response()->json(['reference' => $vacation->reference], 201);
    }

    public function cancel(Request $request, VacationRequest $vacation, BenefitsService $benefits): JsonResponse
    {
        abort_unless($vacation->user_id === $request->user()->id, 404);
        abort_unless($vacation->isCancellable(), 422, 'This request can no longer be cancelled.');

        $benefits->setVacationStatus($vacation, 'cancelled', 'Cancelled by the member.');

        return response()->json(['ok' => true]);
    }
}
