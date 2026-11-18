<?php

declare(strict_types=1);

namespace App\Tests\Modules\Chat\Domain;

use DateTimeImmutable;
use InvalidArgumentException;
use Modules\Chat\Domain\ChatMessage;
use Modules\Chat\Domain\ClassifiedBy;
use Modules\Chat\Domain\ContentType;
use Modules\Chat\Domain\EngineType;
use Modules\Chat\Domain\ParticipantType;
use Modules\Chat\Domain\Priority;
use Modules\Shared\Kernel\ChatMessageId;
use Modules\Shared\Kernel\ChatSessionId;
use Modules\Shared\Kernel\ChatThreadId;
use PHPUnit\Framework\TestCase;

use function str_repeat;

use Symfony\Component\Uid\Uuid;

final class ChatMessageTest extends TestCase
{
    public function testShouldExposeFieldsWhenSent(): void
    {
        $id = ChatMessageId::generate();
        $sessionId = ChatSessionId::generate();
        $threadId = ChatThreadId::generate();
        $senderId = Uuid::v7();
        $recipientId = Uuid::v7();
        $engineId = Uuid::v7();
        $replyToId = ChatMessageId::generate();
        $metadata = ['mentions' => ['@maja'], 'tags' => ['onboarding']];
        $now = new DateTimeImmutable('2026-11-16T11:00:00+00:00');

        $sut = ChatMessage::send(
            $id,
            $sessionId,
            $threadId,
            ParticipantType::User,
            $senderId,
            ParticipantType::Agent,
            $recipientId,
            'First message',
            Priority::P1,
            'Hello Maja, can we start onboarding?',
            ContentType::Text,
            $metadata,
            $replyToId,
            $engineId,
            EngineType::CliTerminal,
            ClassifiedBy::Sender,
            0.85,
            $now,
        );

        self::assertSame($id, $sut->id());
        self::assertSame($sessionId, $sut->sessionId());
        self::assertSame($threadId, $sut->threadId());
        self::assertSame(ParticipantType::User, $sut->senderType());
        self::assertSame($senderId, $sut->senderId());
        self::assertSame(ParticipantType::Agent, $sut->recipientType());
        self::assertSame($recipientId, $sut->recipientId());
        self::assertSame('First message', $sut->subject());
        self::assertSame(Priority::P1, $sut->priority());
        self::assertSame('Hello Maja, can we start onboarding?', $sut->content());
        self::assertSame(ContentType::Text, $sut->contentType());
        self::assertSame($metadata, $sut->metadata());
        self::assertSame($replyToId, $sut->replyToMessageId());
        self::assertSame($engineId, $sut->engineId());
        self::assertSame(EngineType::CliTerminal, $sut->engineType());
        self::assertSame(ClassifiedBy::Sender, $sut->classifiedBy());
        self::assertSame(0.85, $sut->classificationConfidence());
        self::assertEquals($now, $sut->createdAt());
    }

    public function testShouldAcceptNullReplyToAndNullConfidenceAndEmptyMetadata(): void
    {
        $sut = $this->makeMessage(metadata: []);

        self::assertNull($sut->replyToMessageId());
        self::assertNull($sut->classificationConfidence());
        self::assertSame([], $sut->metadata());
    }

    public function testShouldRejectBlankSubjectWhenSent(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->makeMessage(subject: '   ');
    }

    public function testShouldRejectTooLongSubjectWhenSent(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->makeMessage(subject: str_repeat('a', 121));
    }

    public function testShouldAcceptSubjectAtMaxLengthWhenSent(): void
    {
        $subject = str_repeat('a', 120);

        $sut = $this->makeMessage(subject: $subject);

        self::assertSame($subject, $sut->subject());
    }

    public function testShouldRejectBlankContentWhenSent(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->makeMessage(content: "   \n\t");
    }

    public function testShouldRejectClassificationConfidenceBelowZero(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->makeMessage(classificationConfidence: -0.1);
    }

    public function testShouldRejectClassificationConfidenceAboveOne(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->makeMessage(classificationConfidence: 1.01);
    }

    public function testShouldAcceptClassificationConfidenceAtBoundaries(): void
    {
        $zero = $this->makeMessage(classificationConfidence: 0.0);
        $one = $this->makeMessage(classificationConfidence: 1.0);

        self::assertSame(0.0, $zero->classificationConfidence());
        self::assertSame(1.0, $one->classificationConfidence());
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function makeMessage(
        string $subject = 'Some subject',
        string $content = 'Some content',
        ?ChatMessageId $replyToMessageId = null,
        ?float $classificationConfidence = null,
        array $metadata = [],
    ): ChatMessage {
        return ChatMessage::send(
            ChatMessageId::generate(),
            ChatSessionId::generate(),
            ChatThreadId::generate(),
            ParticipantType::User,
            Uuid::v7(),
            ParticipantType::Agent,
            Uuid::v7(),
            $subject,
            Priority::P2,
            $content,
            ContentType::Text,
            $metadata,
            $replyToMessageId,
            Uuid::v7(),
            EngineType::CliTerminal,
            ClassifiedBy::Sender,
            $classificationConfidence,
            new DateTimeImmutable('2026-11-16T11:00:00+00:00'),
        );
    }
}
