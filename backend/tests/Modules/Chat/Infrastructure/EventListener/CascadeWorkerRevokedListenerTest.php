<?php

declare(strict_types=1);

namespace App\Tests\Modules\Chat\Infrastructure\EventListener;

use App\Tests\Fixtures\Chat\InMemoryChatThreadRepository;
use App\Tests\Fixtures\Chat\InMemoryChatThreadWorkerSessionRepository;
use DateTimeImmutable;
use Modules\Chat\Domain\ChatThread;
use Modules\Chat\Domain\ChatThreadWorkerSession;
use Modules\Chat\Infrastructure\EventListener\CascadeWorkerRevokedListener;
use Modules\Shared\Kernel\ChatSessionId;
use Modules\Shared\Kernel\ChatThreadId;
use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\WorkerId;
use Modules\Workers\Api\Event\WorkerRevoked;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class CascadeWorkerRevokedListenerTest extends TestCase
{
    public function testShouldClearCurrentWorkerOnMatchingThreads(): void
    {
        $threads = new InMemoryChatThreadRepository();
        $sessions = new InMemoryChatThreadWorkerSessionRepository();
        $workerId = WorkerId::generate();
        $otherWorkerId = WorkerId::generate();
        $createdAt = new DateTimeImmutable('2026-11-18T10:00:00+00:00');
        $matching = ChatThread::create(ChatThreadId::generate(), ChatSessionId::generate(), 'Thread A', $createdAt);
        $matching->assignCurrentWorker($workerId, $createdAt);

        $threads->save($matching);
        $other = ChatThread::create(ChatThreadId::generate(), ChatSessionId::generate(), 'Thread B', $createdAt);
        $other->assignCurrentWorker($otherWorkerId, $createdAt);

        $threads->save($other);
        $sut = new CascadeWorkerRevokedListener($threads, $sessions);

        $sut(new WorkerRevoked(
            $workerId,
            new UserId(Uuid::v7()),
            new UserId(Uuid::v7()),
            new DateTimeImmutable('2026-11-18T12:00:00+00:00'),
            'worker-threads-test',
        ));

        self::assertNull($threads->findById($matching->id())?->currentWorkerId());
        self::assertTrue($threads->findById($other->id())?->currentWorkerId()?->equals($otherWorkerId));
    }

    public function testShouldDeleteOnlyRevokedWorkerSessions(): void
    {
        $threads = new InMemoryChatThreadRepository();
        $sessions = new InMemoryChatThreadWorkerSessionRepository();
        $workerId = WorkerId::generate();
        $otherWorkerId = WorkerId::generate();
        $createdAt = new DateTimeImmutable('2026-11-18T10:00:00+00:00');
        $sessions->save(ChatThreadWorkerSession::start(ChatThreadId::generate(), $workerId, Uuid::v7(), $createdAt));
        $sessions->save(ChatThreadWorkerSession::start(ChatThreadId::generate(), $workerId, Uuid::v7(), $createdAt));
        $sessions->save(ChatThreadWorkerSession::start(ChatThreadId::generate(), $otherWorkerId, Uuid::v7(), $createdAt));

        $sut = new CascadeWorkerRevokedListener($threads, $sessions);

        $sut(new WorkerRevoked(
            $workerId,
            new UserId(Uuid::v7()),
            new UserId(Uuid::v7()),
            new DateTimeImmutable('2026-11-18T12:00:00+00:00'),
            'worker-sessions-test',
        ));

        $remaining = $sessions->all();
        self::assertCount(1, $remaining);
        self::assertTrue($remaining[0]->workerId()->equals($otherWorkerId));
    }

    public function testShouldDoNothingWhenNoThreadOrSessionReferencesWorker(): void
    {
        $threads = new InMemoryChatThreadRepository();
        $sessions = new InMemoryChatThreadWorkerSessionRepository();
        $otherWorkerId = WorkerId::generate();
        $createdAt = new DateTimeImmutable('2026-11-18T10:00:00+00:00');
        $other = ChatThread::create(ChatThreadId::generate(), ChatSessionId::generate(), 'Thread', $createdAt);
        $other->assignCurrentWorker($otherWorkerId, $createdAt);

        $threads->save($other);
        $sessions->save(ChatThreadWorkerSession::start(ChatThreadId::generate(), $otherWorkerId, Uuid::v7(), $createdAt));
        $sut = new CascadeWorkerRevokedListener($threads, $sessions);

        $sut(new WorkerRevoked(
            WorkerId::generate(),
            new UserId(Uuid::v7()),
            new UserId(Uuid::v7()),
            new DateTimeImmutable('2026-11-18T12:00:00+00:00'),
            'worker-noop-test',
        ));

        self::assertTrue($threads->findById($other->id())?->currentWorkerId()?->equals($otherWorkerId));
        self::assertCount(1, $sessions->all());
    }
}
