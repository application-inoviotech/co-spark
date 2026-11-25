<?php

// Product defaults for the November 2026 content review. Product CMS edits override these.
// Entries use [title, description] pairs; coverage is deliberately not inherited across lines.
return [
    'car' => [
        'options' => ['Liability limits', 'Collision and comprehensive deductibles', 'Uninsured and underinsured motorist coverage where available', 'Rental reimbursement and roadside assistance where available', 'Gap coverage for eligible financed vehicles'],
    ],
    'motorcycle' => [
        'coverage' => [
            ['Liability', 'Coverage for covered injuries or property damage you cause while riding, subject to policy limits.'],
            ['Collision and comprehensive', 'Optional protection for damage to your motorcycle from a collision, theft or other covered events.'],
            ['Rider protection', 'Uninsured or underinsured motorist and medical payments coverage may be available, depending on your state and insurer.'],
        ],
        'options' => ['Custom parts and accessories', 'Safety apparel coverage', 'Roadside assistance for motorcycles', 'Passenger coverage where available'],
    ],
    'boat' => [
        'coverage' => [
            ['Hull and equipment', 'Protection for covered physical damage to your boat, motor and insured equipment, subject to the policy valuation and deductible.'],
            ['Boating liability', 'Coverage for covered injuries or property damage you cause while operating your boat.'],
            ['Uninsured boater', 'Optional protection for covered injuries caused by an uninsured boater, where available.'],
            ['On-water towing', 'Optional assistance for covered towing or service on the water, subject to service limits.'],
        ],
        'options' => ['Hull valuation and navigation limits', 'Fishing equipment and personal effects where available', 'Trailer coverage', 'On-water towing and assistance', 'Medical payments where available'],
    ],
    'atv' => [
        'coverage' => [
            ['Off-road liability', 'Coverage for covered injuries or property damage you cause while operating an insured ATV or UTV.'],
            ['Physical damage', 'Optional collision and comprehensive protection for your off-road vehicle.'],
            ['Rider protection', 'Medical payments and uninsured rider coverage may be available, subject to state and policy terms.'],
        ],
        'options' => ['Accessories and custom equipment', 'Trailer coverage where available', 'Permitted-use and riding-area review', 'Deductible choices'],
    ],
    'other-rec' => [
        'coverage' => [
            ['Recreational vehicle liability', 'Protection for covered injuries or property damage involving an eligible insured recreational vehicle.'],
            ['Physical damage', 'Collision and comprehensive options depend on the vehicle, how it is used and the insurer.'],
            ['Equipment', 'Coverage for attached equipment or accessories may be available when listed on the policy.'],
        ],
        'options' => ['Vehicle-specific protection', 'Custom equipment where available', 'Storage and seasonal-use review', 'Deductible choices'],
    ],
    'rv' => [
        'coverage' => [
            ['RV physical damage', 'Collision and comprehensive protection for an insured motor home or travel trailer, subject to policy terms.'],
            ['Liability', 'Motor homes may need road-use liability; vacation or full-time liability options depend on how you use the RV.'],
            ['Personal effects', 'Coverage for belongings carried in the RV may be available with separate limits.'],
        ],
        'options' => ['Vacation liability', 'Full-time use where eligible', 'Personal effects limits', 'RV roadside assistance', 'Emergency expense coverage where available'],
    ],
    'renters' => [
        'coverage' => [
            ['Personal property', 'Protects your belongings against covered losses, subject to limits and deductibles. The rented building is not insured by your renters policy.'],
            ['Personal liability', 'Protection for covered claims that you caused injury or property damage to others.'],
            ['Additional living expenses', 'Helps with extra living costs if a covered loss makes your rental uninhabitable.'],
            ['Medical payments to others', 'Limited coverage for qualifying guest injuries, subject to policy terms.'],
        ],
    ],
    'auto-renters' => [
        'coverage' => [
            ['Auto liability', 'Your auto policy provides selected liability limits for covered injuries or property damage.'],
            ['Vehicle damage', 'Collision and comprehensive coverage can be added to the auto policy, with separate deductibles.'],
            ['Renters personal property', 'The renters policy protects your belongings against covered losses. It does not insure the rented building.'],
            ['Renters liability and living expenses', 'The renters policy can include personal liability and additional living expenses after a covered loss. Each policy has its own terms.'],
        ],
        'options' => ['Auto liability and vehicle damage limits', 'Renters personal property limits', 'Replacement cost on renters contents where available', 'Separate auto and renters deductibles', 'Scheduled valuables on the renters policy where available'],
    ],
    'auto-home' => [
        'coverage' => [
            ['Auto protection', 'Auto liability and optional collision and comprehensive protection are provided by the auto policy.'],
            ['Home and belongings', 'The homeowners policy covers the dwelling, eligible other structures and personal property against covered losses.'],
            ['Home liability and living expenses', 'The homeowners policy can include personal liability and additional living expenses after a covered loss. Each policy has separate terms and limits.'],
        ],
        'options' => ['Auto liability and vehicle damage limits', 'Home dwelling and personal property limits', 'Separate deductibles for each policy', 'Home endorsements where available'],
    ],
    'condo' => [
        'coverage' => [
            ['Unit interior and improvements', 'Protection for the parts of your unit you are responsible for insuring under the association documents. Coordinate this with the HOA master policy.'],
            ['Personal property', 'Covers your belongings against covered losses, subject to limits and deductibles.'],
            ['Personal liability', 'Protection for covered claims that you caused injury or property damage to others.'],
            ['Additional living expenses', 'Helps with extra living costs if a covered loss makes your unit uninhabitable.'],
        ],
        'options' => ['Unit improvements and betterments limits', 'Loss assessment coverage where available', 'Replacement cost on contents where available', 'Scheduled personal property', 'Deductible review alongside the HOA master policy'],
    ],
    'landlord' => [
        'coverage' => [
            ['Rental building', 'Protection for an insured rental building against covered losses.'],
            ['Landlord-owned property', 'Coverage may include appliances or equipment you own and provide to service the rental. Tenants need their own renters insurance for their belongings.'],
            ['Loss of rental income', 'Helps replace covered lost rent when an insured loss makes the rental uninhabitable, subject to policy limits.'],
            ['Landlord liability', 'Protection for covered liability claims related to the insured rental property.'],
        ],
        'options' => ['Building valuation and deductibles', 'Rental income limits', 'Landlord-owned equipment where available', 'Premises liability limits', 'Ordinance or law coverage where available'],
    ],
    'vacant-home' => [
        'coverage' => [
            ['Vacant building', 'Protection for an unoccupied building against the causes of loss listed in the vacant-property policy.'],
            ['Other structures', 'Eligible detached structures may be insured when listed on the policy.'],
            ['Premises liability', 'Liability coverage may be available for the vacant property. Confirm inspections, security requirements and exclusions with your insurer.'],
        ],
        'options' => ['Policy term matched to the vacancy', 'Named-peril or broader coverage where available', 'Vandalism coverage where available', 'Renovation and occupancy review', 'Premises liability where available'],
    ],
    'flood' => [
        'coverage' => [
            ['Building coverage', 'Insures eligible building components against direct physical loss from a covered flood. Limits, deductibles and settlement conditions apply.'],
            ['Contents coverage', 'Purchased separately from building coverage. NFIP contents losses are settled at actual cash value, which accounts for depreciation.'],
            ['Coverage boundaries', 'NFIP coverage does not include additional living expenses, personal liability or medical payments. Private flood policies may have different terms.'],
        ],
        'options' => ['Building coverage limits', 'Contents coverage limits', 'Separate building and contents deductibles', 'NFIP or private flood policy comparison', 'Waiting periods and effective-date review'],
        'benefits' => ['Help comparing flood coverage and exclusions', 'Digital policy information in your dashboard', 'Agent support for flood claims', 'NFIP premiums are not discounted for bundling with auto or home insurance'],
    ],
    'earthquake' => [
        'coverage' => [
            ['Earthquake damage to the dwelling', 'Coverage for eligible damage to your insured home caused by an earthquake, subject to limits and an earthquake deductible.'],
            ['Personal property', 'Coverage for covered earthquake damage to your belongings when included or selected.'],
            ['Additional living expenses', 'Helps with extra living costs if covered earthquake damage makes your home uninhabitable, subject to policy terms.'],
        ],
        'options' => ['Dwelling and personal property limits', 'Earthquake deductible choices', 'Additional living expense limits', 'Building code upgrade coverage where available', 'Breakable items coverage where available'],
    ],
    'life' => [
        'options' => ['Compare term and permanent life insurance', 'Death benefit amount', 'Beneficiary selection', 'Available living benefit riders', 'Waiver of premium rider where available'],
    ],
    'term-life' => [
        'coverage' => [
            ['Death benefit', 'Pays the named beneficiaries if the insured dies while the term policy is in force, subject to policy terms.'],
            ['Coverage for a set term', 'Protection for a selected number of years. Standard term life does not build cash value.'],
            ['Optional riders', 'Conversion, accelerated benefit or waiver of premium provisions may be available, depending on the insurer.'],
        ],
        'options' => ['Available term lengths', 'Death benefit amount', 'Level premium period', 'Conversion privilege where available', 'Accelerated benefit rider where available'],
    ],
    'whole-life' => [
        'coverage' => [
            ['Lifetime death benefit', 'Permanent protection when required premiums are paid and the policy remains in force.'],
            ['Guaranteed cash value', 'Cash value grows according to the policy schedule. Loans and withdrawals can reduce benefits and may have tax consequences.'],
            ['Level premiums', 'Traditional whole life generally has scheduled level premiums. Participating policy dividends are not guaranteed.'],
        ],
        'options' => ['Death benefit amount', 'Premium payment schedule', 'Paid-up additions where available', 'Waiver of premium rider where available', 'Accelerated benefit rider where available'],
    ],
    'universal-life' => [
        'coverage' => [
            ['Permanent death benefit', 'Protection that can continue while policy funding and other in-force requirements are met.'],
            ['Flexible funding', 'Premium and death benefit changes may be permitted within policy limits. Inadequate funding can cause the policy to lapse.'],
            ['Cash value', 'Cash value reflects premiums, charges and credited interest under the contract. Loans or withdrawals can reduce benefits and may have tax consequences.'],
        ],
        'options' => ['Death benefit options', 'Funding and premium flexibility', 'Interest-crediting provisions', 'No-lapse guarantees where available', 'Optional riders offered by the insurer'],
    ],
    'commercial-auto' => [
        'coverage' => [
            ['Business auto liability', 'Coverage for covered bodily injury or property damage caused by an insured business vehicle.'],
            ['Vehicle physical damage', 'Optional collision and comprehensive coverage for scheduled vehicles.'],
            ['Driver and passenger protection', 'Uninsured motorist, medical payments or personal injury protection may be available or required, depending on the state.'],
        ],
        'options' => ['Owned vehicle schedules', 'Hired and non-owned auto liability where available', 'Physical damage deductibles', 'Liability limits', 'Business vehicle roadside assistance where available'],
    ],
    'commercial-property' => [
        'coverage' => [
            ['Business building', 'Protection for an insured building against covered causes of loss.'],
            ['Business personal property', 'Coverage for insured equipment, inventory and fixtures at covered locations.'],
            ['Business income and extra expense', 'Available when included or endorsed, following a covered property loss and subject to applicable waiting periods and limits.'],
        ],
        'options' => ['Building and business property limits', 'Replacement cost or actual cash value valuation', 'Business income and extra expense where available', 'Equipment breakdown endorsement where available', 'Ordinance or law coverage where available'],
    ],
    'workers-comp' => [
        'coverage' => [
            ['Work-related medical care', 'Benefits for covered employee injuries or occupational illnesses under applicable state law.'],
            ['Wage replacement', 'Statutory benefits for qualifying lost wages after a covered work-related injury or illness.'],
            ['Rehabilitation and survivor benefits', 'Benefits may include rehabilitation and payments to eligible dependents, as provided by state law.'],
            ['Employers liability', 'Commonly included alongside workers compensation, subject to the policy and state requirements.'],
        ],
        'options' => ['Accurate payroll and job classifications', 'Employers liability limits where applicable', 'State-specific requirements', 'Pay-as-you-go billing where offered', 'Safety and return-to-work support where available'],
    ],
    'general-liability' => [
        'coverage' => [
            ['Bodily injury and property damage', 'Protection for covered third-party injury or property damage claims arising from business operations.'],
            ['Personal and advertising injury', 'Coverage for specified offenses, subject to policy definitions and exclusions.'],
            ['Defense of covered claims', 'Legal defense subject to policy terms. This policy does not replace commercial property, commercial auto, workers compensation or professional liability coverage.'],
        ],
        'options' => ['Per-occurrence and aggregate limits', 'Products-completed operations limits', 'Additional insured endorsements where available', 'Contractual coverage review', 'Premises and operations review'],
    ],
    'pet' => [
        'coverage' => [
            ['Accidents', 'Reimbursement for eligible veterinary treatment after a covered accident, subject to the partner policy.'],
            ['Illnesses', 'Eligible veterinary treatment for covered illnesses. Pre-existing conditions, waiting periods and exclusions apply.'],
            ['Veterinary treatment', 'Diagnostics, surgery and medications may be included for covered conditions. Check the benefit schedule.'],
        ],
        'options' => ['Accident-only or accident-and-illness plans where available', 'Reimbursement percentage', 'Annual benefit limit and deductible', 'Optional wellness benefits where offered'],
    ],
    'device-protection' => [
        'coverage' => [
            ['Accidental damage', 'Repair or replacement for eligible accidental damage, such as a cracked screen, if included in the partner plan.'],
            ['Mechanical or electrical breakdown', 'Protection for eligible breakdowns according to the service contract or policy. Manufacturer warranty terms may also apply.'],
            ['Theft protection', 'Available only if expressly included. Device eligibility, exclusions, service fees and claim limits depend on the plan.'],
        ],
        'options' => ['Eligible phones, tablets or laptops', 'Accidental damage protection', 'Theft protection where available', 'Repair or replacement terms', 'Service fee and claim limit review'],
    ],
    'medicare' => [
        'coverage' => [
            ['Medicare Advantage', 'Private plans provide Part A and Part B benefits; many include drug coverage. Networks, cost sharing and extra benefits vary by plan.'],
            ['Medicare Supplement', 'Medigap helps pay certain Original Medicare out-of-pocket costs. It is separate from Medicare Advantage.'],
            ['Part D prescription drug coverage', 'Stand-alone drug plans have their own formularies, pharmacy networks and cost sharing. Review your prescriptions before selecting a plan.'],
        ],
        'options' => ['Original Medicare with Medigap and Part D, or Medicare Advantage', 'Provider and pharmacy networks', 'Prescription formularies', 'Premiums and out-of-pocket costs', 'Eligibility and enrollment periods'],
        'benefits' => ['Help comparing available Medicare plan options', 'Review of doctors, pharmacies and prescriptions', 'Enrollment guidance from an appropriately licensed agent', 'Plan benefits and costs are separate from COSPARK membership'],
    ],
    'health' => [
        'coverage' => [
            ['Medical care', 'Coverage for eligible doctor visits, hospital care and other medical services under the selected health plan.'],
            ['Preventive care', 'Eligible preventive services are covered according to plan terms and applicable requirements.'],
            ['Prescription drugs', 'Coverage depends on the plan formulary, network pharmacies and applicable cost sharing.'],
        ],
        'options' => ['Individual or family coverage', 'Provider networks', 'Premiums, deductibles and out-of-pocket limits', 'Prescription drug formulary', 'Marketplace eligibility and enrollment periods'],
        'benefits' => ['Help comparing available health plans', 'Provider and prescription coverage review', 'Guidance on enrollment and eligibility', 'Plan benefits and costs are separate from COSPARK membership'],
    ],
    'financial-solutions' => [
        'coverage' => [
            ['Retirement planning', 'Discuss savings goals, time horizons and potential retirement income needs with an appropriately qualified professional.'],
            ['Savings strategies', 'Compare approaches for education and other long-term goals, including costs, restrictions and risks.'],
            ['Protection review', 'Coordinate financial goals with insurance needs. Any annuity or insurance contract has its own fees and terms.'],
        ],
        'options' => ['Goal and time-horizon review', 'Risk tolerance discussion', 'Fee and expense disclosure', 'Account and beneficiary review'],
    ],
    'asset-management' => [
        'coverage' => [
            ['Investment objectives', 'Discuss your goals, time horizon and risk tolerance with an appropriately qualified professional.'],
            ['Portfolio strategy', 'Review asset allocation and diversification. Investing involves risk, including possible loss of principal.'],
            ['Ongoing review', 'Discuss monitoring, rebalancing and service arrangements, including all fees and expenses.'],
        ],
        'options' => ['Investment objectives and risk tolerance', 'Asset allocation review', 'Account and service arrangements', 'Fees and expense disclosure', 'Periodic portfolio reviews'],
    ],
];
