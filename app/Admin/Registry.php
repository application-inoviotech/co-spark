<?php

namespace App\Admin;

/** All admin sections, in sidebar order. */
class Registry
{
    /** @var array<int, class-string<resource>> */
    private const RESOURCES = [
        Resources\UserResource::class,
        Resources\MembershipResource::class,
        Resources\PolicyResource::class,
        Resources\ClaimResource::class,
        Resources\LeadResource::class,
        Resources\PaymentResource::class,
        Resources\TokenTransactionResource::class,
        Resources\TokenTransferResource::class,
        Resources\ProducerApplicationResource::class,
        Resources\ProducerResource::class,
        Resources\CommissionResource::class,
        Resources\VacationRequestResource::class,
        Resources\PartnerResource::class,
        Resources\QrCodeResource::class,
        Resources\AppointmentResource::class,
        Resources\ContactMessageResource::class,
        Resources\PageResource::class,
        Resources\MembershipPlanResource::class,
        Resources\InsuranceProductResource::class,
        Resources\InsuranceCategoryResource::class,
        Resources\ArticleResource::class,
        Resources\ArticleCategoryResource::class,
        Resources\ClaimTypeResource::class,
    ];

    /**
     * Not offered in the sidebar for now (website content and plans are not handed over yet). The sections
     * still exist, so remove a line here to show it again.
     *
     * @var array<int, class-string<resource>>
     */
    private const HIDDEN_FROM_NAV = [
        // Resources\PageResource::class,
        // Resources\MembershipPlanResource::class,
        // Resources\InsuranceProductResource::class,
        // Resources\InsuranceCategoryResource::class,
        // Resources\ArticleResource::class,
        // Resources\ArticleCategoryResource::class,
        // Resources\ClaimTypeResource::class,
    ];

    public function get(string $key): Resource
    {
        foreach (self::RESOURCES as $class) {
            if ($class::key() === $key) {
                return app($class);
            }
        }
        abort(404);
    }

    /** Sidebar: [group => [[label, url]]] */
    public function navigation(): array
    {
        $nav = [];
        foreach (array_diff(self::RESOURCES, self::HIDDEN_FROM_NAV) as $class) {
            $nav[$class::$group][] = ['label' => $class::$label, 'href' => route('admin.resources.index', $class::key()), 'key' => $class::key()];
        }

        return $nav;
    }
}
