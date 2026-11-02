<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClaimRequest;
use App\Models\Claim;
use App\Models\ClaimType;
use App\Notifications\SubmissionReceived;
use App\Services\SubmissionNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** Claims Center, claim-type guidance and File a Claim. */
class ClaimController extends Controller
{
    public function __construct(private SubmissionNotifier $notifier) {}

    public function index(Request $request): View
    {
        $claimTypes = ClaimType::orderBy('sort_order')->get();
        $requested = $request->query('type');

        return view('claims.index', [
            'claimTypes' => $claimTypes,
            'activeType' => $claimTypes->contains('slug', $requested) ? $requested : 'auto',
        ]);
    }

    public function create(Request $request): View
    {
        $request->validate(['policy' => ['nullable', 'integer', 'min:1'], 'type' => ['nullable', 'string', 'max:40']]);
        $user = $request->user();
        $policy = null;
        if ($user && (! $user->isSubAccount() || $user->hasPermission('View policies'))) {
            $owner = $user->isSubAccount() ? $user->parent : $user;
            // Same rule as StoreClaimRequest: any of the member's policies that isn't cancelled
            $policies = $owner?->policies()->where('status', '!=', 'cancelled');
            $choices = $policies ? (clone $policies)->orderBy('type')->get()->map(fn ($p) => ['no' => $p->policy_number, 'type' => $p->type])->all() : [];
            if ($request->filled('policy')) {
                $policy = $policies?->whereKey($request->query('policy'))->first();
                abort_unless($policy, 404);
            } else {
                $type = ['auto' => 'Auto', 'glass' => 'Auto', 'home' => 'Home', 'renters' => 'Renters', 'condo' => 'Condo', 'business' => 'Commercial', 'commercial' => 'Commercial', 'life' => 'Life', 'health' => 'Health', 'umbrella' => 'Umbrella', 'other' => 'Other'][$request->query('type', '')] ?? null;
                $matches = $policies?->when($type, fn ($q) => $q->where('type', $type))->limit(2)->get();
                $policy = $matches?->count() === 1 ? $matches->first() : null;
            }
        } elseif ($request->filled('policy')) {
            abort(404);
        }

        return view('claims.create', [
            // Signed-in members get their contact details filled in (SOW: auto-populate profile).
            'prefill' => $user ? [
                'first' => $user->first_name ?? $user->name,
                'last' => $user->last_name ?? '',
                'email' => $user->email,
                'phone' => $user->phone ?? '',
                'pref' => 'Email',
                'policy' => $policy?->policy_number ?? '',
                'itype' => $policy?->claimType(),
                // Their policy numbers to pick from, so they never have to type one
                'policies' => $choices ?? [],
            ] : null,
        ]);
    }

    public function store(StoreClaimRequest $request): JsonResponse
    {
        $data = $request->validated();

        $claim = DB::transaction(function () use ($request, $data) {
            $claim = Claim::create([
                'user_id' => $request->user()?->id,
                'first_name' => $data['first'],
                'last_name' => $data['last'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'preferred_contact' => $data['pref'],
                'policy_id' => $request->policy?->id,
                'policy_number' => $request->policy?->policy_number ?? $data['policy'],
                'insurance_type' => $data['itype'],
                'claim_type' => $data['ctype'],
                'incident_date' => $data['date'],
                'incident_time' => $data['time'] ?? null,
                'location' => $data['loc'],
                'description' => $data['desc'],
                'comments' => $data['comments'] ?? null,
                'details' => $request->details(),
            ]);
            $claim->storeAttachments($request->uploadedFiles(), 'claims');

            return $claim;
        });

        $this->notifier->notify(
            new SubmissionReceived('Your claim has been submitted', $claim->first_name, $claim->reference, [
                "We received your {$claim->insurance_type} claim ({$claim->claim_type}).",
                'An adjuster will contact you within one business day.',
            ]),
            $claim->email,
            $claim->phone,
            'claim',
            ['Policy' => $claim->policy_number, 'Type' => "{$claim->insurance_type} · {$claim->claim_type}", 'Incident date' => $claim->incident_date->toFormattedDateString(), 'Name' => "{$claim->first_name} {$claim->last_name}"],
        );

        return response()->json(['reference' => $claim->reference], 201);
    }
}
