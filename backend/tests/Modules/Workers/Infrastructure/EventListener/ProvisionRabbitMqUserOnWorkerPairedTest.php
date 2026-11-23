<?php

declare(strict_types=1);

namespace App\Tests\Modules\Workers\Infrastructure\EventListener;

use App\Tests\Fixtures\Workers\SpyRabbitMqAdminClient;
use DateTimeImmutable;
use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\WorkerId;
use Modules\Workers\Api\Event\WorkerPaired;
use Modules\Workers\Infrastructure\EventListener\ProvisionRabbitMqUserOnWorkerPaired;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class ProvisionRabbitMqUserOnWorkerPairedTest extends TestCase
{
    public function testShouldProvisionRabbitMqUserWithEventCredentials(): void
    {
        $admin = new SpyRabbitMqAdminClient();
        $sut = new ProvisionRabbitMqUserOnWorkerPaired($admin);
        $workerId = new WorkerId(Uuid::v7());

        $sut(new WorkerPaired(
            workerId: $workerId,
            ownerUserId: new UserId(Uuid::v7()),
            rabbitmqUser: 'worker-abc123',
            rabbitmqPassword: 'super-secret-token',
            occurredAt: new DateTimeImmutable('2026-11-23T12:00:00+00:00'),
        ));

        self::assertCount(1, $admin->provisioned);
        self::assertSame('worker-abc123', $admin->provisioned[0]['username']);
        self::assertSame('super-secret-token', $admin->provisioned[0]['password']);
        self::assertSame((string) $workerId, $admin->provisioned[0]['workerId']);
    }

    public function testShouldNotCallRemoveUser(): void
    {
        $admin = new SpyRabbitMqAdminClient();
        $sut = new ProvisionRabbitMqUserOnWorkerPaired($admin);

        $sut(new WorkerPaired(
            workerId: new WorkerId(Uuid::v7()),
            ownerUserId: new UserId(Uuid::v7()),
            rabbitmqUser: 'worker-x',
            rabbitmqPassword: 'pw',
            occurredAt: new DateTimeImmutable('2026-11-23T12:00:00+00:00'),
        ));

        self::assertSame([], $admin->removed);
    }
}
