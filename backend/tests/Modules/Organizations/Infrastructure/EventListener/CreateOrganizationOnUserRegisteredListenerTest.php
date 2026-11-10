<?php

declare(strict_types=1);

namespace App\Tests\Modules\Organizations\Infrastructure\EventListener;

use App\Tests\Fixtures\Organizations\InMemoryOrganizationRepository;
use DateTimeImmutable;
use Modules\Organizations\Application\CreateOrganizationCommandHandler;
use Modules\Organizations\Infrastructure\EventListener\CreateOrganizationOnUserRegisteredListener;
use Modules\Shared\Kernel\FrozenClock;
use Modules\Shared\Kernel\UserId;
use Modules\UserManagement\Api\Event\UserRegistered;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class CreateOrganizationOnUserRegisteredListenerTest extends TestCase
{
    public function testShouldCreateOrganizationNamedAfterDisplayNameWhenUserHasNoOrg(): void
    {
        $repo = new InMemoryOrganizationRepository();
        $handler = new CreateOrganizationCommandHandler(
            $repo,
            new FrozenClock(new DateTimeImmutable('2026-11-10T14:00:00+00:00')),
        );
        $sut = new CreateOrganizationOnUserRegisteredListener($repo, $handler);
        $userId = new UserId(Uuid::v7());

        $sut(new UserRegistered(
            userId: $userId,
            email: 'alice@example.com',
            displayName: 'Alice Cooper',
            occurredAt: new DateTimeImmutable('2026-11-10T14:00:00+00:00'),
        ));

        self::assertSame(1, $repo->count());
        $organization = $repo->findByOwner($userId);
        self::assertNotNull($organization);
        self::assertSame("Alice Cooper's Organization", $organization->name());
    }

    public function testShouldFallBackToEmailLocalPartWhenDisplayNameMissing(): void
    {
        $repo = new InMemoryOrganizationRepository();
        $handler = new CreateOrganizationCommandHandler(
            $repo,
            new FrozenClock(new DateTimeImmutable('2026-11-10T14:00:00+00:00')),
        );
        $sut = new CreateOrganizationOnUserRegisteredListener($repo, $handler);
        $userId = new UserId(Uuid::v7());

        $sut(new UserRegistered(
            userId: $userId,
            email: 'bob@example.com',
            displayName: null,
            occurredAt: new DateTimeImmutable('2026-11-10T14:00:00+00:00'),
        ));

        $organization = $repo->findByOwner($userId);
        self::assertNotNull($organization);
        self::assertSame("bob's Organization", $organization->name());
    }

    public function testShouldFallBackToEmailLocalPartWhenDisplayNameIsBlank(): void
    {
        $repo = new InMemoryOrganizationRepository();
        $handler = new CreateOrganizationCommandHandler(
            $repo,
            new FrozenClock(new DateTimeImmutable('2026-11-10T14:00:00+00:00')),
        );
        $sut = new CreateOrganizationOnUserRegisteredListener($repo, $handler);
        $userId = new UserId(Uuid::v7());

        $sut(new UserRegistered(
            userId: $userId,
            email: 'carol@example.com',
            displayName: '   ',
            occurredAt: new DateTimeImmutable('2026-11-10T14:00:00+00:00'),
        ));

        $organization = $repo->findByOwner($userId);
        self::assertNotNull($organization);
        self::assertSame("carol's Organization", $organization->name());
    }

    public function testShouldBeIdempotentAndSkipWhenUserAlreadyHasOrganization(): void
    {
        $repo = new InMemoryOrganizationRepository();
        $handler = new CreateOrganizationCommandHandler(
            $repo,
            new FrozenClock(new DateTimeImmutable('2026-11-10T14:00:00+00:00')),
        );
        $sut = new CreateOrganizationOnUserRegisteredListener($repo, $handler);
        $userId = new UserId(Uuid::v7());
        $sut(new UserRegistered(
            userId: $userId,
            email: 'dana@example.com',
            displayName: 'Dana',
            occurredAt: new DateTimeImmutable('2026-11-10T14:00:00+00:00'),
        ));

        $sut(new UserRegistered(
            userId: $userId,
            email: 'dana@example.com',
            displayName: 'Dana',
            occurredAt: new DateTimeImmutable('2026-11-10T14:01:00+00:00'),
        ));

        self::assertSame(1, $repo->count());
    }
}
