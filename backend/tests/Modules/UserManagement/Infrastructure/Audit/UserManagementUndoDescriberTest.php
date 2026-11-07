<?php

declare(strict_types=1);

namespace App\Tests\Modules\UserManagement\Infrastructure\Audit;

use DateTimeImmutable;
use Modules\Audit\Api\Dto\AuditEntryDto;
use Modules\Shared\Kernel\AuditEntryId;
use Modules\Shared\Kernel\UserId;
use Modules\UserManagement\Application\CreateUserCommandHandler;
use Modules\UserManagement\Application\DeactivateUserCommandHandler;
use Modules\UserManagement\Application\ReactivateUserCommandHandler;
use Modules\UserManagement\Infrastructure\Audit\UserManagementUndoDescriber;
use Modules\Workspaces\Application\AssignMemberCommandHandler;
use Modules\Workspaces\Application\ChangeMemberRoleCommandHandler;
use Modules\Workspaces\Application\RemoveMemberCommandHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class UserManagementUndoDescriberTest extends TestCase
{
    public function testShouldSupportAllSixUserManagementUndoableActions(): void
    {
        $sut = new UserManagementUndoDescriber();

        self::assertTrue($sut->supports(CreateUserCommandHandler::AUDIT_ACTION));
        self::assertTrue($sut->supports(DeactivateUserCommandHandler::AUDIT_ACTION));
        self::assertTrue($sut->supports(ReactivateUserCommandHandler::AUDIT_ACTION));
        self::assertTrue($sut->supports(AssignMemberCommandHandler::AUDIT_ACTION));
        self::assertTrue($sut->supports(RemoveMemberCommandHandler::AUDIT_ACTION));
        self::assertTrue($sut->supports(ChangeMemberRoleCommandHandler::AUDIT_ACTION));
        self::assertFalse($sut->supports('user_management.user.password_reset'));
        self::assertFalse($sut->supports('unrelated.action'));
    }

    public function testShouldDescribeUserCreatedUndoAsDeactivateAndClearMemberships(): void
    {
        $sut = new UserManagementUndoDescriber();

        $description = $sut->describe($this->entry(CreateUserCommandHandler::AUDIT_ACTION));

        self::assertSame(
            "Dezaktywuj utworzonego użytkownika i usuń go ze wszystkich workspace'ów.",
            $description,
        );
    }

    public function testShouldDescribeUserDeactivatedUndoAsReactivation(): void
    {
        $sut = new UserManagementUndoDescriber();

        self::assertSame(
            'Reaktywuj użytkownika.',
            $sut->describe($this->entry(DeactivateUserCommandHandler::AUDIT_ACTION)),
        );
    }

    public function testShouldDescribeUserReactivatedUndoAsDeactivation(): void
    {
        $sut = new UserManagementUndoDescriber();

        self::assertSame(
            'Dezaktywuj użytkownika ponownie.',
            $sut->describe($this->entry(ReactivateUserCommandHandler::AUDIT_ACTION)),
        );
    }

    public function testShouldDescribeMemberAssignedUndoAsRemoval(): void
    {
        $sut = new UserManagementUndoDescriber();

        self::assertSame(
            "Usuń użytkownika z workspace'a.",
            $sut->describe($this->entry(AssignMemberCommandHandler::AUDIT_ACTION)),
        );
    }

    public function testShouldDescribeMemberRemovedUndoAsRestoration(): void
    {
        $sut = new UserManagementUndoDescriber();

        self::assertSame(
            'Przywróć członkostwo użytkownika w workspace.',
            $sut->describe($this->entry(RemoveMemberCommandHandler::AUDIT_ACTION)),
        );
    }

    public function testShouldDescribeRoleChangedUndoAsPreviousRoleRestoration(): void
    {
        $sut = new UserManagementUndoDescriber();

        self::assertSame(
            'Przywróć poprzednią rolę użytkownika w workspace.',
            $sut->describe($this->entry(ChangeMemberRoleCommandHandler::AUDIT_ACTION)),
        );
    }

    private function entry(string $action): AuditEntryDto
    {
        return new AuditEntryDto(
            id: AuditEntryId::generate(),
            actorUserId: new UserId(Uuid::v7()),
            actorEmail: null,
            action: $action,
            resourceType: null,
            resourceId: null,
            beforeState: null,
            afterState: null,
            metadata: [],
            occurredAt: new DateTimeImmutable('2026-11-07T15:00:00+00:00'),
            undoneAt: null,
            undoneBy: null,
            undoneByEmail: null,
            revertsEntryId: null,
        );
    }
}
