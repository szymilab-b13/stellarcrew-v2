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
use Modules\Workspaces\Api\Exception\MemberAlreadyExistsException;
use Modules\Workspaces\Application\AssignMemberCommand;
use Modules\Workspaces\Application\AssignMemberCommandHandler;
use Modules\Workspaces\Domain\WorkspaceMember;
use Modules\Workspaces\Domain\WorkspaceRole;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class AssignMemberHandlerTest extends TestCase
{
    public function testShouldPersistMemberWithRoleWhenCommandExecuted(): void
    {
        $repo = new InMemoryWorkspaceRepository();
        $workspaceId = new WorkspaceId(Uuid::v7());
        $userId = new UserId(Uuid::v7());
        $sut = new AssignMemberCommandHandler(
            $repo,
            new SpyAuditLogger(),
            new FrozenClock(new DateTimeImmutable('2026-11-07T15:00:00+00:00')),
        );

        $member = $sut(new AssignMemberCommand($workspaceId, $userId, WorkspaceRole::Member));

        self::assertSame(WorkspaceRole::Member, $member->role());
        $stored = $repo->findMembership($workspaceId, $userId);
        self::assertInstanceOf(WorkspaceMember::class, $stored);
        self::assertTrue($stored->workspaceId()->equals($workspaceId));
        self::assertTrue($stored->userId()->equals($userId));
        self::assertSame(WorkspaceRole::Member, $stored->role());
    }

    public function testShouldThrowMemberAlreadyExistsWhenUserAlreadyMember(): void
    {
        $repo = new InMemoryWorkspaceRepository();
        $workspaceId = new WorkspaceId(Uuid::v7());
        $userId = new UserId(Uuid::v7());
        $audit = new SpyAuditLogger();
        $sut = new AssignMemberCommandHandler(
            $repo,
            $audit,
            new FrozenClock(new DateTimeImmutable('2026-11-07T15:00:00+00:00')),
        );
        $sut(new AssignMemberCommand($workspaceId, $userId, WorkspaceRole::Member));

        $this->expectException(MemberAlreadyExistsException::class);

        try {
            $sut(new AssignMemberCommand($workspaceId, $userId, WorkspaceRole::Admin));
        } finally {
            self::assertCount(1, $audit->entries);
            self::assertSame(WorkspaceRole::Member, $repo->findMembership($workspaceId, $userId)?->role());
        }
    }

    public function testShouldThrowInvalidMemberRoleWhenAssigningOwnerRole(): void
    {
        $repo = new InMemoryWorkspaceRepository();
        $audit = new SpyAuditLogger();
        $sut = new AssignMemberCommandHandler(
            $repo,
            $audit,
            new FrozenClock(new DateTimeImmutable('2026-11-07T15:00:00+00:00')),
        );

        $this->expectException(InvalidMemberRoleException::class);

        try {
            $sut(new AssignMemberCommand(
                new WorkspaceId(Uuid::v7()),
                new UserId(Uuid::v7()),
                WorkspaceRole::Owner,
            ));
        } finally {
            self::assertSame([], $audit->entries);
        }
    }

    public function testShouldLogAuditEntryWithAfterStateWhenMemberAssigned(): void
    {
        $audit = new SpyAuditLogger();
        $workspaceId = new WorkspaceId(Uuid::v7());
        $userId = new UserId(Uuid::v7());
        $invitedBy = new UserId(Uuid::v7());
        $sut = new AssignMemberCommandHandler(
            new InMemoryWorkspaceRepository(),
            $audit,
            new FrozenClock(new DateTimeImmutable('2026-11-07T15:00:00+00:00')),
        );

        $sut(new AssignMemberCommand($workspaceId, $userId, WorkspaceRole::Admin, $invitedBy));

        self::assertCount(1, $audit->entries);
        $entry = $audit->entries[0];
        self::assertSame(AssignMemberCommandHandler::AUDIT_ACTION, $entry->action);
        self::assertSame(AssignMemberCommandHandler::AUDIT_RESOURCE_TYPE, $entry->resourceType);
        self::assertTrue($entry->userId?->equals($invitedBy));
        self::assertSame((string) $workspaceId, $entry->metadata['after_state']['workspace_id']);
        self::assertSame((string) $userId, $entry->metadata['after_state']['user_id']);
        self::assertSame('admin', $entry->metadata['after_state']['role']);
    }
}
