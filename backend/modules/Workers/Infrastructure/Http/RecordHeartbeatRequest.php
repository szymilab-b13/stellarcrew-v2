<?php

declare(strict_types=1);

namespace Modules\Workers\Infrastructure\Http;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class RecordHeartbeatRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'secret is required')]
        public string $secret,
    ) {
    }
}
