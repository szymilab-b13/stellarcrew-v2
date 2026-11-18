<?php

declare(strict_types=1);

namespace Modules\Chat\Infrastructure\Http;

use Modules\Chat\Domain\ChatThread;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateThreadRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'sessionId is required')]
        #[Assert\Uuid]
        public string $sessionId,
        #[Assert\NotBlank(message: 'subject is required')]
        #[Assert\Length(max: ChatThread::SUBJECT_MAX, maxMessage: 'subject cannot exceed {{ limit }} characters')]
        public string $subject,
        #[Assert\Uuid]
        public ?string $parentThreadId = null,
    ) {
    }
}
