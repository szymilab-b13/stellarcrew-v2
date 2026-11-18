<?php

declare(strict_types=1);

namespace App\Tests\Modules\Chat\Application;

use App\Tests\Fixtures\Chat\InMemoryChatMessageRepository;
use App\Tests\Fixtures\Chat\InMemoryChatSessionRepository;
use App\Tests\Fixtures\Chat\InMemoryChatThreadRepository;
use App\Tests\Fixtures\Chat\InMemoryChatThreadSummaryRepository;
use DateTimeImmutable;
use Modules\Chat\Application\GetAgentConversationsQuery;
use Modules\Chat\Application\GetAgentConversationsQueryHandler;
use Modules\Chat\Domain\ChatMessage;
use Modules\Chat\Domain\ChatSession;
use Modules\Chat\Domain\ChatThread;
use Modules\Chat\Domain\ChatThreadSummary;
use Modules\Chat\Domain\ClassifiedBy;
use Modules\Chat\Domain\ContentType;
use Modules\Chat\Domain\EngineType;
use Modules\Chat\Domain\ParticipantType;
use Modules\Chat\Domain\Priority;
use Modules\Shared\Kernel\ChatMessageId;
use Modules\Shared\Kernel\ChatSessionId;
use Modules\Shared\Kernel\ChatThreadId;
use Modules\Shared\Kernel\OrganizationId;
use Modules\Shared\Kernel\UserId;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class GetAgentConversationsQueryHandlerTest extends TestCase
{
    public function testShouldReturnEmptyListWhenAgentHasNoMessages(): void
    {
        $messages = new InMemoryChatMessageRepository();
        $threads = new InMemoryChatThreadRepository();
        $sessions = new InMemoryChatSessionRepository();
        $summaries = new InMemoryChatThreadSummaryRepository();
        $actorId = new UserId(Uuid::v7());
        $agentId = Uuid::v7();

        $sut = new GetAgentConversationsQueryHandler($messages, $threads, $sessions, $summaries);

        $result = $sut(new GetAgentConversationsQuery($actorId, $agentId));

        self::assertSame([], $result);
    }

    public function testShouldReturnSingleConversationWhenAgentHasOneThread(): void
    {
        $messages = new InMemoryChatMessageRepository();
        $threads = new InMemoryChatThreadRepository();
        $sessions = new InMemoryChatSessionRepository();
        $summaries = new InMemoryChatThreadSummaryRepository();
        $actorId = new UserId(Uuid::v7());
        $agentId = Uuid::v7();
        $session = $this->makeSession($actorId, new DateTimeImmutable('2026-11-16T10:00:00+00:00'));
        $sessions->save($session);
        $thread = $this->makeThread($session->id(), 'Onboarding', new DateTimeImmutable('2026-11-16T10:05:00+00:00'));
        $threads->save($thread);
        $messages->save($this->makeMessageBetween(
            $session->id(),
            $thread->id(),
            $actorId->value,
            $agentId,
            new DateTimeImmutable('2026-11-16T10:10:00+00:00'),
        ));

        $sut = new GetAgentConversationsQueryHandler($messages, $threads, $sessions, $summaries);

        $result = $sut(new GetAgentConversationsQuery($actorId, $agentId));

        self::assertCount(1, $result);
        self::assertTrue($result[0]->threadId->equals($thread->id()));
        self::assertSame('Onboarding', $result[0]->subject);
        self::assertNull($result[0]->shortDescription);
        self::assertSame(1, $result[0]->messageCount);
        self::assertEquals(new DateTimeImmutable('2026-11-16T10:10:00+00:00'), $result[0]->lastMessageAt);
        self::assertNull($result[0]->parentThreadId);
    }

    public function testShouldOrderConversationsByLatestMessageDescending(): void
    {
        $messages = new InMemoryChatMessageRepository();
        $threads = new InMemoryChatThreadRepository();
        $sessions = new InMemoryChatSessionRepository();
        $summaries = new InMemoryChatThreadSummaryRepository();
        $actorId = new UserId(Uuid::v7());
        $agentId = Uuid::v7();
        $session = $this->makeSession($actorId, new DateTimeImmutable('2026-11-16T10:00:00+00:00'));
        $sessions->save($session);

        $older = $this->makeThread($session->id(), 'Older', new DateTimeImmutable('2026-11-16T10:05:00+00:00'));
        $newer = $this->makeThread($session->id(), 'Newer', new DateTimeImmutable('2026-11-16T10:06:00+00:00'));
        $threads->save($older);
        $threads->save($newer);

        $messages->save($this->makeMessageBetween(
            $session->id(),
            $older->id(),
            $actorId->value,
            $agentId,
            new DateTimeImmutable('2026-11-16T10:10:00+00:00'),
        ));
        $messages->save($this->makeMessageBetween(
            $session->id(),
            $newer->id(),
            $agentId,
            $actorId->value,
            new DateTimeImmutable('2026-11-16T11:00:00+00:00'),
        ));

        $sut = new GetAgentConversationsQueryHandler($messages, $threads, $sessions, $summaries);

        $result = $sut(new GetAgentConversationsQuery($actorId, $agentId));

        self::assertCount(2, $result);
        self::assertTrue($result[0]->threadId->equals($newer->id()));
        self::assertTrue($result[1]->threadId->equals($older->id()));
    }

    public function testShouldSkipThreadWhenSessionOwnerIsForeign(): void
    {
        $messages = new InMemoryChatMessageRepository();
        $threads = new InMemoryChatThreadRepository();
        $sessions = new InMemoryChatSessionRepository();
        $summaries = new InMemoryChatThreadSummaryRepository();
        $actorId = new UserId(Uuid::v7());
        $foreignOwnerId = new UserId(Uuid::v7());
        $agentId = Uuid::v7();

        $mine = $this->makeSession($actorId, new DateTimeImmutable('2026-11-16T10:00:00+00:00'));
        $foreign = $this->makeSession($foreignOwnerId, new DateTimeImmutable('2026-11-16T10:00:00+00:00'));
        $sessions->save($mine);
        $sessions->save($foreign);

        $mineThread = $this->makeThread($mine->id(), 'Mine', new DateTimeImmutable('2026-11-16T10:05:00+00:00'));
        $foreignThread = $this->makeThread($foreign->id(), 'Foreign', new DateTimeImmutable('2026-11-16T10:05:00+00:00'));
        $threads->save($mineThread);
        $threads->save($foreignThread);

        $messages->save($this->makeMessageBetween(
            $mine->id(),
            $mineThread->id(),
            $actorId->value,
            $agentId,
            new DateTimeImmutable('2026-11-16T10:10:00+00:00'),
        ));
        $messages->save($this->makeMessageBetween(
            $foreign->id(),
            $foreignThread->id(),
            $foreignOwnerId->value,
            $agentId,
            new DateTimeImmutable('2026-11-16T10:11:00+00:00'),
        ));

        $sut = new GetAgentConversationsQueryHandler($messages, $threads, $sessions, $summaries);

        $result = $sut(new GetAgentConversationsQuery($actorId, $agentId));

        self::assertCount(1, $result);
        self::assertTrue($result[0]->threadId->equals($mineThread->id()));
    }

    public function testShouldIncludeShortDescriptionFromSummaryWhenPresent(): void
    {
        $messages = new InMemoryChatMessageRepository();
        $threads = new InMemoryChatThreadRepository();
        $sessions = new InMemoryChatSessionRepository();
        $summaries = new InMemoryChatThreadSummaryRepository();
        $actorId = new UserId(Uuid::v7());
        $agentId = Uuid::v7();

        $session = $this->makeSession($actorId, new DateTimeImmutable('2026-11-16T10:00:00+00:00'));
        $sessions->save($session);
        $thread = $this->makeThread($session->id(), 'Billing issue', new DateTimeImmutable('2026-11-16T10:05:00+00:00'));
        $threads->save($thread);

        $summary = ChatThreadSummary::createEmpty($thread->id());
        $summary->updateFromGenerator(
            'User cannot access invoices',
            null,
            null,
            'stub-v1',
            new DateTimeImmutable('2026-11-16T10:12:00+00:00'),
        );
        $summaries->save($summary);

        $messages->save($this->makeMessageBetween(
            $session->id(),
            $thread->id(),
            $actorId->value,
            $agentId,
            new DateTimeImmutable('2026-11-16T10:10:00+00:00'),
        ));

        $sut = new GetAgentConversationsQueryHandler($messages, $threads, $sessions, $summaries);

        $result = $sut(new GetAgentConversationsQuery($actorId, $agentId));

        self::assertCount(1, $result);
        self::assertSame('User cannot access invoices', $result[0]->shortDescription);
    }

    public function testShouldExposeParentThreadIdWhenThreadIsAttached(): void
    {
        $messages = new InMemoryChatMessageRepository();
        $threads = new InMemoryChatThreadRepository();
        $sessions = new InMemoryChatSessionRepository();
        $summaries = new InMemoryChatThreadSummaryRepository();
        $actorId = new UserId(Uuid::v7());
        $agentId = Uuid::v7();

        $session = $this->makeSession($actorId, new DateTimeImmutable('2026-11-16T10:00:00+00:00'));
        $sessions->save($session);

        $parent = $this->makeThread($session->id(), 'Root', new DateTimeImmutable('2026-11-16T10:05:00+00:00'));
        $threads->save($parent);
        $child = $this->makeThread($session->id(), 'Follow-up', new DateTimeImmutable('2026-11-16T10:06:00+00:00'));
        $child->attachToParent($parent->id(), new DateTimeImmutable('2026-11-16T10:06:30+00:00'));

        $threads->save($child);

        $messages->save($this->makeMessageBetween(
            $session->id(),
            $child->id(),
            $actorId->value,
            $agentId,
            new DateTimeImmutable('2026-11-16T10:10:00+00:00'),
        ));

        $sut = new GetAgentConversationsQueryHandler($messages, $threads, $sessions, $summaries);

        $result = $sut(new GetAgentConversationsQuery($actorId, $agentId));

        self::assertCount(1, $result);
        self::assertNotNull($result[0]->parentThreadId);
        self::assertTrue($result[0]->parentThreadId->equals($parent->id()));
    }

    public function testShouldCountAllMessagesInThreadRegardlessOfAgentParticipation(): void
    {
        $messages = new InMemoryChatMessageRepository();
        $threads = new InMemoryChatThreadRepository();
        $sessions = new InMemoryChatSessionRepository();
        $summaries = new InMemoryChatThreadSummaryRepository();
        $actorId = new UserId(Uuid::v7());
        $agentId = Uuid::v7();
        $otherAgentId = Uuid::v7();

        $session = $this->makeSession($actorId, new DateTimeImmutable('2026-11-16T10:00:00+00:00'));
        $sessions->save($session);
        $thread = $this->makeThread($session->id(), 'Multi-agent', new DateTimeImmutable('2026-11-16T10:05:00+00:00'));
        $threads->save($thread);

        $messages->save($this->makeMessageBetween(
            $session->id(),
            $thread->id(),
            $actorId->value,
            $agentId,
            new DateTimeImmutable('2026-11-16T10:10:00+00:00'),
        ));
        $messages->save($this->makeMessageBetween(
            $session->id(),
            $thread->id(),
            $actorId->value,
            $otherAgentId,
            new DateTimeImmutable('2026-11-16T10:11:00+00:00'),
        ));
        $messages->save($this->makeMessageBetween(
            $session->id(),
            $thread->id(),
            $agentId,
            $actorId->value,
            new DateTimeImmutable('2026-11-16T10:12:00+00:00'),
        ));

        $sut = new GetAgentConversationsQueryHandler($messages, $threads, $sessions, $summaries);

        $result = $sut(new GetAgentConversationsQuery($actorId, $agentId));

        self::assertCount(1, $result);
        self::assertSame(3, $result[0]->messageCount);
        self::assertEquals(new DateTimeImmutable('2026-11-16T10:12:00+00:00'), $result[0]->lastMessageAt);
    }

    private function makeSession(UserId $ownerId, DateTimeImmutable $startedAt): ChatSession
    {
        return ChatSession::create(
            ChatSessionId::generate(),
            OrganizationId::generate(),
            $ownerId,
            'Chat',
            $startedAt,
        );
    }

    private function makeThread(ChatSessionId $sessionId, string $subject, DateTimeImmutable $createdAt): ChatThread
    {
        return ChatThread::create(
            ChatThreadId::generate(),
            $sessionId,
            $subject,
            $createdAt,
        );
    }

    private function makeMessageBetween(
        ChatSessionId $sessionId,
        ChatThreadId $threadId,
        Uuid $senderId,
        Uuid $recipientId,
        DateTimeImmutable $createdAt,
    ): ChatMessage {
        return ChatMessage::send(
            ChatMessageId::generate(),
            $sessionId,
            $threadId,
            ParticipantType::User,
            $senderId,
            ParticipantType::Agent,
            $recipientId,
            'Subject',
            Priority::P2,
            'Body',
            ContentType::Text,
            [],
            null,
            Uuid::v7(),
            EngineType::CliTerminal,
            ClassifiedBy::Sender,
            null,
            $createdAt,
        );
    }
}
