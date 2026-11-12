<?php

declare(strict_types=1);

namespace App\Tests\Modules\Organizations\Infrastructure\Audit;

use App\Tests\Fixtures\Organizations\InMemoryOrganizationRepository;
use DateTimeImmutable;
use Modules\Audit\Api\Dto\AuditEntryDto;
use Modules\Audit\Api\Exception\UndoConflictException;
use Modules\Organizations\Application\CreateOrganizationCommandHandler;
use Modules\Organizations\Application\UpdateOrganizationCommandHandler;
use Modules\Organizations\Domain\Organization;
use Modules\Organizations\Infrastructure\Audit\OrganizationsUndoer;
use Modules\Shared\Kernel\AuditEntryId;
use Modules\Shared\Kernel\Clock;
use Modules\Shared\Kernel\FrozenClock;
use Modules\Shared\Kernel\OrganizationId;
use Modules\Shared\Kernel\UserId;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class OrganizationsUndoerTest extends TestCase
{
    private const string CREATED_AT = '2026-11-10T10:00:00+00:00';

    private const string DELETED_AT = '2026-11-10T11:00:00+00:00';

    private const string UNDO_AT = '2026-11-10T12:00:00+00:00';

    public function testShouldSupportCreatedAndUpdatedActions(): void
    {
        $sut = $this->makeSut();

        self::assertTrue($sut->supports(CreateOrganizationCommandHandler::AUDIT_ACTION));
        self::assertTrue($sut->supports(UpdateOrganizationCommandHandler::AUDIT_ACTION));
        self::assertFalse($sut->supports('organizations.organization.deleted'));
        self::assertFalse($sut->supports('other.action'));
    }

    public function testShouldSoftDeleteOrganizationWhenUndoingCreated(): void
    {
        $repo = new InMemoryOrganizationRepository();
        $organization = $this->seedActiveOrganization($repo);
        $sut = $this->makeSut($repo);

        $sut->undo(
            $this->entryFor(CreateOrganizationCommandHandler::AUDIT_ACTION, $organization),
            new UserId(Uuid::v7()),
        );

        $after = $repo->findById($organization->id());
        self::assertInstanceOf(Organization::class, $after);
        self::assertTrue($after->isDeleted());
    }

    public function testShouldThrowAlreadyDeletedWhenUndoingCreatedOfDeletedOrganization(): void
    {
        $repo = new InMemoryOrganizationRepository();
        $organization = $this->seedActiveOrganization($repo);
        $organization->softDelete(new DateTimeImmutable(self::DELETED_AT));

        $repo->save($organization);
        $sut = $this->makeSut($repo);

        $this->expectException(UndoConflictException::class);
        $sut->undo(
            $this->entryFor(CreateOrganizationCommandHandler::AUDIT_ACTION, $organization),
            new UserId(Uuid::v7()),
        );
    }

    public function testShouldRestoreBeforeStateWhenUndoingUpdated(): void
    {
        $repo = new InMemoryOrganizationRepository();
        $organization = $this->seedActiveOrganization($repo, name: 'Renamed', slug: 'renamed');
        $sut = $this->makeSut($repo);

        $entry = $this->updatedEntry($organization, before: [
            'name' => 'Acme',
            'slug' => 'acme',
        ]);

        $sut->undo($entry, new UserId(Uuid::v7()));

        $after = $repo->findById($organization->id());
        self::assertInstanceOf(Organization::class, $after);
        self::assertSame('Acme', $after->name());
        self::assertSame('acme', $after->slug());
    }

    public function testShouldThrowMissingStateWhenUndoingUpdatedWithoutBeforeState(): void
    {
        $repo = new InMemoryOrganizationRepository();
        $organization = $this->seedActiveOrganization($repo);
        $sut = $this->makeSut($repo);

        $entry = $this->updatedEntry($organization, before: null);

        $this->expectException(UndoConflictException::class);
        $sut->undo($entry, new UserId(Uuid::v7()));
    }

    public function testShouldThrowMissingNameWhenUndoingUpdatedWithoutNameInBeforeState(): void
    {
        $repo = new InMemoryOrganizationRepository();
        $organization = $this->seedActiveOrganization($repo);
        $sut = $this->makeSut($repo);

        $entry = $this->updatedEntry($organization, before: ['slug' => 'acme']);

        $this->expectException(UndoConflictException::class);
        $sut->undo($entry, new UserId(Uuid::v7()));
    }

    public function testShouldThrowMissingSlugWhenUndoingUpdatedWithoutSlugInBeforeState(): void
    {
        $repo = new InMemoryOrganizationRepository();
        $organization = $this->seedActiveOrganization($repo);
        $sut = $this->makeSut($repo);

        $entry = $this->updatedEntry($organization, before: ['name' => 'Acme']);

        $this->expectException(UndoConflictException::class);
        $sut->undo($entry, new UserId(Uuid::v7()));
    }

    public function testShouldThrowOrganizationDeletedWhenUndoingUpdatedOfSoftDeletedOrganization(): void
    {
        $repo = new InMemoryOrganizationRepository();
        $organization = $this->seedActiveOrganization($repo);
        $organization->softDelete(new DateTimeImmutable(self::DELETED_AT));

        $repo->save($organization);
        $sut = $this->makeSut($repo);

        $entry = $this->updatedEntry($organization, before: [
            'name' => 'Acme',
            'slug' => 'acme',
        ]);

        $this->expectException(UndoConflictException::class);
        $sut->undo($entry, new UserId(Uuid::v7()));
    }

    public function testShouldThrowOrganizationNotFoundWhenEntryResourceMissing(): void
    {
        $repo = new InMemoryOrganizationRepository();
        $sut = $this->makeSut($repo);

        $entry = new AuditEntryDto(
            id: new AuditEntryId(Uuid::v7()),
            actorUserId: new UserId(Uuid::v7()),
            actorEmail: null,
            action: CreateOrganizationCommandHandler::AUDIT_ACTION,
            resourceType: CreateOrganizationCommandHandler::AUDIT_RESOURCE_TYPE,
            resourceId: (string) OrganizationId::generate(),
            beforeState: null,
            afterState: null,
            metadata: [],
            occurredAt: new DateTimeImmutable(self::CREATED_AT),
            undoneAt: null,
            undoneBy: null,
            undoneByEmail: null,
            revertsEntryId: null,
        );

        $this->expectException(UndoConflictException::class);
        $sut->undo($entry, new UserId(Uuid::v7()));
    }

    private function makeSut(?InMemoryOrganizationRepository $repo = null, ?Clock $clock = null): OrganizationsUndoer
    {
        return new OrganizationsUndoer(
            $repo ?? new InMemoryOrganizationRepository(),
            $clock ?? new FrozenClock(new DateTimeImmutable(self::UNDO_AT)),
        );
    }

    private function seedActiveOrganization(
        InMemoryOrganizationRepository $repo,
        string $name = 'Acme',
        string $slug = 'acme',
    ): Organization {
        $organization = Organization::create(
            OrganizationId::generate(),
            $name,
            $slug,
            new UserId(Uuid::v7()),
            new DateTimeImmutable(self::CREATED_AT),
        );
        $repo->save($organization);

        return $organization;
    }

    /**
     * @param array<string, mixed>|null $before
     */
    private function updatedEntry(Organization $organization, ?array $before): AuditEntryDto
    {
        return new AuditEntryDto(
            id: new AuditEntryId(Uuid::v7()),
            actorUserId: new UserId(Uuid::v7()),
            actorEmail: null,
            action: UpdateOrganizationCommandHandler::AUDIT_ACTION,
            resourceType: UpdateOrganizationCommandHandler::AUDIT_RESOURCE_TYPE,
            resourceId: (string) $organization->id(),
            beforeState: $before,
            afterState: null,
            metadata: [],
            occurredAt: new DateTimeImmutable(self::CREATED_AT),
            undoneAt: null,
            undoneBy: null,
            undoneByEmail: null,
            revertsEntryId: null,
        );
    }

    private function entryFor(string $action, Organization $organization): AuditEntryDto
    {
        return new AuditEntryDto(
            id: new AuditEntryId(Uuid::v7()),
            actorUserId: new UserId(Uuid::v7()),
            actorEmail: null,
            action: $action,
            resourceType: 'organization',
            resourceId: (string) $organization->id(),
            beforeState: null,
            afterState: null,
            metadata: [],
            occurredAt: new DateTimeImmutable(self::CREATED_AT),
            undoneAt: null,
            undoneBy: null,
            undoneByEmail: null,
            revertsEntryId: null,
        );
    }
}
