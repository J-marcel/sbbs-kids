<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageHelpers
{
    public static function pathToUrl(?string $path = null): ?string
    {
        if (!$path || Str::startsWith($path, ['https://', 'http://'])) {
            return $path;
        }

        $storageUrl = Storage::url($path);

        if (Str::startsWith($storageUrl, ['https://', 'http://'])) {
            return $storageUrl;
        }

        return url($storageUrl);
    }

}
