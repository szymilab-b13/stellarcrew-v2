<?php

declare(strict_types=1);

namespace App\Tests\Modules\Workspaces\Domain;

use DateTimeImmutable;
use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\WorkspaceId;
use Modules\Workspaces\Domain\WorkspaceMember;
use Modules\Workspaces\Domain\WorkspaceRole;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class WorkspaceMemberTest extends TestCase
{
    public function testShouldExposeFieldsWhenJoined(): void
    {
        $wsId = WorkspaceId::generate();
        $userId = new UserId(Uuid::v7());
        $joinedAt = new DateTimeImmutable('2026-11-06T10:00:00+00:00');

        $sut = WorkspaceMember::join($wsId, $userId, WorkspaceRole::Owner, $joinedAt);

        self::assertSame($wsId, $sut->workspaceId());
        self::assertSame($userId, $sut->userId());
        self::assertSame(WorkspaceRole::Owner, $sut->role());
        self::assertNull($sut->invitedBy());
        self::assertEquals($joinedAt, $sut->joinedAt());
    }

    public function testShouldChangeRoleWhenAsked(): void
    {
        $sut = WorkspaceMember::join(
            WorkspaceId::generate(),
            new UserId(Uuid::v7()),
            WorkspaceRole::Member,
            new DateTimeImmutable('2026-11-06T10:00:00+00:00'),
        );

        $sut->changeRole(WorkspaceRole::Admin);

        self::assertSame(WorkspaceRole::Admin, $sut->role());
    }
}
