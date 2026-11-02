<?php

namespace App\Support;

class AssetVersion
{
    /** Changed public scripts must not keep using an old browser cache after deployment. */
    public static function for(string $path): string
    {
        $file = public_path($path);

        return config('app.asset_version').'.'.(is_file($file) ? substr(hash_file('sha256', $file), 0, 12) : 'missing');
    }
}
