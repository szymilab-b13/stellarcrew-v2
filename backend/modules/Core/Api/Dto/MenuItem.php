<?php

declare(strict_types=1);

namespace Modules\Core\Api\Dto;

final readonly class MenuItem
{
    public function __construct(
        public string $id,
        public string $labelKey,
        public string $icon,
        public string $path,
        public int $order = 100,
        public ?string $requiresRole = null,
    ) {
    }
}
