<?php

declare(strict_types=1);

namespace Modules\Workspaces\Domain;

use function iconv;
use function preg_replace;
use function random_int;
use function strtolower;
use function trim;

final class SlugGenerator
{
    public static function fromName(string $name): string
    {
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
        if ($ascii === false) {
            $ascii = $name;
        }

        $slug = strtolower($ascii);
        $slug = (string) preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');

        if ($slug === '') {
            $slug = 'workspace';
        }

        return $slug;
    }

    public static function withRandomSuffix(string $base): string
    {
        return $base . '-' . random_int(1000, 9999);
    }
}
