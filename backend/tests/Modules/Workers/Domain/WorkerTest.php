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

    public function testShouldExposeNullFingerprintWhenCreated(): void
    {
        $sut = $this->makeWorker();

        self::assertNull($sut->fingerprintHash());
        self::assertNull($sut->fingerprintMeta());
    }

    public function testShouldUpdateFingerprintAndBumpUpdatedAtWhenCalled(): void
    {
        $sut = $this->makeWorker();
        $later = new DateTimeImmutable('2026-11-18T16:00:00+00:00');
        $hash = str_repeat('a', 64);
        $meta = ['hostname' => 'mac-studio', 'machineId' => 'abc-def', 'cpuModel' => 'Apple M2 Pro'];

        $sut->updateFingerprint($hash, $meta, $later);

        self::assertSame($hash, $sut->fingerprintHash());
        self::assertSame($meta, $sut->fingerprintMeta());
        self::assertEquals($later, $sut->updatedAt());
    }

    public function testShouldRejectInvalidFingerprintHashWhenUpdated(): void
    {
        $sut = $this->makeWorker();
        $later = new DateTimeImmutable('2026-11-18T16:00:00+00:00');

        $this->expectException(InvalidArgumentException::class);
        $sut->updateFingerprint('not-a-sha256', null, $later);
    }

    public function testShouldStartWithEmptyFingerprintChangeHistoryWhenCreated(): void
    {
        $sut = $this->makeWorker();

        self::assertSame([], $sut->fingerprintChangeHistory());
    }

    public function testShouldNotAppendHistoryEntryOnInitialFingerprintSet(): void
    {
        $sut = $this->makeWorker();
        $hash = str_repeat('a', 64);
        $later = new DateTimeImmutable('2026-11-18T16:00:00+00:00');

        $sut->updateFingerprint($hash, ['hostname' => 'first'], $later);

        self::assertSame([], $sut->fingerprintChangeHistory());
    }

    public function testShouldAppendAcceptedHistoryEntryWhenFingerprintRotated(): void
    {
        $sut = $this->makeWorker();
        $oldHash = str_repeat('a', 64);
        $newHash = str_repeat('b', 64);
        $firstAt = new DateTimeImmutable('2026-11-18T16:00:00+00:00');
        $acceptAt = new DateTimeImmutable('2026-11-18T17:00:00+00:00');
        $sut->updateFingerprint($oldHash, ['hostname' => 'old'], $firstAt);

        $sut->updateFingerprint($newHash, ['hostname' => 'new'], $acceptAt);

        $history = $sut->fingerprintChangeHistory();
        self::assertCount(1, $history);
        self::assertSame('accepted', $history[0]['status']);
        self::assertSame($oldHash, $history[0]['previousHash']);
        self::assertSame(['hostname' => 'old'], $history[0]['previousMeta']);
        self::assertSame($newHash, $history[0]['newHash']);
        self::assertSame(['hostname' => 'new'], $history[0]['newMeta']);
        self::assertSame($acceptAt->format(DateTimeImmutable::ATOM), $history[0]['at']);
    }

    public function testShouldNotAppendHistoryEntryWhenUpdatingFingerprintToSameHash(): void
    {
        $sut = $this->makeWorker();
        $hash = str_repeat('a', 64);
        $firstAt = new DateTimeImmutable('2026-11-18T16:00:00+00:00');
        $secondAt = new DateTimeImmutable('2026-11-18T17:00:00+00:00');
        $sut->updateFingerprint($hash, ['hostname' => 'old'], $firstAt);

        $sut->updateFingerprint($hash, ['hostname' => 'refreshed'], $secondAt);

        self::assertSame([], $sut->fingerprintChangeHistory());
    }

    public function testShouldAppendDetectedHistoryEntryAndRefreshMetaWhenMismatchRecorded(): void
    {
        $sut = $this->makeWorker();
        $storedHash = str_repeat('a', 64);
        $firstAt = new DateTimeImmutable('2026-11-18T16:00:00+00:00');
        $sut->updateFingerprint($storedHash, ['hostname' => 'old'], $firstAt);
        $newHash = str_repeat('b', 64);
        $mismatchAt = new DateTimeImmutable('2026-11-18T17:00:00+00:00');

        $sut->recordFingerprintMismatch($newHash, ['hostname' => 'new'], $mismatchAt);

        self::assertSame($storedHash, $sut->fingerprintHash());
        self::assertSame(['hostname' => 'new'], $sut->fingerprintMeta());
        self::assertEquals($mismatchAt, $sut->updatedAt());

        $history = $sut->fingerprintChangeHistory();
        self::assertCount(1, $history);
        self::assertSame('detected', $history[0]['status']);
        self::assertSame($storedHash, $history[0]['previousHash']);
        self::assertSame(['hostname' => 'old'], $history[0]['previousMeta']);
        self::assertSame($newHash, $history[0]['newHash']);
        self::assertSame(['hostname' => 'new'], $history[0]['newMeta']);
        self::assertSame($mismatchAt->format(DateTimeImmutable::ATOM), $history[0]['at']);
    }

    public function testShouldRejectInvalidFingerprintHashWhenMismatchRecorded(): void
    {
        $sut = $this->makeWorker();
        $later = new DateTimeImmutable('2026-11-18T16:00:00+00:00');

        $this->expectException(InvalidArgumentException::class);
        $sut->recordFingerprintMismatch('not-a-sha256', null, $later);
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
