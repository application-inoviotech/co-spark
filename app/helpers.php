<?php

use App\Support\Content;
use Illuminate\Support\HtmlString;

if (! function_exists('cms')) {
    /** CMS copy for "page.key", escaped, with line breaks kept as <br>. */
    function cms(string $key): HtmlString
    {
        return new HtmlString(nl2br(e((string) app(Content::class)->get($key)), false));
    }
}

if (! function_exists('cms_rows')) {
    /**
     * CMS list for "page.key": one row per line, cells split on "|" and padded to $cells.
     *
     * @return array<int, string[]>
     */
    function cms_rows(string $key, int $cells = 1): array
    {
        return array_map(
            fn ($line) => array_pad(array_map('trim', explode('|', $line, $cells)), $cells, ''),
            (array) app(Content::class)->get($key),
        );
    }
}
