<?php

declare(strict_types=1);

namespace Modules\Workers\Application;

final readonly class IssuedTokenResult
{
    /**
     * @param array<string, mixed>|null $previousFingerprintMeta
     */
    public function __construct(
        public string $accessToken,
        public string $refreshToken,
        public int $expiresIn,
        public bool $fingerprintWarning = false,
        public ?array $previousFingerprintMeta = null,
    ) {
    }
}
