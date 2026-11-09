<?php

declare(strict_types=1);

namespace App\Tests\Modules\Projects\Infrastructure\Audit;

use DateTimeImmutable;
use Modules\Audit\Api\Dto\AuditEntryDto;
use Modules\Projects\Application\CreateProjectCommandHandler;
use Modules\Projects\Application\DeleteProjectCommandHandler;
use Modules\Projects\Application\RestoreProjectCommandHandler;
use Modules\Projects\Application\UpdateProjectCommandHandler;
use Modules\Projects\Infrastructure\Audit\ProjectsUndoDescriber;
use Modules\Shared\Kernel\AuditEntryId;
use Modules\Shared\Kernel\ProjectId;
use Modules\Shared\Kernel\UserId;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class ProjectsUndoDescriberTest extends TestCase
{
    public function testShouldSupportAllFourProjectLifecycleActions(): void
    {
        $sut = new ProjectsUndoDescriber();

        self::assertTrue($sut->supports(CreateProjectCommandHandler::AUDIT_ACTION));
        self::assertTrue($sut->supports(UpdateProjectCommandHandler::AUDIT_ACTION));
        self::assertTrue($sut->supports(DeleteProjectCommandHandler::AUDIT_ACTION));
        self::assertTrue($sut->supports(RestoreProjectCommandHandler::AUDIT_ACTION));
        self::assertFalse($sut->supports('unrelated.action'));
    }

    public function testShouldDescribeProjectCreatedUndoAsSoftDelete(): void
    {
        $sut = new ProjectsUndoDescriber();

        $description = $sut->describe($this->entry(CreateProjectCommandHandler::AUDIT_ACTION));

        self::assertSame('Usuń utworzony projekt (soft delete).', $description);
    }

    public function testShouldDescribeProjectUpdatedUndoAsRestorePreviousAttributes(): void
    {
        $sut = new ProjectsUndoDescriber();

        $description = $sut->describe($this->entry(UpdateProjectCommandHandler::AUDIT_ACTION));

        self::assertSame('Przywróć poprzednią nazwę, slug i opis projektu.', $description);
    }

    public function testShouldDescribeProjectDeletedUndoAsRestore(): void
    {
        $sut = new ProjectsUndoDescriber();

        $description = $sut->describe($this->entry(DeleteProjectCommandHandler::AUDIT_ACTION));

        self::assertSame('Przywróć usunięty projekt.', $description);
    }

    public function testShouldDescribeProjectRestoredUndoAsSoftDeleteAgain(): void
    {
        $sut = new ProjectsUndoDescriber();

        $description = $sut->describe($this->entry(RestoreProjectCommandHandler::AUDIT_ACTION));

        self::assertSame('Ponownie usuń przywrócony projekt (soft delete).', $description);
    }

    private function entry(string $action): AuditEntryDto
    {
        return new AuditEntryDto(
            id: new AuditEntryId(Uuid::v7()),
            actorUserId: new UserId(Uuid::v7()),
            actorEmail: null,
            action: $action,
            resourceType: 'project',
            resourceId: (string) ProjectId::generate(),
            beforeState: null,
            afterState: null,
            metadata: [],
            occurredAt: new DateTimeImmutable('2026-11-09T10:00:00+00:00'),
            undoneAt: null,
            undoneBy: null,
            undoneByEmail: null,
            revertsEntryId: null,
        );
    }
}
