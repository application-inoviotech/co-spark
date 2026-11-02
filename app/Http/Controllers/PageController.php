<?php

namespace App\Http\Controllers;

use App\Models\InsuranceProduct;
use App\Models\Partner;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Public marketing pages whose content is static or comes from config. */
class PageController extends Controller
{
    public function home(): View
    {
        return view('pages.home');
    }

    public function membership(): View
    {
        return view('pages.membership');
    }

    public function payments(): View
    {
        return view('pages.payments');
    }

    public function producer(): View
    {
        return view('pages.producer');
    }

    public function ecosystem(): View
    {
        $partners = Partner::active()->get();

        return view('pages.ecosystem', [
            'vacations' => $partners->where('is_vacation', true),
            'partners' => $partners->where('is_vacation', false),
        ]);
    }

    public function contact(Request $request): View
    {
        return view('pages.contact', [
            'office' => config('cospark.office'),
            'quoteProduct' => InsuranceProduct::active()->where('slug', $request->query('product', ''))->first(),
        ]);
    }

    public function legal(): View
    {
        return view('pages.legal');
    }
}
