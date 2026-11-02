<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Permanently redirects the approved prototype URLs (e.g. /product.html?p=car,
 * /quote.html?type=auto, /article.html?id=x) to their Laravel routes, keeping the
 * remaining query string, so every previously shared link keeps working.
 */
class LegacyRedirectController extends Controller
{
    /** page => [route name, query key moved into the route path] */
    private const MAP = [
        'index' => ['home', null],
        'insurance' => ['insurance.index', null],
        'product' => ['insurance.show', 'p'],
        'quote' => ['quote', null],
        'membership' => ['membership', null],
        'claims' => ['claims.index', null],
        'file-claim' => ['claims.create', null],
        'resources' => ['resources.index', null],
        'article' => ['resources.show', 'id'],
        'payments' => ['payments', null],
        'producer' => ['producer', null],
        'ecosystem' => ['ecosystem', null],
        'contact' => ['contact', null],
        'legal' => ['legal', null],
        'login' => ['login', null],
        'register' => ['register', null],
        'forgot-password' => ['password.request', null],
        'dashboard' => ['dashboard', null],
        'producer-dashboard' => ['producer.dashboard', null],
    ];

    public function __invoke(Request $request, string $page): RedirectResponse
    {
        abort_unless(isset(self::MAP[$page]), 404);

        [$route, $pathKey] = self::MAP[$page];
        $query = $request->query();

        if ($pathKey) {
            $value = $query[$pathKey] ?? null;
            unset($query[$pathKey]);
            // product.html / article.html without a valid id fall back to the hub page
            $url = $value ? route($route, $value) : route($route === 'insurance.show' ? 'insurance.index' : 'resources.index');
        } else {
            $url = route($route);
        }

        return redirect()->to($query ? $url.'?'.http_build_query($query) : $url, 301);
    }
}
