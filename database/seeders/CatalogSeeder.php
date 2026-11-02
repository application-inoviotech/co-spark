<?php

namespace Database\Seeders;

use App\Models\ClaimType;
use App\Models\InsuranceCategory;
use App\Models\InsuranceProduct;
use App\Models\Partner;
use App\Models\Resource;
use App\Models\ResourceCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Initial website content, taken from the approved prototype (assets/js/data.js,
 * insurance.html and claims.html). After launch it is managed from the admin CMS.
 */
class CatalogSeeder extends Seeder
{
    private function img(string $id): string
    {
        return "https://images.unsplash.com/{$id}?auto=format&fit=crop&w=1600&q=70";
    }

    public function run(): void
    {
        $this->seedInsurance();
        $this->seedResources();
        $this->seedPartners();
        $this->seedClaimTypes();
    }

    private function images(): array
    {
        return array_map(fn ($id) => $this->img($id), [
            'home' => 'photo-1600585154340-be6161a56a0c', 'home2' => 'photo-1613490493576-7fde63acd811', 'street' => 'photo-1570129477492-45c003edd2be',
            'car' => 'photo-1503376780353-7e6692767b70', 'car2' => 'photo-1492144534655-ae79c964c9d7', 'moto' => 'photo-1558981806-ec527fa84c39',
            'boat' => 'photo-1567899378494-47b22a2ae96a', 'classic' => 'photo-1494905998402-395d579af36f', 'rv' => 'photo-1523987355523-c7b5b0dd90a7',
            'condo' => 'photo-1545324418-cc1a3fa10c00', 'interior' => 'photo-1502672260266-1c1ef2d93688', 'rain' => 'photo-1534274988757-a28bf1a57c17',
            'family' => 'photo-1511895426328-dc8714191300', 'office' => 'photo-1497366216548-37526070297c', 'tower' => 'photo-1486406146926-c627a92ad1ab',
            'health' => 'photo-1505751172876-fa1923c5c528', 'beach' => 'photo-1507525428034-b723cf961d3e', 'villa' => 'photo-1499793983690-e29da59ef1c2',
            'dog' => 'photo-1543466835-00a7907e9de1', 'phone' => 'photo-1511707171634-5f897ff02aa9', 'finance' => 'photo-1554224155-6726b3ff858f',
            'truck' => 'photo-1601584115197-04ecc0da31d7', 'crew' => 'photo-1504307651254-35680f356dfd', 'cabin' => 'photo-1449158743715-0a90ebb6d2d8',
            'city' => 'photo-1477959858617-67f85cf4f1df', 'ocean' => 'photo-1500375592092-40eb2168fd21', 'cafe' => 'photo-1517248135467-4c7edcad34c4',
            'mobile' => 'photo-1449844908441-8829872d2607',
        ]);
    }

    private function seedInsurance(): void
    {
        $categories = [
            'vehicle' => ['Vehicle', 'auto', 'Cars, bikes, boats and everything with an engine.',
                [['Liability', 'Pays for injuries and property damage you cause to others, up to your limits.'], ['Collision', 'Repairs or replaces your vehicle after an accident, regardless of fault.'], ['Comprehensive', 'Covers theft, vandalism, fire, hail, flood and animal strikes.'], ['Uninsured motorist', 'Protects you when the other driver has little or no insurance.'], ['Medical payments', 'Helps with medical bills for you and your passengers.'], ['Roadside & rental', 'Towing, lockouts, jump-starts and a rental while yours is repaired.']],
                ['Agreed or stated value', 'Accessory & custom equipment', 'Gap coverage for financed vehicles', 'Trip interruption', 'Glass with zero deductible'],
                ['Multi-policy savings when you bundle', 'Policies and ID cards in your member dashboard', 'Earn Insurance Tokens on eligible annual premium', 'Claims help from a local COSPARK agent']],
            'property' => ['Property', 'home', 'Homes, condos, rentals and the places you care for — plus bundles that save.',
                [['Dwelling', 'Repairs or rebuilds the structure after a covered loss.'], ['Other structures', 'Covers detached garages, fences and sheds.'], ['Personal property', 'Protects belongings like furniture, clothing and electronics.'], ['Loss of use', "Helps with living costs if you can't stay in your home."], ['Personal liability', 'Covers you if someone is injured on your property.'], ['Medical payments', "Pays guests' minor medical bills regardless of fault."]],
                ['Replacement cost on contents', 'Water backup', 'Scheduled jewelry & valuables', 'Identity protection', 'Equipment breakdown'],
                ['Bundle with auto and save', 'Digital declarations in your dashboard', 'Earn Insurance Tokens on eligible annual premium', 'Catastrophe support when severe weather hits']],
            'life' => ['Life', 'life', 'Coverage that helps your family stay secure.',
                [['Death benefit', 'A tax-advantaged payment to your beneficiaries.'], ['Living benefits', 'Some policies allow access to benefits during a qualifying illness.'], ['Cash value', 'Permanent policies build value you can borrow against.'], ['Riders', 'Add waiver of premium, child term or accidental death coverage.']],
                ['Level or decreasing term', 'Return of premium', 'Child term rider', 'Accelerated benefit rider'],
                ['Coverage options for every stage of life', 'Help choosing the right amount', 'Earn Insurance Tokens on eligible annual premium', 'Beneficiary support from a dedicated agent']],
            'commercial' => ['Commercial', 'business', 'Insurance built around how your business operates.',
                [['General liability', 'Covers third-party injury, property damage and advertising injury.'], ['Commercial property', 'Protects your building, equipment, inventory and fixtures.'], ['Business income', 'Replaces lost income while you recover from a covered loss.'], ['Commercial auto', 'Covers vehicles used for business purposes.'], ["Workers' compensation", 'Medical care and lost wages for employees injured on the job.'], ['Cyber & professional', 'Data breach response and errors & omissions protection.']],
                ["Business owner's policy (BOP)", 'Umbrella / excess liability', 'Inland marine', 'Employment practices liability', 'Hired & non-owned auto'],
                ['Coverage built around your industry', 'Certificates of insurance on request', 'Earn Insurance Tokens on eligible annual premium', 'One agent for every policy you hold']],
            'financial' => ['Financial Products', 'life', 'Plan, grow and protect your wealth.',
                [['Retirement planning', "Strategies for income you can't outlive."], ['Annuities', 'Fixed and indexed options for steady growth.'], ['College savings', 'Plans that help you prepare for education costs.'], ['Asset protection', 'Coordinate insurance and investments to protect your wealth.']],
                ['One-on-one planning session', 'Annual portfolio review', 'Beneficiary review'],
                ['A single plan across insurance and investments', 'Meet in person or by phone', 'Members receive priority scheduling']],
            'partner' => ['Partner Products', 'home', 'Products from vetted COSPARK partners.',
                [['Accidents & illness', 'Coverage for unexpected events, as defined by the partner plan.'], ['Repairs & replacement', 'Fast replacement or repair of covered items.'], ['Routine options', 'Optional add-ons for routine care or maintenance.']],
                ['Partner-specific plans', 'Flexible deductibles'],
                ['Products from vetted COSPARK partners', 'Ask a COSPARK agent before you buy', 'Access partner benefits from your dashboard']],
            'more' => ['More', 'home', 'Extra protection when you need higher limits.',
                [['Excess liability', 'Adds liability limits above your auto, home and boat policies.'], ['Worldwide coverage', 'Protection that travels with you.'], ['Legal defense', 'Defense costs are paid in addition to your limits.']],
                ['$1M to $5M limits', 'Uninsured motorist extension'],
                ['Affordable extra protection', 'Bundles with your existing policies']],
        ];

        $categoryIds = [];
        $order = 0;
        foreach ($categories as $slug => [$name, $quoteType, $lead, $coverage, $options, $benefits]) {
            $categoryIds[$slug] = InsuranceCategory::updateOrCreate(['slug' => $slug], [
                'name' => $name,
                'quote_type' => $quoteType,
                'lead' => $lead,
                'coverage' => array_map(fn ($c) => ['title' => $c[0], 'text' => $c[1]], $coverage),
                'options' => $options,
                'benefits' => $benefits,
                'sort_order' => $order++,
            ])->id;
        }

        $img = $this->images();
        $products = [
            ['car', 'Car Insurance', 'vehicle', 'car', 'Own the road ahead.', "Coverage for the car you drive every day — from liability that meets your state's requirements to full protection for a new vehicle.", 'auto'],
            ['motorcycle', 'Motorcycle Insurance', 'vehicle', 'moto', 'Ride with confidence.', 'Protect your bike, your gear and your passengers with coverage built for riders.', 'auto'],
            ['boat', 'Boat & Watercraft Insurance', 'vehicle', 'boat', 'Calm water, covered.', 'Coverage for boats, personal watercraft and the trailers that carry them.', 'auto'],
            ['classic-auto', 'Classic & Collector Auto', 'vehicle', 'classic', 'Protect the story it tells.', 'Agreed-value coverage for vintage, classic and collector vehicles kept with care.', 'auto'],
            ['atv', 'ATV & Off-Road Insurance', 'vehicle', 'car2', 'Built for the backroads.', 'Coverage for ATVs, UTVs, dirt bikes and side-by-sides on and off the trail.', 'auto'],
            ['rv', 'RV & Motor Home Insurance', 'vehicle', 'rv', 'Home, wherever you park it.', 'Protection for motor homes, travel trailers and the belongings that travel with you.', 'auto'],
            ['other-rec', 'Other Recreational Vehicle', 'vehicle', 'car2', 'Coverage for the fun stuff.', 'Golf carts, snowmobiles and other recreational vehicles — ask us what we can cover.', 'auto'],
            ['auto-home', 'Auto & Home Bundle', 'property', 'home', 'Bundle and save.', 'Combine your auto and homeowners coverage for simpler billing and multi-policy savings.', 'auto-home'],
            ['auto-renters', 'Auto & Renters Bundle', 'property', 'interior', 'Two policies, one plan.', 'Pair auto with renters coverage — an affordable way to protect your car and your belongings.', 'auto-renters'],
            ['homeowners', 'Homeowners Insurance', 'property', 'home2', 'Protect your place.', 'Coverage designed around your home and what matters inside it.', 'home'],
            ['condo', 'Condo Insurance', 'property', 'condo', 'Your unit, fully covered.', "Covers the interior of your unit, improvements you've made and your personal property.", 'condo'],
            ['renters', 'Renters Insurance', 'property', 'interior', 'Your things, protected.', 'Affordable protection for your belongings and personal liability wherever you rent.', 'renters'],
            ['flood', 'Flood Insurance', 'property', 'rain', 'Ready when the water rises.', "Standard home policies don't cover flood. Add protection for your structure and contents.", 'home'],
            ['landlord', 'Landlord Insurance', 'property', 'street', 'Protect your rental income.', 'Coverage for the properties you rent out, including lost rent and landlord liability.', 'home'],
            ['mobile-home', 'Mobile & Manufactured Home', 'property', 'mobile', 'Coverage that fits your home.', 'Specialized coverage for mobile and manufactured homes, on owned or rented land.', 'home'],
            ['vacation-home', 'Vacation Home Insurance', 'property', 'cabin', 'Your getaway, guarded.', 'Coverage for second homes and seasonal properties — including time they sit empty.', 'home'],
            ['vacant-home', 'Vacant Home Insurance', 'property', 'street', "Empty doesn't mean unprotected.", 'Coverage for homes that are between owners, tenants or renovations.', 'home'],
            ['earthquake', 'Earthquake Insurance', 'property', 'city', 'Steady ground for the unexpected.', 'Adds protection for damage most home policies exclude.', 'home'],
            ['life', 'Life Insurance', 'life', 'family', 'Protect the people you love.', 'Help your family stay financially secure, whatever happens.', 'life'],
            ['term-life', 'Term Life Insurance', 'life', 'family', 'Simple coverage for set years.', 'Affordable coverage for 10, 20 or 30 years — while your family needs it most.', 'life'],
            ['whole-life', 'Whole Life Insurance', 'life', 'family', 'Coverage for your whole life.', 'Permanent coverage with level premiums and guaranteed cash value growth.', 'life'],
            ['universal-life', 'Universal Life Insurance', 'life', 'family', 'Flexible, permanent protection.', 'Adjust premiums and death benefit as your life changes.', 'life'],
            ['business', 'Business Insurance', 'commercial', 'tower', "Protect what you've built.", 'Coverage options for businesses and commercial needs, matched to how you operate.', 'business'],
            ['commercial-auto', 'Commercial Auto', 'commercial', 'truck', 'Keep your business moving.', 'Coverage for cars, vans and trucks used for work — one vehicle or a fleet.', 'business'],
            ['commercial-property', 'Commercial Property', 'commercial', 'office', 'Your space, secured.', 'Protects your building, equipment and inventory against fire, theft and weather.', 'business'],
            ['workers-comp', "Workers' Compensation", 'commercial', 'crew', 'Take care of your team.', 'Covers medical costs and lost wages when employees are hurt on the job.', 'business'],
            ['general-liability', 'General Liability', 'commercial', 'office', 'Everyday protection for business.', 'Covers claims of injury or property damage from customers and third parties.', 'business'],
            ['by-industry', 'Insurance by Industry', 'commercial', 'cafe', 'Built for your trade.', 'Restaurants, contractors, retail, professional services and more — coverage matched to your industry.', 'business'],
            ['financial-solutions', 'Financial Solutions', 'financial', 'finance', 'Plan for what comes next.', 'Retirement, savings and protection strategies coordinated with your insurance.', 'life'],
            ['asset-management', 'Asset Management', 'financial', 'finance', 'Grow and protect your wealth.', 'Professional guidance for managing and protecting your assets.', 'life'],
            ['pet', 'Pet Insurance', 'partner', 'dog', 'Care for every member of the family.', 'Partner coverage for accidents and illnesses for your dogs and cats.', 'home'],
            ['device-protection', 'Electronic Device Protection', 'partner', 'phone', 'Cracked screens happen.', 'Partner protection for phones, tablets and laptops against damage and theft.', 'home'],
            ['medicare', 'Medicare Products', 'partner', 'health', 'Clarity on Medicare.', 'Medicare Supplement, Advantage and Part D options through our partners.', 'life'],
            ['health', 'Health Insurance', 'partner', 'health', 'Protect your wellbeing.', 'Insurance options for your healthcare needs, for individuals and families.', 'life'],
            ['umbrella', 'Personal Umbrella', 'more', 'home', 'An extra layer of protection.', 'Extends your liability limits above your home, auto and boat policies.', 'home'],
        ];

        foreach ($products as $i => [$slug, $name, $cat, $image, $tagline, $intro, $quoteType]) {
            InsuranceProduct::updateOrCreate(['slug' => $slug], [
                'insurance_category_id' => $categoryIds[$cat],
                'name' => $name,
                'image_url' => $img[$image],
                'tagline' => $tagline,
                'intro' => $intro,
                'quote_type' => $quoteType,
                'sort_order' => $i,
                'is_active' => true,
            ]);
        }
    }

    private function seedResources(): void
    {
        $categories = [
            'general' => 'General Insurance', 'auto' => 'Auto', 'home' => 'Home & Property', 'business' => 'Business',
            'life' => 'Life', 'claims' => 'Claims', 'membership' => 'Membership & Tokens', 'billing' => 'Payment & Billing',
            'plan' => 'Plan & Prep', 'stories' => 'Real Stories', 'faq' => 'FAQ',
        ];
        $categoryIds = [];
        $order = 0;
        foreach ($categories as $slug => $name) {
            $categoryIds[$slug] = ResourceCategory::updateOrCreate(['slug' => $slug], [
                'name' => $name,
                'show_in_filter' => $slug !== 'faq',
                'sort_order' => $order++,
            ])->id;
        }

        $img = $this->images();
        $resources = [
            ['how-much-auto', 'auto', 'How much auto insurance do I actually need?', "State minimums are just the start. Here's how to choose limits that protect your savings.", 'car', 6],
            ['deductible', 'general', 'Deductibles, explained in plain language', 'How your deductible affects your premium — and how to pick one you can afford.', 'finance', 4],
            ['home-inventory', 'home', 'Build a home inventory in one afternoon', 'A simple room-by-room approach that makes any claim faster.', 'interior', 5],
            ['term-vs-whole', 'life', 'Term vs. whole life: which fits you?', 'Compare cost, duration and cash value to find the right policy.', 'family', 7],
            ['bop', 'business', "What a business owner's policy covers", 'Property, liability and business income — bundled for small businesses.', 'office', 6],
            ['after-accident', 'claims', 'What to do in the first hour after an accident', 'Stay safe, document the scene and start your claim the right way.', 'car2', 4],
            ['tokens-101', 'membership', 'COSPARK tokens 101: DT, IT and VT', 'How Dues, Insurance and Vacation Tokens are earned, tracked and redeemed.', 'beach', 5],
            ['autopay', 'billing', 'Set up autopay for dues and premiums', 'Keep membership and insurance payments separate — and on time.', 'phone', 3],
            ['storm-prep', 'plan', 'Storm season checklist for Michigan homes', 'Gutters, sump pumps, trees and the photos to take before it hits.', 'rain', 5],
            ['flood-myth', 'home', 'Does homeowners insurance cover floods?', 'Usually not. Learn where flood coverage fits in your plan.', 'rain', 4],
            ['story-hail', 'stories', 'Real story: a hailstorm, two cars and one easy claim', 'How a Rochester family got back on the road in nine days.', 'car', 4],
            ['workers-comp-faq', 'business', "Do I need workers' comp with one employee?", "State rules vary. Here's how to check yours.", 'crew', 4],
        ];

        foreach ($resources as $i => [$slug, $cat, $title, $summary, $image, $read]) {
            Resource::updateOrCreate(['slug' => $slug], [
                'resource_category_id' => $categoryIds[$cat],
                'title' => $title,
                'summary' => $summary,
                'image_url' => $img[$image],
                'read_minutes' => $read,
                'body' => null, // null renders the standard article template body
                'is_published' => true,
                'sort_order' => $i,
            ]);
        }
    }

    private function seedPartners(): void
    {
        $img = $this->images();
        $partners = [
            ['Keys Cove Luxury Vacations', 'Vacations', 'Waterfront luxury stays in the Florida Keys.', 'villa', true, 'Florida Keys, FL', ['Ocean view', 'Private dock', 'Pool', 'Sleeps 8']],
            ['Fish Camp', 'Vacations', 'Relaxed waterside cabins built for fishing trips.', 'ocean', true, 'Florida Gulf Coast', ['Boat slip', 'Kayaks', 'Grill', 'Sleeps 6']],
            ['Sarasota Properties', 'Vacations', "Luxury homes minutes from Sarasota's beaches.", 'beach', true, 'Sarasota, FL', ['Beach access', 'Pool', 'Gym', 'Sleeps 10']],
            ['Grassy Key Oceanfront', 'Vacations', 'Oceanfront properties on Grassy Key.', 'villa', true, 'Grassy Key, FL', ['Oceanfront', 'Snorkel gear', 'Hot tub', 'Sleeps 6']],
            ['Stay for Free Vacations', 'Travel', 'Token-eligible stays across partner destinations.', 'cabin', true, 'Multiple destinations', ['Token eligible', 'Flexible dates']],
            ['COSPARK Eateries', 'Dining', 'Monthly dining credits at participating restaurants.', 'cafe', false, null, null],
            ['Big Circle Auctions', 'Auctions', 'Member access to partner auctions.', 'office', false, null, null],
            ['COSPARK Centers', 'Centers', 'Community centers and member events.', 'tower', false, null, null],
            ['COSPARK Business Partners', 'Member benefits', 'Local businesses offering member pricing via QR.', 'office', false, null, null],
        ];

        foreach ($partners as $i => [$name, $category, $description, $image, $isVacation, $location, $amenities]) {
            Partner::updateOrCreate(['slug' => Str::slug($name)], [
                'name' => $name,
                'category' => $category,
                'description' => $description,
                'image_url' => $img[$image],
                'is_vacation' => $isVacation,
                'location' => $location,
                'amenities' => $amenities,
                'sort_order' => $i,
                'is_active' => true,
            ]);
        }
    }

    private function seedClaimTypes(): void
    {
        $types = [
            'auto' => ['Auto', ['Check for injuries and call 911 if needed', 'Exchange information with other drivers', 'Photograph vehicles, plates and the scene'], ['Report the claim online or by phone', 'An adjuster contacts you within one business day', 'Get an estimate at a repair shop', 'Repairs are approved and paid'], ['Police report number', "Other driver's information", 'Repair estimate', 'Rental receipts if applicable'], 'Photos of all vehicles from four angles, close-ups of damage, the scene and road conditions.'],
            'glass' => ['Glass', ['Avoid car washes until repaired', 'Cover a broken window to keep weather out'], ['Report the glass claim', 'Choose a repair partner or mobile service', 'Repair is completed — often same day'], ['Policy number', 'Photo of the damage'], 'A clear photo of the chip or crack with a coin for scale.'],
            'home' => ['Home', ['Stop the source of damage if safe', 'Make temporary repairs and keep receipts', "Don't throw away damaged items yet"], ['Report the claim', 'An adjuster inspects the damage', 'Receive your estimate', 'Repairs and payment'], ['List of damaged items', 'Repair estimates', 'Receipts for temporary repairs', 'Police report for theft'], 'Wide shots of each room, close-ups of damage, serial numbers of electronics and the source of damage.'],
            'renters' => ['Renters', ['Notify your landlord', 'Secure your belongings', 'Report theft to police'], ['Report the claim', 'List damaged or stolen items', 'Adjuster review', 'Payment for covered items'], ['Inventory of items', 'Receipts or proof of ownership', 'Police report for theft'], 'Photos of damaged items and the affected area.'],
            'condo' => ['Condo', ['Contact your HOA or property manager', 'Prevent further damage inside your unit'], ['Report the claim', 'Coordinate with the HOA master policy', 'Adjuster review', 'Repairs and payment'], ['HOA contact and master policy info', 'Repair estimates', 'Inventory of items'], 'Photos of interior damage, improvements and personal property.'],
            'business' => ['Commercial', ['Ensure employee and customer safety', 'Secure the premises', 'Document lost business time'], ['Report the claim', 'Adjuster contacts your business', 'Submit loss documentation', 'Settlement'], ['Financial records for lost income', 'Inventory and equipment lists', 'Incident or injury reports'], 'Photos of property, equipment and inventory damage.'],
            'life' => ['Life', ['Gather the policy number if available', 'Obtain certified copies of the death certificate'], ['Notify us of the loss', 'Beneficiaries complete claim forms', 'Documents are reviewed', 'Benefit is paid'], ['Certified death certificate', 'Beneficiary claim form', 'Beneficiary ID'], 'Not usually required.'],
            'other' => ['Other', ['Keep yourself safe', 'Document the incident'], ['Report the claim', 'An agent reviews coverage', 'Next steps are confirmed'], ['Policy number', 'Description and photos'], 'Any photos that show the incident and the damage.'],
        ];

        $order = 0;
        foreach ($types as $slug => [$name, $immediate, $steps, $documents, $photos]) {
            ClaimType::updateOrCreate(['slug' => $slug], [
                'name' => $name,
                'immediate_actions' => $immediate,
                'steps' => $steps,
                'documents' => $documents,
                'photo_guidance' => $photos,
                'sort_order' => $order++,
            ]);
        }
    }
}
