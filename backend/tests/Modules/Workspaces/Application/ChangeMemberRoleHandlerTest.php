<?php

declare(strict_types=1);

namespace App\Tests\Modules\Workspaces\Application;

use App\Tests\Fixtures\Core\SpyAuditLogger;
use App\Tests\Fixtures\Workspaces\InMemoryWorkspaceRepository;
use DateTimeImmutable;
use Modules\Shared\Kernel\FrozenClock;
use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\WorkspaceId;
use Modules\Workspaces\Api\Exception\InvalidMemberRoleException;
use Modules\Workspaces\Api\Exception\MemberNotFoundException;
use Modules\Workspaces\Application\ChangeMemberRoleCommand;
use Modules\Workspaces\Application\ChangeMemberRoleCommandHandler;
use Modules\Workspaces\Domain\WorkspaceMember;
use Modules\Workspaces\Domain\WorkspaceRole;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class ChangeMemberRoleHandlerTest extends TestCase
{
    public function testShouldUpdateMemberRoleWhenCommandExecuted(): void
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
        $sut = new ChangeMemberRoleCommandHandler(
            $repo,
            new SpyAuditLogger(),
            new FrozenClock(new DateTimeImmutable('2026-11-07T15:00:00+00:00')),
        );

        $updated = $sut(new ChangeMemberRoleCommand($workspaceId, $userId, WorkspaceRole::Admin));

        self::assertSame(WorkspaceRole::Admin, $updated->role());
        self::assertSame(WorkspaceRole::Admin, $repo->findMembership($workspaceId, $userId)?->role());
    }

    public function testShouldThrowInvalidMemberRoleWhenPromotingToOwner(): void
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
        $sut = new ChangeMemberRoleCommandHandler(
            $repo,
            $audit,
            new FrozenClock(new DateTimeImmutable('2026-11-07T15:00:00+00:00')),
        );

        $this->expectException(InvalidMemberRoleException::class);

        try {
            $sut(new ChangeMemberRoleCommand($workspaceId, $userId, WorkspaceRole::Owner));
        } finally {
            self::assertSame([], $audit->entries);
            self::assertSame(WorkspaceRole::Admin, $repo->findMembership($workspaceId, $userId)?->role());
        }
    }

    public function testShouldThrowMemberNotFoundWhenMemberDoesNotExist(): void
    {
        $audit = new SpyAuditLogger();
        $sut = new ChangeMemberRoleCommandHandler(
            new InMemoryWorkspaceRepository(),
            $audit,
            new FrozenClock(new DateTimeImmutable('2026-11-07T15:00:00+00:00')),
        );

        $this->expectException(MemberNotFoundException::class);

        try {
            $sut(new ChangeMemberRoleCommand(
                new WorkspaceId(Uuid::v7()),
                new UserId(Uuid::v7()),
                WorkspaceRole::Admin,
            ));
        } finally {
            self::assertSame([], $audit->entries);
        }
    }

    public function testShouldLogAuditEntryWithBeforeAndAfterStateWhenRoleChanged(): void
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
        $audit = new SpyAuditLogger();
        $sut = new ChangeMemberRoleCommandHandler(
            $repo,
            $audit,
            new FrozenClock(new DateTimeImmutable('2026-11-07T15:00:00+00:00')),
        );

        $sut(new ChangeMemberRoleCommand($workspaceId, $userId, WorkspaceRole::Member));

        self::assertCount(1, $audit->entries);
        $entry = $audit->entries[0];
        self::assertSame(ChangeMemberRoleCommandHandler::AUDIT_ACTION, $entry->action);
        self::assertSame(ChangeMemberRoleCommandHandler::AUDIT_RESOURCE_TYPE, $entry->resourceType);
        self::assertSame('guest', $entry->metadata['before_state']['role']);
        self::assertSame('member', $entry->metadata['after_state']['role']);
        self::assertSame((string) $workspaceId, $entry->metadata['workspace_id']);
        self::assertSame((string) $userId, $entry->metadata['user_id']);
    }
}
