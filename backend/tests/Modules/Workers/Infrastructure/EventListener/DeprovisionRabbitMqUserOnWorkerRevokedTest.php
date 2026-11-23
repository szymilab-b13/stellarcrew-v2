<?php

declare(strict_types=1);

namespace App\Tests\Modules\Workers\Infrastructure\EventListener;

use App\Tests\Fixtures\Workers\SpyRabbitMqAdminClient;
use DateTimeImmutable;
use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\WorkerId;
use Modules\Workers\Api\Event\WorkerRevoked;
use Modules\Workers\Infrastructure\EventListener\DeprovisionRabbitMqUserOnWorkerRevoked;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class DeprovisionRabbitMqUserOnWorkerRevokedTest extends TestCase
{
    public function testShouldRemoveRabbitMqUserWithEventUsername(): void
    {
        $admin = new SpyRabbitMqAdminClient();
        $sut = new DeprovisionRabbitMqUserOnWorkerRevoked($admin);

        $sut(new WorkerRevoked(
            workerId: new WorkerId(Uuid::v7()),
            ownerUserId: new UserId(Uuid::v7()),
            actorId: new UserId(Uuid::v7()),
            occurredAt: new DateTimeImmutable('2026-11-23T12:30:00+00:00'),
            rabbitmqUser: 'worker-to-remove',
        ));

        self::assertSame(['worker-to-remove'], $admin->removed);
    }

    public function testShouldNotCallProvisionWorkerUser(): void
    {
        $admin = new SpyRabbitMqAdminClient();
        $sut = new DeprovisionRabbitMqUserOnWorkerRevoked($admin);

        $sut(new WorkerRevoked(
            workerId: new WorkerId(Uuid::v7()),
            ownerUserId: new UserId(Uuid::v7()),
            actorId: new UserId(Uuid::v7()),
            occurredAt: new DateTimeImmutable('2026-11-23T12:30:00+00:00'),
            rabbitmqUser: 'worker-to-remove',
        ));

        self::assertSame([], $admin->provisioned);
    }
}
