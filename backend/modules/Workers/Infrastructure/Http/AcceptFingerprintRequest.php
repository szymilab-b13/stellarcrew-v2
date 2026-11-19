<?php

declare(strict_types=1);

namespace Modules\Workers\Infrastructure\Http;

use Modules\Workers\Domain\Worker;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class AcceptFingerprintRequest
{
    /**
     * @param array<string, mixed>|null $fingerprintMeta
     */
    public function __construct(
        #[Assert\NotBlank(message: 'fingerprintHash is required')]
        #[Assert\Regex(pattern: Worker::FINGERPRINT_HASH_PATTERN, message: 'fingerprintHash must match sha256 hex pattern')]
        public string $fingerprintHash,
        public ?array $fingerprintMeta = null,
    ) {
    }
}
