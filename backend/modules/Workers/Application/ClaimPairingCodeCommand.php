<?php

declare(strict_types=1);

namespace Modules\Workers\Application;

final readonly class ClaimPairingCodeCommand
{
    /**
     * @param array<string, mixed>|null $fingerprintMeta
     */
    public function __construct(
        public string $code,
        public string $name,
        public ?string $hostLabel = null,
        public ?string $fingerprintHash = null,
        public ?array $fingerprintMeta = null,
    ) {
    }
}
