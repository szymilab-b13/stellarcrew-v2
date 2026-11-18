<?php

declare(strict_types=1);

namespace Modules\Workers\Infrastructure\Http;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class WorkerRecordMessageRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'threadId is required')]
        #[Assert\Uuid]
        public string $threadId,
        #[Assert\NotBlank(message: 'agentId is required')]
        #[Assert\Uuid]
        public string $agentId,
        #[Assert\NotBlank(message: 'content is required')]
        public string $content,
        #[Assert\Uuid]
        public ?string $replyToMessageId = null,
    ) {
    }
}
