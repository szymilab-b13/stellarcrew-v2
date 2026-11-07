<?php

declare(strict_types=1);

namespace App\Tests\Modules\Workspaces\Domain;

use Modules\Workspaces\Domain\WorkspaceRole;
use PHPUnit\Framework\TestCase;

final class WorkspaceRoleTest extends TestCase
{
    public function testShouldAllowOwnerFullControl(): void
    {
        $sut = WorkspaceRole::Owner;

        self::assertTrue($sut->canEditWorkspace());
        self::assertTrue($sut->canDeleteWorkspace());
        self::assertTrue($sut->canManageMembers());
        self::assertTrue($sut->canChangeRoles());
        self::assertTrue($sut->canCreateProjects());
    }

    public function testShouldDenyAdminFromDeletingAndChangingRoles(): void
    {
        $sut = WorkspaceRole::Admin;

        self::assertTrue($sut->canEditWorkspace());
        self::assertFalse($sut->canDeleteWorkspace());
        self::assertTrue($sut->canManageMembers());
        self::assertFalse($sut->canChangeRoles());
        self::assertTrue($sut->canCreateProjects());
    }

    public function testShouldAllowMemberToCreateProjectsButNotManageMembers(): void
    {
        $sut = WorkspaceRole::Member;

        self::assertFalse($sut->canEditWorkspace());
        self::assertFalse($sut->canManageMembers());
        self::assertTrue($sut->canCreateProjects());
    }

    public function testShouldRestrictGuestToReadingMessages(): void
    {
        $sut = WorkspaceRole::Guest;

        self::assertFalse($sut->canCreateProjects());
        self::assertFalse($sut->canManageMembers());
        self::assertTrue($sut->canReadMessages());
    }
}
