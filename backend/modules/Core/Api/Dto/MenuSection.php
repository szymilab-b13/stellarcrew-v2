<?php

declare(strict_types=1);

namespace Modules\Core\Api\Dto;

final readonly class MenuSection
{
    /**
     * @param list<MenuItem> $items
     */
    public function __construct(
        public string $id,
        public string $labelKey,
        public int $order,
        public array $items,
    ) {
    }
}
