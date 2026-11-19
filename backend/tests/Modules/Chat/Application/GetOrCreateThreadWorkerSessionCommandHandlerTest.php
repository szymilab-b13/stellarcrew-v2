<?php

declare(strict_types=1);

namespace App\Tests\Modules\Chat\Application;

use App\Tests\Fixtures\Chat\InMemoryChatThreadRepository;
use App\Tests\Fixtures\Chat\InMemoryChatThreadWorkerSessionRepository;
use DateTimeImmutable;
use Modules\Chat\Application\GetOrCreateThreadWorkerSessionCommand;
use Modules\Chat\Application\GetOrCreateThreadWorkerSessionCommandHandler;
use Modules\Chat\Domain\ChatThread;
use Modules\Chat\Domain\ChatThreadWorkerSession;
use Modules\Shared\Kernel\ChatSessionId;
use Modules\Shared\Kernel\ChatThreadId;
use Modules\Shared\Kernel\Clock;
use Modules\Shared\Kernel\WorkerId;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Uuid;

final class GetOrCreateThreadWorkerSessionCommandHandlerTest extends TestCase
{
    public function testShouldCreateNewSessionWhenNoneExistsForThreadAndWorker(): void
    {
        $threads = new InMemoryChatThreadRepository();
        $sessions = new InMemoryChatThreadWorkerSessionRepository();
        $threadId = ChatThreadId::generate();
        $workerId = WorkerId::generate();
        $threads->save(ChatThread::create($threadId, ChatSessionId::generate(), 'Test', new DateTimeImmutable('2026-11-18T15:00:00+00:00')));
        $sut = new GetOrCreateThreadWorkerSessionCommandHandler($threads, $sessions, $this->fixedClock(new DateTimeImmutable('2026-11-18T15:05:00+00:00')));

        $result = $sut(new GetOrCreateThreadWorkerSessionCommand($threadId, $workerId));

        self::assertTrue($result->threadId()->equals($threadId));
        self::assertTrue($result->workerId()->equals($workerId));
        self::assertNotNull($sessions->findByThreadAndWorker($threadId, $workerId));
    }

    public function testShouldReturnExistingSessionWhenPresent(): void
    {
        $threads = new InMemoryChatThreadRepository();
        $sessions = new InMemoryChatThreadWorkerSessionRepository();
        $threadId = ChatThreadId::generate();
        $workerId = WorkerId::generate();
        $existingSid = Uuid::v7();
        $sessions->save(ChatThreadWorkerSession::start($threadId, $workerId, $existingSid, new DateTimeImmutable('2026-11-18T15:00:00+00:00')));
        $sut = new GetOrCreateThreadWorkerSessionCommandHandler($threads, $sessions, $this->fixedClock(new DateTimeImmutable('2026-11-18T15:05:00+00:00')));

        $result = $sut(new GetOrCreateThreadWorkerSessionCommand($threadId, $workerId));

        self::assertTrue($result->claudeSessionId()->equals($existingSid));
    }

    public function testShouldThrow404WhenThreadMissing(): void
    {
        $sut = new GetOrCreateThreadWorkerSessionCommandHandler(
            new InMemoryChatThreadRepository(),
            new InMemoryChatThreadWorkerSessionRepository(),
            $this->fixedClock(new DateTimeImmutable('2026-11-18T15:00:00+00:00')),
        );

        $this->expectException(NotFoundHttpException::class);
        $sut(new GetOrCreateThreadWorkerSessionCommand(ChatThreadId::generate(), WorkerId::generate()));
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
