<?php

declare(strict_types=1);

namespace Modules\Chat\Infrastructure\Http;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class AttachThreadRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'parentThreadId is required')]
        #[Assert\Uuid]
        public string $parentThreadId,
    ) {
    }
}
