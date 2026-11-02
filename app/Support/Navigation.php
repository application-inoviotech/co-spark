<?php

namespace App\Support;

/**
 * Resolves the menus (config/cospark.php and the CMS "layout" page) into
 * [label, url] pairs for the header, mobile nav and footer.
 */
class Navigation
{
    /** @return array<int, array{0: string, 1: array<int, array{0: string, 1: string}>}> */
    public function insurance(): array
    {
        return array_map(fn ($group) => [
            $group[0],
            array_map(fn ($item) => [$item[0], $this->insuranceUrl($item[1])], $group[1]),
        ], config('cospark.nav_insurance'));
    }

    /** @return array<int, array{0: string, 1: string}> */
    public function quotes(): array
    {
        return collect(config('cospark.quote_types'))
            ->map(fn ($label, $type) => [$label, route('quote', ['type' => $type])])
            ->values()
            ->all();
    }

    /**
     * An admin-editable "Label | /path" menu from the "Header & footer" CMS page.
     *
     * @return array<int, array{0: string, 1: string}>
     */
    public function links(string $key): array
    {
        return array_map(fn ($l) => [$l[0], url($l[1])], cms_rows("layout.{$key}", 2));
    }

    public function url(string $route, array $params = [], ?string $fragment = null): string
    {
        return route($route, $params).($fragment ? "#{$fragment}" : '');
    }

    /** A product slug, or [route, params, fragment] for hub links. */
    private function insuranceUrl(string|array $target): string
    {
        return is_array($target)
            ? $this->url(...$target)
            : route('insurance.show', $target);
    }
}
