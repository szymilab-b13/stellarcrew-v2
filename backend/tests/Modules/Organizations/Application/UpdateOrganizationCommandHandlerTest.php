<?php

declare(strict_types=1);

namespace App\Tests\Modules\Organizations\Application;

use App\Tests\Fixtures\Organizations\InMemoryOrganizationRepository;
use DateTimeImmutable;
use Modules\Organizations\Application\UpdateOrganizationCommand;
use Modules\Organizations\Application\UpdateOrganizationCommandHandler;
use Modules\Organizations\Domain\Organization;
use Modules\Shared\Kernel\FrozenClock;
use Modules\Shared\Kernel\OrganizationId;
use Modules\Shared\Kernel\UserId;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Uid\Uuid;

final class UpdateOrganizationCommandHandlerTest extends TestCase
{
    private const string CREATED_AT = '2026-11-10T14:00:00+00:00';

    private const string UPDATED_AT = '2026-11-10T15:00:00+00:00';

    public function testShouldRenameOrganizationWhenOnlyNameProvided(): void
    {
        $ownerId = new UserId(Uuid::v7());
        $organization = $this->seedOrganization($ownerId, 'Acme', 'acme');
        $repo = new InMemoryOrganizationRepository();
        $repo->save($organization);

        $sut = $this->handler($repo);

        $result = $sut(new UpdateOrganizationCommand($ownerId, $organization->id(), name: 'Renamed'));

        self::assertSame('Renamed', $result->name());
        self::assertSame('acme', $result->slug());
        self::assertEquals(new DateTimeImmutable(self::UPDATED_AT), $result->updatedAt());
    }

    public function testShouldChangeSlugWhenDifferentFromCurrent(): void
    {
        $ownerId = new UserId(Uuid::v7());
        $organization = $this->seedOrganization($ownerId, 'Acme', 'acme');
        $repo = new InMemoryOrganizationRepository();
        $repo->save($organization);

        $sut = $this->handler($repo);

        $result = $sut(new UpdateOrganizationCommand($ownerId, $organization->id(), slug: 'acme-inc'));

        self::assertSame('acme-inc', $result->slug());
        self::assertEquals(new DateTimeImmutable(self::UPDATED_AT), $result->updatedAt());
    }

    public function testShouldLeaveSlugUnchangedWhenSameSlugProvided(): void
    {
        $ownerId = new UserId(Uuid::v7());
        $organization = $this->seedOrganization($ownerId, 'Acme', 'acme');
        $repo = new InMemoryOrganizationRepository();
        $repo->save($organization);

        $sut = $this->handler($repo);

        $result = $sut(new UpdateOrganizationCommand($ownerId, $organization->id(), slug: 'acme'));

        self::assertSame('acme', $result->slug());
        self::assertEquals(new DateTimeImmutable(self::CREATED_AT), $result->updatedAt());
    }

    public function testShouldAppendRandomSuffixWhenNewSlugTakenByAnotherOrganization(): void
    {
        $ownerA = new UserId(Uuid::v7());
        $ownerB = new UserId(Uuid::v7());
        $organizationA = $this->seedOrganization($ownerA, 'Acme', 'acme');
        $organizationB = $this->seedOrganization($ownerB, 'Beta', 'beta');
        $repo = new InMemoryOrganizationRepository();
        $repo->save($organizationA);
        $repo->save($organizationB);

        $sut = $this->handler($repo);

        $result = $sut(new UpdateOrganizationCommand($ownerA, $organizationA->id(), slug: 'beta'));

        self::assertNotSame('beta', $result->slug());
        self::assertNotSame('acme', $result->slug());
        self::assertStringStartsWith('beta-', $result->slug());
    }

    public function testShouldUpdateBothFieldsAtOnce(): void
    {
        $ownerId = new UserId(Uuid::v7());
        $organization = $this->seedOrganization($ownerId, 'Acme', 'acme');
        $repo = new InMemoryOrganizationRepository();
        $repo->save($organization);

        $sut = $this->handler($repo);

        $result = $sut(new UpdateOrganizationCommand(
            actorId: $ownerId,
            organizationId: $organization->id(),
            name: 'Renamed',
            slug: 'renamed-slug',
        ));

        self::assertSame('Renamed', $result->name());
        self::assertSame('renamed-slug', $result->slug());
    }

    public function testShouldLeaveOrganizationUnchangedWhenAllFieldsNull(): void
    {
        $ownerId = new UserId(Uuid::v7());
        $organization = $this->seedOrganization($ownerId, 'Acme', 'acme');
        $repo = new InMemoryOrganizationRepository();
        $repo->save($organization);

        $sut = $this->handler($repo);

        $result = $sut(new UpdateOrganizationCommand($ownerId, $organization->id()));

        self::assertSame('Acme', $result->name());
        self::assertSame('acme', $result->slug());
        self::assertEquals(new DateTimeImmutable(self::CREATED_AT), $result->updatedAt());
    }

    public function testShouldThrowNotFoundWhenOrganizationMissing(): void
    {
        $sut = $this->handler(new InMemoryOrganizationRepository());

        $this->expectException(NotFoundHttpException::class);

        $sut(new UpdateOrganizationCommand(new UserId(Uuid::v7()), OrganizationId::generate(), name: 'X'));
    }

    public function testShouldThrowNotFoundWhenOrganizationSoftDeleted(): void
    {
        $ownerId = new UserId(Uuid::v7());
        $organization = $this->seedOrganization($ownerId, 'Acme', 'acme');
        $organization->softDelete(new DateTimeImmutable(self::UPDATED_AT));

        $repo = new InMemoryOrganizationRepository();
        $repo->save($organization);

        $sut = $this->handler($repo);

        $this->expectException(NotFoundHttpException::class);

        $sut(new UpdateOrganizationCommand($ownerId, $organization->id(), name: 'X'));
    }

    public function testShouldThrowAccessDeniedWhenActorIsNotOwner(): void
    {
        $ownerId = new UserId(Uuid::v7());
        $otherId = new UserId(Uuid::v7());
        $organization = $this->seedOrganization($ownerId, 'Acme', 'acme');
        $repo = new InMemoryOrganizationRepository();
        $repo->save($organization);

        $sut = $this->handler($repo);

        $this->expectException(AccessDeniedException::class);

        $sut(new UpdateOrganizationCommand($otherId, $organization->id(), name: 'X'));
    }

    public function testShouldStampUpdatedAtFromClock(): void
    {
        $ownerId = new UserId(Uuid::v7());
        $organization = $this->seedOrganization($ownerId, 'Acme', 'acme');
        $repo = new InMemoryOrganizationRepository();
        $repo->save($organization);

        $sut = $this->handler($repo);

        $result = $sut(new UpdateOrganizationCommand($ownerId, $organization->id(), name: 'Renamed'));

        self::assertEquals(new DateTimeImmutable(self::UPDATED_AT), $result->updatedAt());
        self::assertEquals(new DateTimeImmutable(self::CREATED_AT), $result->createdAt());
    }

    private function handler(InMemoryOrganizationRepository $repo): UpdateOrganizationCommandHandler
    {
        return new UpdateOrganizationCommandHandler(
            $repo,
            new FrozenClock(new DateTimeImmutable(self::UPDATED_AT)),
        );
    }

    private function seedOrganization(UserId $ownerId, string $name, string $slug): Organization
    {
        return Organization::create(
            OrganizationId::generate(),
            $name,
            $slug,
            $ownerId,
            new DateTimeImmutable(self::CREATED_AT),
        );
    }
}
