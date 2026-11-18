<?php

declare(strict_types=1);

namespace App\Tests\Modules\Workers\Domain;

use DateTimeImmutable;
use InvalidArgumentException;
use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\WorkerId;
use Modules\Workers\Domain\Worker;
use PHPUnit\Framework\TestCase;

use function str_repeat;

use Symfony\Component\Uid\Uuid;

final class WorkerTest extends TestCase
{
    public function testShouldExposeFieldsWhenCreated(): void
    {
        $id = WorkerId::generate();
        $ownerId = new UserId(Uuid::v7());
        $now = new DateTimeImmutable('2026-11-18T15:00:00+00:00');

        $sut = Worker::create(
            $id,
            $ownerId,
            'MacBook Pro',
            'worker-mac-01',
            'hash-xyz',
            $now,
            'macbook.local',
        );

        self::assertTrue($sut->id()->equals($id));
        self::assertTrue($sut->ownerUserId()->equals($ownerId));
        self::assertSame('MacBook Pro', $sut->name());
        self::assertSame('macbook.local', $sut->hostLabel());
        self::assertSame('hash-xyz', $sut->secretTokenHash());
        self::assertSame('worker-mac-01', $sut->rabbitmqUser());
        self::assertNull($sut->lastHeartbeatAt());
        self::assertEquals($now, $sut->createdAt());
        self::assertEquals($now, $sut->updatedAt());
    }

    public function testShouldAllowNullHostLabelWhenCreated(): void
    {
        $sut = $this->makeWorker(hostLabel: null);

        self::assertNull($sut->hostLabel());
    }

    public function testShouldRejectBlankNameWhenCreated(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->makeWorker(name: '   ');
    }

    public function testShouldRejectNameExceedingLimitWhenCreated(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->makeWorker(name: str_repeat('x', Worker::NAME_MAX + 1));
    }

    public function testShouldRejectHostLabelExceedingLimitWhenCreated(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->makeWorker(hostLabel: str_repeat('x', Worker::HOST_LABEL_MAX + 1));
    }

    public function testShouldRejectBlankRabbitmqUserWhenCreated(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->makeWorker(rabbitmqUser: '   ');
    }

    public function testShouldRejectInvalidRabbitmqUserPatternWhenCreated(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->makeWorker(rabbitmqUser: 'Bad Worker!');
    }

    public function testShouldRejectBlankSecretTokenHashWhenCreated(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->makeWorker(secretTokenHash: '   ');
    }

    public function testShouldRenameAndBumpUpdatedAtWhenRenamed(): void
    {
        $sut = $this->makeWorker();
        $later = new DateTimeImmutable('2026-11-18T16:00:00+00:00');

        $sut->rename('Updated Name', $later);

        self::assertSame('Updated Name', $sut->name());
        self::assertEquals($later, $sut->updatedAt());
    }

    public function testShouldLeaveUpdatedAtUnchangedWhenRenamingToSameName(): void
    {
        $initialNow = new DateTimeImmutable('2026-11-18T15:00:00+00:00');
        $sut = $this->makeWorker(name: 'Same Name', now: $initialNow);
        $later = new DateTimeImmutable('2026-11-18T16:00:00+00:00');

        $sut->rename('Same Name', $later);

        self::assertEquals($initialNow, $sut->updatedAt());
    }

    public function testShouldUpdateHostLabelWhenCalled(): void
    {
        $sut = $this->makeWorker();
        $later = new DateTimeImmutable('2026-11-18T16:00:00+00:00');

        $sut->updateHostLabel('new-host.internal', $later);

        self::assertSame('new-host.internal', $sut->hostLabel());
        self::assertEquals($later, $sut->updatedAt());
    }

    public function testShouldClearHostLabelWhenPassedNull(): void
    {
        $sut = $this->makeWorker(hostLabel: 'old-host');
        $later = new DateTimeImmutable('2026-11-18T16:00:00+00:00');

        $sut->updateHostLabel(null, $later);

        self::assertNull($sut->hostLabel());
    }

    public function testShouldRecordHeartbeatWhenCalled(): void
    {
        $sut = $this->makeWorker();
        $heartbeatAt = new DateTimeImmutable('2026-11-18T16:00:00+00:00');

        $sut->recordHeartbeat($heartbeatAt);

        self::assertEquals($heartbeatAt, $sut->lastHeartbeatAt());
        self::assertEquals($heartbeatAt, $sut->updatedAt());
    }

    public function testShouldReportOfflineWhenNoHeartbeatRecorded(): void
    {
        $sut = $this->makeWorker();
        $now = new DateTimeImmutable('2026-11-18T16:00:00+00:00');

        self::assertFalse($sut->isOnline($now));
    }

    public function testShouldReportOnlineWhenHeartbeatWithinThreshold(): void
    {
        $sut = $this->makeWorker();
        $heartbeatAt = new DateTimeImmutable('2026-11-18T16:00:00+00:00');
        $sut->recordHeartbeat($heartbeatAt);
        $now = new DateTimeImmutable('2026-11-18T16:00:30+00:00');

        self::assertTrue($sut->isOnline($now, thresholdSeconds: 60));
    }

    public function testShouldReportOfflineWhenHeartbeatOutsideThreshold(): void
    {
        $sut = $this->makeWorker();
        $heartbeatAt = new DateTimeImmutable('2026-11-18T16:00:00+00:00');
        $sut->recordHeartbeat($heartbeatAt);
        $now = new DateTimeImmutable('2026-11-18T16:02:00+00:00');

        self::assertFalse($sut->isOnline($now, thresholdSeconds: 60));
    }

    public function testShouldRotateSecretAndBumpUpdatedAtWhenCalled(): void
    {
        $sut = $this->makeWorker();
        $later = new DateTimeImmutable('2026-11-18T16:00:00+00:00');

        $sut->rotateSecret('new-hash', $later);

        self::assertSame('new-hash', $sut->secretTokenHash());
        self::assertEquals($later, $sut->updatedAt());
    }

    private function makeWorker(
        ?string $name = null,
        ?string $hostLabel = 'default-host',
        ?string $rabbitmqUser = null,
        ?string $secretTokenHash = null,
        ?DateTimeImmutable $now = null,
    ): Worker {
        return Worker::create(
            WorkerId::generate(),
            new UserId(Uuid::v7()),
            $name ?? 'Default Worker',
            $rabbitmqUser ?? 'worker-default-01',
            $secretTokenHash ?? 'hash-default',
            $now ?? new DateTimeImmutable('2026-11-18T15:00:00+00:00'),
            $hostLabel,
        );
    }
}
