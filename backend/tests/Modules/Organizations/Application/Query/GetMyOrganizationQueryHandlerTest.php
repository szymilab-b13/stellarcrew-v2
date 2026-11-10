<?php

declare(strict_types=1);

namespace App\Tests\Modules\Organizations\Application\Query;

use App\Tests\Fixtures\Organizations\InMemoryOrganizationRepository;
use DateTimeImmutable;
use Modules\Organizations\Application\Query\GetMyOrganizationQuery;
use Modules\Organizations\Application\Query\GetMyOrganizationQueryHandler;
use Modules\Organizations\Domain\Organization;
use Modules\Shared\Kernel\OrganizationId;
use Modules\Shared\Kernel\UserId;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Uuid;

final class GetMyOrganizationQueryHandlerTest extends TestCase
{
    private const string CREATED_AT = '2026-11-10T14:00:00+00:00';

    public function testShouldReturnOrganizationWhenCurrentUserIsOwner(): void
    {
        $ownerId = new UserId(Uuid::v7());
        $organization = $this->seedOrganization($ownerId, 'Acme', 'acme');
        $repo = new InMemoryOrganizationRepository();
        $repo->save($organization);

        $sut = new GetMyOrganizationQueryHandler($repo);

        $result = $sut(new GetMyOrganizationQuery($ownerId));

        self::assertSame($organization, $result);
        self::assertSame('Acme', $result->name());
        self::assertSame('acme', $result->slug());
    }

    public function testShouldThrowNotFoundWhenUserDoesNotOwnAnyOrganization(): void
    {
        $sut = new GetMyOrganizationQueryHandler(new InMemoryOrganizationRepository());

        $this->expectException(NotFoundHttpException::class);

        $sut(new GetMyOrganizationQuery(new UserId(Uuid::v7())));
    }

    public function testShouldThrowNotFoundWhenOnlyOrganizationForOwnerIsSoftDeleted(): void
    {
        $ownerId = new UserId(Uuid::v7());
        $organization = $this->seedOrganization($ownerId, 'Acme', 'acme');
        $organization->softDelete(new DateTimeImmutable('2026-11-10T15:00:00+00:00'));

        $repo = new InMemoryOrganizationRepository();
        $repo->save($organization);

        $sut = new GetMyOrganizationQueryHandler($repo);

        $this->expectException(NotFoundHttpException::class);

        $sut(new GetMyOrganizationQuery($ownerId));
    }

    public function testShouldIgnoreOrganizationsOwnedByOtherUsers(): void
    {
        $ownerId = new UserId(Uuid::v7());
        $otherId = new UserId(Uuid::v7());
        $repo = new InMemoryOrganizationRepository();
        $repo->save($this->seedOrganization($otherId, 'Beta', 'beta'));

        $sut = new GetMyOrganizationQueryHandler($repo);

        $this->expectException(NotFoundHttpException::class);

        $sut(new GetMyOrganizationQuery($ownerId));
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
