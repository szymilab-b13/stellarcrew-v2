<?php

declare(strict_types=1);

namespace App\Tests\Modules\Workers\Application;

use App\Tests\Fixtures\Workers\InMemoryWorkerRepository;
use DateTimeImmutable;
use Modules\Shared\Kernel\Clock;
use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\WorkerId;
use Modules\Workers\Application\RecordHeartbeatCommand;
use Modules\Workers\Application\RecordHeartbeatCommandHandler;
use Modules\Workers\Domain\Worker;
use Modules\Workers\Infrastructure\Support\WorkerSecretTokenHasher;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Uid\Uuid;

final class RecordHeartbeatCommandHandlerTest extends TestCase
{
    public function testShouldUpdateLastHeartbeatWhenCredentialsValid(): void
    {
        $workers = new InMemoryWorkerRepository();
        $hasher = new WorkerSecretTokenHasher();
        $workerId = WorkerId::generate();
        $plaintext = 'test-secret-xyz';
        $now = new DateTimeImmutable('2026-11-18T15:00:00+00:00');
        $worker = Worker::create($workerId, new UserId(Uuid::v7()), 'Test', 'worker-xyz', $hasher->hash($plaintext), $now);
        $workers->save($worker);
        $beatAt = new DateTimeImmutable('2026-11-18T15:05:00+00:00');
        $sut = new RecordHeartbeatCommandHandler($workers, $hasher, $this->fixedClock($beatAt));

        $sut(new RecordHeartbeatCommand($workerId, $plaintext));

        self::assertEquals($beatAt, $workers->findById($workerId)?->lastHeartbeatAt());
    }

    public function testShouldThrowWhenWorkerUnknown(): void
    {
        $sut = new RecordHeartbeatCommandHandler(
            new InMemoryWorkerRepository(),
            new WorkerSecretTokenHasher(),
            $this->fixedClock(new DateTimeImmutable('2026-11-18T15:00:00+00:00')),
        );

        $this->expectException(UserNotFoundException::class);
        $sut(new RecordHeartbeatCommand(WorkerId::generate(), 'any-secret'));
    }

    public function testShouldThrowWhenSecretInvalid(): void
    {
        $workers = new InMemoryWorkerRepository();
        $hasher = new WorkerSecretTokenHasher();
        $workerId = WorkerId::generate();
        $workers->save(Worker::create(
            $workerId,
            new UserId(Uuid::v7()),
            'Test',
            'worker-xyz',
            $hasher->hash('correct-secret'),
            new DateTimeImmutable('2026-11-18T15:00:00+00:00'),
        ));
        $sut = new RecordHeartbeatCommandHandler($workers, $hasher, $this->fixedClock(new DateTimeImmutable('2026-11-18T15:05:00+00:00')));

        $this->expectException(AccessDeniedException::class);
        $sut(new RecordHeartbeatCommand($workerId, 'wrong-secret'));
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
