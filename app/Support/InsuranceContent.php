<?php

namespace App\Support;

use App\Models\InsuranceProduct;

/** Resolve product copy before category fallbacks, including existing deployed records. */
class InsuranceContent
{
    public function forProduct(InsuranceProduct $product): array
    {
        $category = $product->category;
        $defaults = config('insurance_content.'.$product->slug, []);
        if (isset($defaults['coverage'])) {
            $defaults['coverage'] = array_map(fn ($row) => ['title' => $row[0], 'text' => $row[1]], $defaults['coverage']);
        }

        return array_replace([
            'coverage' => $category->coverage ?? [],
            'options' => $category->options ?? [],
            'benefits' => $category->benefits ?? [],
            'faqs' => $this->faqs($product),
        ], $defaults, $product->content ?? []);
    }

    private function faqs(InsuranceProduct $product): array
    {
        $slug = $product->slug;
        $group = $product->category->slug;
        $name = $product->short_name;

        if ($group === 'financial') {
            return [
                ['What fees and expenses apply?', 'Fees depend on the service, account and products selected. Ask for written disclosure of advisory fees, product expenses, commissions and any surrender charges before proceeding.'],
                ['How do I choose a service?', 'Discuss your goals, time horizon, risk tolerance and liquidity needs with an appropriately qualified professional. Investment returns are not guaranteed and principal can be lost.'],
                ['Are financial services part of an insurance bundle?', 'Financial services have separate agreements, fees and risks. Do not assume insurance discounts or membership token rewards apply.'],
                ['How do I get help with my account?', 'Contact your advisor or account provider for account servicing, withdrawals or questions about statements. Financial accounts are not serviced through the insurance claims form.'],
            ];
        }

        $price = match (true) {
            $group === 'life' => 'Premiums depend on age, health, tobacco use, the death benefit, policy type and any required underwriting. Life insurance does not have a deductible. Ask an agent to compare suitable options.',
            $slug === 'workers-comp' => 'Premiums are based on payroll, job classifications, the state and claims experience, along with insurer rating factors. Most standard policies do not have a deductible; special deductible arrangements depend on state rules and insurer approval.',
            $slug === 'medicare' => 'Costs depend on the type of Medicare coverage and plan selected. Compare premiums, deductibles, copayments, coinsurance, provider networks and drug coverage, and consider any income-related charges that apply.',
            $slug === 'health' => 'Premiums depend on the plan, location, ages and household members covered, and other permitted rating factors. Marketplace financial assistance depends on eligibility. Compare the deductible, cost sharing and out-of-pocket limit as well as the premium.',
            $slug === 'pet' => 'Premiums depend on your pet, location and plan. Compare deductibles, reimbursement percentages, annual limits, waiting periods and exclusions.',
            $slug === 'device-protection' => 'Cost depends on the device and protection plan. Review service fees, deductibles, claim limits, repair arrangements and exclusions before purchase.',
            $slug === 'flood' => 'Flood premiums depend on the property flood risk, coverage limits, deductibles and insurer. NFIP and private flood policies may have different pricing and terms.',
            default => 'Pricing depends on the risk being insured, location, claims history, selected limits and applicable deductibles. An agent can compare available options and explain policy terms.',
        };

        [$bundleQuestion, $bundle] = match (true) {
            $group === 'life' => ['Can I arrange life insurance alongside other policies?', 'An agent can coordinate life insurance with your other protection. Life premiums are priced separately; do not assume a multi-policy discount reduces your life insurance premium.'],
            $slug === 'workers-comp' => ['Can I arrange workers compensation alongside other business policies?', 'An agent can coordinate your business policies, but workers compensation is rated separately. Do not assume bundling reduces its premium.'],
            $slug === 'flood' => ['Does bundling reduce my flood premium?', 'NFIP premiums are not discounted for bundling with auto or home insurance. Private flood policy terms may differ; ask for a separate quote.'],
            $slug === 'medicare' => ['Does a multi-policy bundle reduce Medicare premiums?', 'Medicare plan premiums are not discounted for bundling with auto, home or other insurance. Compare plans based on their benefits, costs and your healthcare needs.'],
            $slug === 'health' => ['Can I arrange health coverage alongside other insurance?', 'Health coverage has separate benefits, eligibility and pricing. Do not assume an auto or home bundle changes health plan premiums.'],
            in_array($slug, ['auto-home', 'auto-renters'], true) => ['Can I add other policies to my auto and '.($slug === 'auto-home' ? 'home' : 'renters').' bundle?', 'Ask an agent which additional policies qualify for a multi-policy discount. Eligibility and savings vary by insurer and state, and each policy retains its own terms.'],
            in_array($slug, ['pet', 'device-protection'], true) => ['Can I arrange this protection alongside my other policies?', 'Partner plans have separate eligibility, prices and terms. Ask about available options; a multi-policy discount is not guaranteed.'],
            default => ["Can I bundle {$name} with other policies?", 'Multi-policy discounts may be available for eligible policies, depending on the insurer and state. Ask an agent to confirm which policies qualify and any savings.'],
        };

        $tokens = in_array($slug, ['medicare', 'health'], true)
            ? 'Do not assume this coverage is eligible for Insurance Tokens. No percentage-of-premium reward is promised for this plan. Any permitted benefits must be confirmed for the specific plan and applicable rules before enrollment; choose coverage based on your healthcare needs, benefits and costs.'
            : 'Insurance Tokens apply only to eligible policies and premiums under the membership program terms and applicable rules. Ask an agent to confirm eligibility and the current allocation rate before purchasing. Membership does not change the policy coverage or claim settlement.';

        $claim = match ($slug) {
            'medicare', 'health' => 'Providers usually submit medical claims to the plan. For reimbursement, billing disputes or appeals, follow the instructions from Medicare or your health plan and contact the plan using your member ID card.',
            'pet' => 'Follow the pet insurer claim process and submit the required veterinary invoices and records. Reimbursement depends on the policy terms.',
            'device-protection' => 'Contact the protection-plan administrator and follow its repair or replacement process. Check any authorization requirements and service fees before arranging repairs.',
            'workers-comp' => 'Report the work-related injury to the employer promptly and follow the insurer and state reporting instructions. Seek appropriate medical care and contact your agent for reporting assistance.',
            default => 'Report a claim through the Claims page or your member dashboard, or contact your insurer directly. An agent can help you find the appropriate reporting process.',
        };

        return [
            ["How much does {$name} coverage cost?", $price],
            [$bundleQuestion, $bundle],
            ['Do I earn COSPARK tokens on this policy?', $tokens],
            ['How do I file a claim?', $claim],
        ];
    }
}
