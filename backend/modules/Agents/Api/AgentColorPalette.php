<?php

declare(strict_types=1);

namespace Modules\Agents\Api;

use function abs;
use function count;
use function crc32;

final class AgentColorPalette
{
    /**
     * @var list<string>
     */
    private const array PALETTE = [
        '#6366f1', // indigo-500
        '#d946ef', // fuchsia-500
        '#10b981', // emerald-500
        '#f59e0b', // amber-500
        '#f43f5e', // rose-500
        '#06b6d4', // cyan-500
        '#8b5cf6', // violet-500
        '#0ea5e9', // sky-500
        '#f97316', // orange-500
        '#84cc16', // lime-500
        '#14b8a6', // teal-500
        '#ec4899', // pink-500
    ];

    private function __construct()
    {
    }

    public static function deriveFromSlug(string $slug): string
    {
        $index = abs(crc32($slug)) % count(self::PALETTE);

        return self::PALETTE[$index];
    }

    /**
     * @return list<string>
     */
    public static function palette(): array
    {
        return self::PALETTE;
    }
}
