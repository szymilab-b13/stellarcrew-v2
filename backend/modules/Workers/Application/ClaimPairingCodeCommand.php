<?php

declare(strict_types=1);

namespace Modules\Workers\Application;

final readonly class ClaimPairingCodeCommand
{
    public function __construct(
        public string $code,
        public string $name,
        public ?string $hostLabel = null,
    ) {
    }
}
