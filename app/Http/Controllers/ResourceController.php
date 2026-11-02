<?php

namespace App\Http\Controllers;

use App\Models\Resource;
use App\Models\ResourceCategory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResourceController extends Controller
{
    public function index(Request $request): View
    {
        $categories = ResourceCategory::where('show_in_filter', true)->orderBy('sort_order')->get();
        $requested = $request->query('cat');

        return view('resources.index', [
            'categories' => $categories,
            'resources' => Resource::published()->with('category')->get(),
            // "faq" (and unknown categories) show everything, as in the prototype
            'activeCategory' => $categories->contains('slug', $requested) ? $requested : 'all',
        ]);
    }

    public function show(Resource $resource): View
    {
        abort_unless($resource->is_published, 404);

        $related = Resource::published()
            ->with('category')
            ->whereKeyNot($resource->id)
            ->get()
            ->sortByDesc(fn (Resource $r) => $r->resource_category_id === $resource->resource_category_id)
            ->take(3);

        return view('resources.show', [
            'resource' => $resource->load('category'),
            'related' => $related,
        ]);
    }
}
