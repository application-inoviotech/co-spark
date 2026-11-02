<?php

namespace App\Http\Middleware;

use App\Admin\Registry;
use Illuminate\Http\Request;
use Inertia\Middleware;

/** Inertia is used only by the admin panel (resources/js/admin). */
class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'admin.app';

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => fn () => ['name' => $request->user('admin')?->name, 'email' => $request->user('admin')?->email],
            'flash' => fn () => ['success' => $request->session()->get('success'), 'error' => $request->session()->get('error')],
            'nav' => fn () => app(Registry::class)->navigation(),
            'site' => fn () => ['home' => route('home'), 'logout' => route('admin.logout')],
        ];
    }
}
