<?php

declare(strict_types=1);

namespace Modules\Chat\Domain;

use DateTimeImmutable;
use Modules\Shared\Kernel\ChatMessageId;
use Modules\Shared\Kernel\ChatThreadId;

class ChatThreadSummary
{
    /**
     * @param array<string, mixed>|null $summaryJson
     */
    public function __construct(
        private readonly ChatThreadId $threadId,
        private ?string $shortDescription,
        private ?array $summaryJson,
        private ?ChatMessageId $messagesCoveredUntilId,
        private ?DateTimeImmutable $lastGeneratedAt,
        private ?string $generatorVersion,
    ) {
    }

    public static function createEmpty(ChatThreadId $threadId): self
    {
        return new self(
            $threadId,
            null,
            null,
            null,
            null,
            null,
        );
    }

    public function threadId(): ChatThreadId
    {
        return $this->threadId;
    }

    public function shortDescription(): ?string
    {
        return $this->shortDescription;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function summaryJson(): ?array
    {
        return $this->summaryJson;
    }

    public function messagesCoveredUntilId(): ?ChatMessageId
    {
        return $this->messagesCoveredUntilId;
    }

    public function lastGeneratedAt(): ?DateTimeImmutable
    {
        return $this->lastGeneratedAt;
    }

    public function generatorVersion(): ?string
    {
        return $this->generatorVersion;
    }

    /**
     * @param array<string, mixed>|null $summaryJson
     */
    public function updateFromGenerator(
        ?string $shortDescription,
        ?array $summaryJson,
        ?ChatMessageId $messagesCoveredUntilId,
        ?string $generatorVersion,
        DateTimeImmutable $generatedAt,
    ): void {
        $this->shortDescription = $shortDescription;
        $this->summaryJson = $summaryJson;
        $this->messagesCoveredUntilId = $messagesCoveredUntilId;
        $this->generatorVersion = $generatorVersion;
        $this->lastGeneratedAt = $generatedAt;
    }
}
