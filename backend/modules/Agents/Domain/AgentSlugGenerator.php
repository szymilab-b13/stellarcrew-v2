<?php

declare(strict_types=1);

namespace Modules\Agents\Domain;

use function mb_strtolower;
use function mb_substr;
use function preg_replace;
use function random_int;
use function sprintf;
use function trim;

final class AgentSlugGenerator
{
    private const int MAX_LENGTH = Agent::SLUG_MAX;

    private const int SUFFIX_RANGE_MIN = 1000;

    private const int SUFFIX_RANGE_MAX = 9999;

    public static function fromName(string $name): string
    {
        $slug = mb_strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9]+/u', '-', $slug) ?? '';
        $slug = trim($slug, '-');

        if ($slug === '') {
            $slug = sprintf('agent-%d', random_int(self::SUFFIX_RANGE_MIN, self::SUFFIX_RANGE_MAX));
        }

        return mb_substr($slug, 0, self::MAX_LENGTH);
    }

    public static function withRandomSuffix(string $base): string
    {
        $suffix = sprintf('-%d', random_int(self::SUFFIX_RANGE_MIN, self::SUFFIX_RANGE_MAX));
        $available = self::MAX_LENGTH - mb_strlen($suffix);

        return mb_substr($base, 0, $available) . $suffix;
    }
}
