<?php

declare(strict_types=1);

namespace Modules\Chat\Domain;

use DateTimeImmutable;
use InvalidArgumentException;

use function mb_strlen;

use Modules\Shared\Kernel\ChatMessageId;
use Modules\Shared\Kernel\ChatSessionId;
use Modules\Shared\Kernel\ChatThreadId;

use function sprintf;

use Symfony\Component\Uid\Uuid;

use function trim;

class ChatMessage
{
    public const int SUBJECT_MAX = 120;

    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        private readonly ChatMessageId $id,
        private readonly ChatSessionId $sessionId,
        private readonly ChatThreadId $threadId,
        private readonly ParticipantType $senderType,
        private readonly Uuid $senderId,
        private readonly ParticipantType $recipientType,
        private readonly Uuid $recipientId,
        private readonly string $subject,
        private readonly Priority $priority,
        private readonly string $content,
        private readonly ContentType $contentType,
        private readonly array $metadata,
        private readonly ?ChatMessageId $replyToMessageId,
        private readonly Uuid $engineId,
        private readonly EngineType $engineType,
        private readonly ClassifiedBy $classifiedBy,
        private readonly ?float $classificationConfidence,
        private readonly DateTimeImmutable $createdAt,
    ) {
        $this->guardSubject($subject);
        $this->guardContent($content);
        $this->guardConfidence($classificationConfidence);
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public static function send(
        ChatMessageId $id,
        ChatSessionId $sessionId,
        ChatThreadId $threadId,
        ParticipantType $senderType,
        Uuid $senderId,
        ParticipantType $recipientType,
        Uuid $recipientId,
        string $subject,
        Priority $priority,
        string $content,
        ContentType $contentType,
        array $metadata,
        ?ChatMessageId $replyToMessageId,
        Uuid $engineId,
        EngineType $engineType,
        ClassifiedBy $classifiedBy,
        ?float $classificationConfidence,
        DateTimeImmutable $now,
    ): self {
        return new self(
            $id,
            $sessionId,
            $threadId,
            $senderType,
            $senderId,
            $recipientType,
            $recipientId,
            $subject,
            $priority,
            $content,
            $contentType,
            $metadata,
            $replyToMessageId,
            $engineId,
            $engineType,
            $classifiedBy,
            $classificationConfidence,
            $now,
        );
    }

    public function id(): ChatMessageId
    {
        return $this->id;
    }

    public function sessionId(): ChatSessionId
    {
        return $this->sessionId;
    }

    public function threadId(): ChatThreadId
    {
        return $this->threadId;
    }

    public function senderType(): ParticipantType
    {
        return $this->senderType;
    }

    public function senderId(): Uuid
    {
        return $this->senderId;
    }

    public function recipientType(): ParticipantType
    {
        return $this->recipientType;
    }

    public function recipientId(): Uuid
    {
        return $this->recipientId;
    }

    public function subject(): string
    {
        return $this->subject;
    }

    public function priority(): Priority
    {
        return $this->priority;
    }

    public function content(): string
    {
        return $this->content;
    }

    public function contentType(): ContentType
    {
        return $this->contentType;
    }

    /**
     * @return array<string, mixed>
     */
    public function metadata(): array
    {
        return $this->metadata;
    }

    public function replyToMessageId(): ?ChatMessageId
    {
        return $this->replyToMessageId;
    }

    public function engineId(): Uuid
    {
        return $this->engineId;
    }

    public function engineType(): EngineType
    {
        return $this->engineType;
    }

    public function classifiedBy(): ClassifiedBy
    {
        return $this->classifiedBy;
    }

    public function classificationConfidence(): ?float
    {
        return $this->classificationConfidence;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    private function guardSubject(string $subject): void
    {
        $trimmed = trim($subject);
        if ($trimmed === '') {
            throw new InvalidArgumentException('Chat message subject must not be blank.');
        }

        if (mb_strlen($subject) > self::SUBJECT_MAX) {
            throw new InvalidArgumentException(sprintf(
                'Chat message subject must be at most %d characters.',
                self::SUBJECT_MAX,
            ));
        }
    }

    private function guardContent(string $content): void
    {
        if (trim($content) === '') {
            throw new InvalidArgumentException('Chat message content must not be blank.');
        }
    }

    private function guardConfidence(?float $confidence): void
    {
        if ($confidence === null) {
            return;
        }

        if ($confidence < 0.0 || $confidence > 1.0) {
            throw new InvalidArgumentException(sprintf(
                'Chat message classification confidence must be in range [0.0, 1.0], got %f.',
                $confidence,
            ));
        }
    }
}
