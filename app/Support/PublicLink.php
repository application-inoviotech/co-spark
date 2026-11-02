<?php

namespace App\Support;

class PublicLink
{
    /** Only render configured, absolute web destinations as external links. */
    public static function external(?string $url): ?string
    {
        $url = trim($url ?? '');

        return filter_var($url, FILTER_VALIDATE_URL) && in_array(strtolower(parse_url($url, PHP_URL_SCHEME) ?? ''), ['http', 'https'], true)
            ? $url : null;
    }
}
