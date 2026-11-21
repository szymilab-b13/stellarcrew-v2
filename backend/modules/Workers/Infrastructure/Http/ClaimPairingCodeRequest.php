<?php

declare(strict_types=1);

namespace Modules\Workers\Infrastructure\Http;

use Modules\Workers\Domain\Worker;
use Modules\Workers\Domain\WorkerPairingCode;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class ClaimPairingCodeRequest
{
    /**
     * @param array<string, mixed>|null $fingerprintMeta
     * @param array<string, mixed>|null $hostInfo
     */
    public function __construct(
        #[Assert\NotBlank(message: 'code is required')]
        #[Assert\Regex(pattern: WorkerPairingCode::CODE_PATTERN, message: 'code must match XXXX-XXXX unambiguous pattern')]
        public string $code,
        #[Assert\Length(max: Worker::NAME_MAX, maxMessage: 'name cannot exceed {{ limit }} characters')]
        public ?string $name = null,
        #[Assert\Length(max: Worker::HOST_LABEL_MAX, maxMessage: 'hostLabel cannot exceed {{ limit }} characters')]
        public ?string $hostLabel = null,
        #[Assert\Regex(pattern: Worker::FINGERPRINT_HASH_PATTERN, message: 'fingerprintHash must match sha256 hex pattern')]
        public ?string $fingerprintHash = null,
        public ?array $fingerprintMeta = null,
        public ?array $hostInfo = null,
    ) {
    }
}
