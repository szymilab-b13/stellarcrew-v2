<?php

declare(strict_types=1);

namespace Modules\Workers\Infrastructure\Http;

use Modules\Workers\Domain\Worker;
use Modules\Workers\Domain\WorkerPairingCode;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class ClaimPairingCodeRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'code is required')]
        #[Assert\Regex(pattern: WorkerPairingCode::CODE_PATTERN, message: 'code must match XXXX-XXXX unambiguous pattern')]
        public string $code,
        #[Assert\NotBlank(message: 'name is required')]
        #[Assert\Length(max: Worker::NAME_MAX, maxMessage: 'name cannot exceed {{ limit }} characters')]
        public string $name,
        #[Assert\Length(max: Worker::HOST_LABEL_MAX, maxMessage: 'hostLabel cannot exceed {{ limit }} characters')]
        public ?string $hostLabel = null,
    ) {
    }
}
