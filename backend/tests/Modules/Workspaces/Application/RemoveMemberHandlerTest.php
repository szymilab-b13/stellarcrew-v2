<?php

declare(strict_types=1);

namespace App\Tests\Modules\Workspaces\Application;

use App\Tests\Fixtures\Core\SpyAuditLogger;
use App\Tests\Fixtures\Workspaces\InMemoryWorkspaceRepository;
use DateTimeImmutable;
use Modules\Shared\Kernel\FrozenClock;
use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\WorkspaceId;
use Modules\Workspaces\Api\Exception\AdminMustBeDowngradedFirstException;
use Modules\Workspaces\Api\Exception\CannotRemoveOwnerException;
use Modules\Workspaces\Api\Exception\MemberNotFoundException;
use Modules\Workspaces\Application\RemoveMemberCommand;
use Modules\Workspaces\Application\RemoveMemberCommandHandler;
use Modules\Workspaces\Domain\WorkspaceMember;
use Modules\Workspaces\Domain\WorkspaceRole;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class RemoveMemberHandlerTest extends TestCase
{
    public function testShouldRemoveMemberWhenRoleIsMember(): void
    {
        $repo = new InMemoryWorkspaceRepository();
        $workspaceId = new WorkspaceId(Uuid::v7());
        $userId = new UserId(Uuid::v7());
        $repo->saveMembership(WorkspaceMember::join(
            $workspaceId,
            $userId,
            WorkspaceRole::Member,
            new DateTimeImmutable('2026-11-07T14:00:00+00:00'),
        ));
        $sut = new RemoveMemberCommandHandler(
            $repo,
            new SpyAuditLogger(),
            new FrozenClock(new DateTimeImmutable('2026-11-07T15:00:00+00:00')),
        );

        $sut(new RemoveMemberCommand($workspaceId, $userId));

        self::assertNull($repo->findMembership($workspaceId, $userId));
    }

    public function testShouldRemoveMemberWhenRoleIsGuest(): void
    {
        $repo = new InMemoryWorkspaceRepository();
        $workspaceId = new WorkspaceId(Uuid::v7());
        $userId = new UserId(Uuid::v7());
        $repo->saveMembership(WorkspaceMember::join(
            $workspaceId,
            $userId,
            WorkspaceRole::Guest,
            new DateTimeImmutable('2026-11-07T14:00:00+00:00'),
        ));
        $sut = new RemoveMemberCommandHandler(
            $repo,
            new SpyAuditLogger(),
            new FrozenClock(new DateTimeImmutable('2026-11-07T15:00:00+00:00')),
        );

        $sut(new RemoveMemberCommand($workspaceId, $userId));

        self::assertNull($repo->findMembership($workspaceId, $userId));
    }

    public function testShouldThrowAdminMustBeDowngradedFirstWhenRoleIsAdmin(): void
    {
        $repo = new InMemoryWorkspaceRepository();
        $workspaceId = new WorkspaceId(Uuid::v7());
        $userId = new UserId(Uuid::v7());
        $repo->saveMembership(WorkspaceMember::join(
            $workspaceId,
            $userId,
            WorkspaceRole::Admin,
            new DateTimeImmutable('2026-11-07T14:00:00+00:00'),
        ));
        $audit = new SpyAuditLogger();
        $sut = new RemoveMemberCommandHandler(
            $repo,
            $audit,
            new FrozenClock(new DateTimeImmutable('2026-11-07T15:00:00+00:00')),
        );

        $this->expectException(AdminMustBeDowngradedFirstException::class);
        $this->expectExceptionMessage('must be downgraded');

        try {
            $sut(new RemoveMemberCommand($workspaceId, $userId));
        } finally {
            self::assertSame([], $audit->entries);
            self::assertNotNull($repo->findMembership($workspaceId, $userId));
        }
    }

    public function testShouldThrowCannotRemoveOwnerWhenRoleIsOwner(): void
    {
        $repo = new InMemoryWorkspaceRepository();
        $workspaceId = new WorkspaceId(Uuid::v7());
        $userId = new UserId(Uuid::v7());
        $repo->saveMembership(WorkspaceMember::join(
            $workspaceId,
            $userId,
            WorkspaceRole::Owner,
            new DateTimeImmutable('2026-11-07T14:00:00+00:00'),
        ));
        $audit = new SpyAuditLogger();
        $sut = new RemoveMemberCommandHandler(
            $repo,
            $audit,
            new FrozenClock(new DateTimeImmutable('2026-11-07T15:00:00+00:00')),
        );

        $this->expectException(CannotRemoveOwnerException::class);

        try {
            $sut(new RemoveMemberCommand($workspaceId, $userId));
        } finally {
            self::assertSame([], $audit->entries);
            self::assertNotNull($repo->findMembership($workspaceId, $userId));
        }
    }

    public function testShouldThrowMemberNotFoundWhenMemberDoesNotExist(): void
    {
        $audit = new SpyAuditLogger();
        $sut = new RemoveMemberCommandHandler(
            new InMemoryWorkspaceRepository(),
            $audit,
            new FrozenClock(new DateTimeImmutable('2026-11-07T15:00:00+00:00')),
        );

        $this->expectException(MemberNotFoundException::class);

        try {
            $sut(new RemoveMemberCommand(new WorkspaceId(Uuid::v7()), new UserId(Uuid::v7())));
        } finally {
            self::assertSame([], $audit->entries);
        }
    }

    public function testShouldLogAuditEntryWithBeforeStateWhenMemberRemoved(): void
    {
        $repo = new InMemoryWorkspaceRepository();
        $workspaceId = new WorkspaceId(Uuid::v7());
        $userId = new UserId(Uuid::v7());
        $repo->saveMembership(WorkspaceMember::join(
            $workspaceId,
            $userId,
            WorkspaceRole::Member,
            new DateTimeImmutable('2026-11-07T14:00:00+00:00'),
        ));
        $audit = new SpyAuditLogger();
        $sut = new RemoveMemberCommandHandler(
            $repo,
            $audit,
            new FrozenClock(new DateTimeImmutable('2026-11-07T15:00:00+00:00')),
        );

        $sut(new RemoveMemberCommand($workspaceId, $userId));

        self::assertCount(1, $audit->entries);
        $entry = $audit->entries[0];
        self::assertSame(RemoveMemberCommandHandler::AUDIT_ACTION, $entry->action);
        self::assertSame(RemoveMemberCommandHandler::AUDIT_RESOURCE_TYPE, $entry->resourceType);
        self::assertSame((string) $workspaceId, $entry->metadata['before_state']['workspace_id']);
        self::assertSame((string) $userId, $entry->metadata['before_state']['user_id']);
        self::assertSame('member', $entry->metadata['before_state']['role']);
    }
}
