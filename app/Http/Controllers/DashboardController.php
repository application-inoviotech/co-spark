<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Member and producer portal shells. Their data comes from dashboard.data
 * (MemberDashboardPresenter) and producer.data (ProducerDashboardPresenter).
 * Producers also use the member dashboard for their required $8.88 membership.
 */
class DashboardController extends Controller
{
    public function member(Request $request): View
    {
        $user = $request->user();

        return view('dashboard.member', [
            'user' => $user,
            'planName' => $user->membership?->planConfig()['name'] ?? 'Free',
            'endpoints' => $this->memberEndpoints(),
        ]);
    }

    /** URLs used by assets/js/dashboard.js; __ID__ / __REF__ are filled in by the script. */
    private function memberEndpoints(): array
    {
        return [
            'data' => route('dashboard.data'),
            'acceptJs' => app(\App\Services\Billing\AuthorizeNet::class)->clientConfig(),
            'receipt' => route('dashboard.payments.receipt', '__REF__'),
            'payDues' => route('dashboard.payments.dues'),
            'payPremium' => route('dashboard.payments.premium'),
            'payVT' => route('dashboard.payments.vt'),
            'upgrade' => route('dashboard.payments.upgrade'),
            'retry' => route('dashboard.payments.retry', '__REF__'),
            'methodStore' => route('dashboard.methods.store'),
            'methodDefault' => route('dashboard.methods.default', '__ID__'),
            'methodDestroy' => route('dashboard.methods.destroy', '__ID__'),
            'frequency' => route('dashboard.membership.frequency'),
            'switchPlan' => route('dashboard.membership.switch'),
            'cancel' => route('dashboard.membership.cancel'),
            'transfer' => route('dashboard.transfers.store'),
            'vacationStore' => route('dashboard.vacations.store'),
            'vacationCancel' => route('dashboard.vacations.cancel', '__REF__'),
            'claimMessage' => route('dashboard.claims.message', '__REF__'),
            'claimUpload' => route('dashboard.claims.upload', '__REF__'),
            'noteRead' => route('dashboard.notifications.read', '__ID__'),
            'notesReadAll' => route('dashboard.notifications.readAll'),
            'subStore' => route('dashboard.subs.store'),
            'subStatus' => route('dashboard.subs.status', '__ID__'),
            'profile' => route('dashboard.profile.update'),
            'avatar' => route('dashboard.profile.avatar'),
            'password' => route('dashboard.profile.password'),
            'qrSend' => route('dashboard.qr.send'),
            'quote' => route('quote'),
            'fileClaim' => route('claims.create'),
            'resources' => route('resources.index'),
        ];
    }

    public function producer(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user->isProducer() || ! $user->producer) {
            return redirect()->to(route('producer').'#apply');
        }

        return view('dashboard.producer', [
            'user' => $user,
            'endpoints' => [
                'data' => route('producer.data'),
                'lead' => route('producer.leads.update', '__REF__'),
                'license' => route('producer.licenses.store'),
                'document' => route('producer.documents.store'),
                'profile' => route('producer.profile'),
                'vacation' => route('producer.vacations'),
                'redeem' => route('producer.bonus.redeem'),
                'export' => route('producer.commissions.export'),
                'noteRead' => route('dashboard.notifications.read', '__ID__'),
                'notesReadAll' => route('dashboard.notifications.readAll'),
                'membership' => route('dashboard').'#membership',
            ],
        ]);
    }
}
