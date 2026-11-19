<?php

declare(strict_types=1);

namespace App\Tests\Modules\Chat\Application;

use App\Tests\Fixtures\Chat\InMemoryChatMessageRepository;
use App\Tests\Fixtures\Chat\InMemoryChatSessionRepository;
use App\Tests\Fixtures\Chat\InMemoryChatThreadRepository;
use App\Tests\Fixtures\Chat\InMemoryChatThreadWorkerSessionRepository;
use DateTimeImmutable;
use Modules\Chat\Application\RecordAgentReplyCommand;
use Modules\Chat\Application\RecordAgentReplyCommandHandler;
use Modules\Chat\Domain\ChatSession;
use Modules\Chat\Domain\ChatThread;
use Modules\Chat\Domain\ChatThreadWorkerSession;
use Modules\Chat\Domain\ParticipantType;
use Modules\Shared\Kernel\ChatSessionId;
use Modules\Shared\Kernel\ChatThreadId;
use Modules\Shared\Kernel\Clock;
use Modules\Shared\Kernel\OrganizationId;
use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\WorkerId;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Uuid;

final class RecordAgentReplyCommandHandlerTest extends TestCase
{
    public function testShouldPersistAgentMessageWithHandledByWorker(): void
    {
        $threads = new InMemoryChatThreadRepository();
        $chatSessions = new InMemoryChatSessionRepository();
        $messages = new InMemoryChatMessageRepository();
        $sessions = new InMemoryChatThreadWorkerSessionRepository();
        $owner = new UserId(Uuid::v7());
        $sessionId = ChatSessionId::generate();
        $threadId = ChatThreadId::generate();
        $workerId = WorkerId::generate();
        $agentId = Uuid::v7();
        $now = new DateTimeImmutable('2026-11-18T15:00:00+00:00');
        $chatSessions->save(ChatSession::create($sessionId, new OrganizationId(Uuid::v7()), $owner, 'Chat', $now));
        $threads->save(ChatThread::create($threadId, $sessionId, 'Deploy', $now));
        $sessions->save(ChatThreadWorkerSession::start($threadId, $workerId, Uuid::v7(), $now));
        $later = new DateTimeImmutable('2026-11-18T15:05:00+00:00');
        $sut = new RecordAgentReplyCommandHandler($threads, $chatSessions, $messages, $sessions, $this->fixedClock($later));

        $message = $sut(new RecordAgentReplyCommand($threadId, $workerId, $agentId, 'Odpowiedź agenta'));

        self::assertSame('Odpowiedź agenta', $message->content());
        self::assertSame(ParticipantType::Agent, $message->senderType());
        self::assertTrue($message->senderId()->equals($agentId));
        self::assertTrue($message->recipientId()->equals($owner->value));
        self::assertNotNull($message->handledByWorkerId());
        self::assertTrue($message->handledByWorkerId()->equals($workerId));
        self::assertEquals($later, $sessions->findByThreadAndWorker($threadId, $workerId)?->lastMessageAt());
    }

    public function testShouldThrow404WhenThreadMissing(): void
    {
        $sut = new RecordAgentReplyCommandHandler(
            new InMemoryChatThreadRepository(),
            new InMemoryChatSessionRepository(),
            new InMemoryChatMessageRepository(),
            new InMemoryChatThreadWorkerSessionRepository(),
            $this->fixedClock(new DateTimeImmutable('2026-11-18T15:00:00+00:00')),
        );

        $this->expectException(NotFoundHttpException::class);
        $sut(new RecordAgentReplyCommand(ChatThreadId::generate(), WorkerId::generate(), Uuid::v7(), 'reply'));
    }

    private function fixedClock(DateTimeImmutable $now): Clock
    {
        return new readonly class ($now) implements Clock {
            public function __construct(private DateTimeImmutable $now)
            {
            }

            public function now(): DateTimeImmutable
            {
                return $this->now;
            }
        };
    }
}
