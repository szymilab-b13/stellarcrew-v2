<?php

declare(strict_types=1);

namespace App\Tests\Modules\Organizations\Infrastructure\Audit;

use DateTimeImmutable;
use Modules\Audit\Api\Dto\AuditEntryDto;
use Modules\Organizations\Application\CreateOrganizationCommandHandler;
use Modules\Organizations\Application\UpdateOrganizationCommandHandler;
use Modules\Organizations\Infrastructure\Audit\OrganizationsUndoDescriber;
use Modules\Shared\Kernel\AuditEntryId;
use Modules\Shared\Kernel\OrganizationId;
use Modules\Shared\Kernel\UserId;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class OrganizationsUndoDescriberTest extends TestCase
{
    public function testShouldSupportCreatedAndUpdatedActions(): void
    {
        $sut = new OrganizationsUndoDescriber();

        self::assertTrue($sut->supports(CreateOrganizationCommandHandler::AUDIT_ACTION));
        self::assertTrue($sut->supports(UpdateOrganizationCommandHandler::AUDIT_ACTION));
        self::assertFalse($sut->supports('organizations.organization.deleted'));
        self::assertFalse($sut->supports('unrelated.action'));
    }

    public function testShouldDescribeOrganizationCreatedUndoAsSoftDelete(): void
    {
        $sut = new OrganizationsUndoDescriber();

        $description = $sut->describe($this->entry(CreateOrganizationCommandHandler::AUDIT_ACTION));

        self::assertSame('Usuń utworzoną organizację (soft delete).', $description);
    }

    public function testShouldDescribeOrganizationUpdatedUndoAsRestorePreviousAttributes(): void
    {
        $sut = new OrganizationsUndoDescriber();

        $description = $sut->describe($this->entry(UpdateOrganizationCommandHandler::AUDIT_ACTION));

        self::assertSame('Przywróć poprzednią nazwę i slug organizacji.', $description);
    }

    private function entry(string $action): AuditEntryDto
    {
        return new AuditEntryDto(
            id: new AuditEntryId(Uuid::v7()),
            actorUserId: new UserId(Uuid::v7()),
            actorEmail: null,
            action: $action,
            resourceType: 'organization',
            resourceId: (string) OrganizationId::generate(),
            beforeState: null,
            afterState: null,
            metadata: [],
            occurredAt: new DateTimeImmutable('2026-11-10T10:00:00+00:00'),
            undoneAt: null,
            undoneBy: null,
            undoneByEmail: null,
            revertsEntryId: null,
        );
    }
}
