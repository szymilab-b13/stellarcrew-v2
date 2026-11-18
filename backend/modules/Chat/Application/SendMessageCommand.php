<?php

declare(strict_types=1);

namespace Modules\Chat\Application;

use Modules\Chat\Domain\ClassifiedBy;
use Modules\Chat\Domain\ContentType;
use Modules\Chat\Domain\EngineType;
use Modules\Chat\Domain\ParticipantType;
use Modules\Chat\Domain\Priority;
use Modules\Shared\Kernel\ChatMessageId;
use Modules\Shared\Kernel\ChatSessionId;
use Modules\Shared\Kernel\ChatThreadId;
use Modules\Shared\Kernel\UserId;
use Symfony\Component\Uid\Uuid;

final readonly class SendMessageCommand
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public UserId $actorId,
        public ChatSessionId $sessionId,
        public ChatThreadId $threadId,
        public ParticipantType $senderType,
        public Uuid $senderId,
        public ParticipantType $recipientType,
        public Uuid $recipientId,
        public string $subject,
        public Priority $priority,
        public string $content,
        public ContentType $contentType,
        public array $metadata,
        public ?ChatMessageId $replyToMessageId,
        public Uuid $engineId,
        public EngineType $engineType,
        public ClassifiedBy $classifiedBy,
        public ?float $classificationConfidence,
    ) {
    }
}
