<?php

declare(strict_types=1);

namespace Modules\Workers\Application;

final readonly class IssueTokenCommand
{
    /**
     * @param array<string, mixed>|null $fingerprintMeta
     */
    public function __construct(
        public string $refreshToken,
        public ?string $fingerprintHash = null,
        public ?array $fingerprintMeta = null,
    ) {
    }
}
