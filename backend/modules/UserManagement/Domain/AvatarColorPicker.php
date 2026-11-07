<?php

declare(strict_types=1);

namespace Modules\UserManagement\Domain;

use function count;
use function crc32;
use function mb_strtolower;
use function trim;

final class AvatarColorPicker
{
    /**
     * @var list<string>
     */
    public const array PALETTE = [
        '#EF4444',
        '#F59E0B',
        '#10B981',
        '#3B82F6',
        '#8B5CF6',
        '#EC4899',
        '#14B8A6',
        '#F97316',
    ];

    public static function pick(string $email): string
    {
        $normalized = mb_strtolower(trim($email));
        $hash = crc32($normalized);
        $index = $hash % count(self::PALETTE);

        return self::PALETTE[$index];
    }
}
