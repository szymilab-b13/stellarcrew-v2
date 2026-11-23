<?php

declare(strict_types=1);

namespace Modules\Workers\Infrastructure\Http;

use Modules\Workers\Domain\Worker;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class IssueTokenRequest
{
    /**
     * @param array<string, mixed>|null $fingerprintMeta
     * @param array<string, mixed>|null $hostInfo
     */
    public function __construct(
        #[Assert\NotBlank(message: 'refreshToken is required')]
        public string $refreshToken,
        #[Assert\Regex(pattern: Worker::FINGERPRINT_HASH_PATTERN, message: 'fingerprintHash must match sha256 hex pattern')]
        public ?string $fingerprintHash = null,
        public ?array $fingerprintMeta = null,
        public ?array $hostInfo = null,
    ) {
    }
}
