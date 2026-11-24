<?php

declare(strict_types=1);

namespace Modules\Chat\Infrastructure\Http;

use Modules\Chat\Domain\ChatMessage;
use Modules\Chat\Domain\ClassifiedBy;
use Modules\Chat\Domain\ContentType;
use Modules\Chat\Domain\EngineType;
use Modules\Chat\Domain\ParticipantType;
use Modules\Chat\Domain\Priority;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class SendMessageRequest
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        #[Assert\NotBlank(message: 'sessionId is required')]
        #[Assert\Uuid]
        public string $sessionId,
        #[Assert\NotBlank(message: 'threadId is required')]
        #[Assert\Uuid]
        public string $threadId,
        #[Assert\NotBlank(message: 'senderType is required')]
        #[Assert\Choice(choices: ['user', 'agent', 'system', 'supervisor'])]
        public string $senderType,
        #[Assert\NotBlank(message: 'senderId is required')]
        #[Assert\Uuid]
        public string $senderId,
        #[Assert\NotBlank(message: 'recipientType is required')]
        #[Assert\Choice(choices: ['user', 'agent', 'system', 'supervisor'])]
        public string $recipientType,
        #[Assert\NotBlank(message: 'recipientId is required')]
        #[Assert\Uuid]
        public string $recipientId,
        #[Assert\NotBlank(message: 'subject is required')]
        #[Assert\Length(max: ChatMessage::SUBJECT_MAX, maxMessage: 'subject cannot exceed {{ limit }} characters')]
        public string $subject,
        #[Assert\Choice(choices: ['P0', 'P1', 'P2', 'P3'])]
        public string $priority,
        #[Assert\NotBlank(message: 'content is required')]
        public string $content,
        #[Assert\Choice(choices: ['text', 'markdown', 'task_dispatch', 'tool_request', 'forwarded', 'notification'])]
        public string $contentType,
        public array $metadata,
        #[Assert\Uuid]
        public ?string $replyToMessageId,
        #[Assert\NotBlank(message: 'engineId is required')]
        #[Assert\Uuid]
        public string $engineId,
        #[Assert\Choice(choices: ['cli_terminal', 'webhook', 'batch', 'stellarcrew_worker'])]
        public string $engineType,
        #[Assert\Choice(choices: ['sender', 'supervisor', 'manual'])]
        public string $classifiedBy,
        #[Assert\Range(min: 0.0, max: 1.0)]
        public ?float $classificationConfidence,
    ) {
    }

    public function senderTypeEnum(): ParticipantType
    {
        return ParticipantType::from($this->senderType);
    }

    public function recipientTypeEnum(): ParticipantType
    {
        return ParticipantType::from($this->recipientType);
    }

    public function priorityEnum(): Priority
    {
        return Priority::from($this->priority);
    }

    public function contentTypeEnum(): ContentType
    {
        return ContentType::from($this->contentType);
    }

    public function engineTypeEnum(): EngineType
    {
        return EngineType::from($this->engineType);
    }

    public function classifiedByEnum(): ClassifiedBy
    {
        return ClassifiedBy::from($this->classifiedBy);
    }
}
