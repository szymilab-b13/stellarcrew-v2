<?php

declare(strict_types=1);

namespace App\Tests\Modules\UserManagement\Infrastructure\Audit;

use App\Tests\Fixtures\Auth\SpyAuthApi;
use App\Tests\Fixtures\UserManagement\InMemoryUserProfileRepository;
use App\Tests\Fixtures\Workspaces\InMemoryWorkspaceRepository;
use App\Tests\Fixtures\Workspaces\SpyWorkspacesApi;
use App\Tests\Fixtures\Workspaces\ThrowingWorkspacesApi;
use DateTimeImmutable;
use Modules\Audit\Api\Dto\AuditEntryDto;
use Modules\Audit\Api\Exception\UndoConflictException;
use Modules\Shared\Kernel\AuditEntryId;
use Modules\Shared\Kernel\FrozenClock;
use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\WorkspaceId;
use Modules\UserManagement\Application\CreateUserCommandHandler;
use Modules\UserManagement\Application\DeactivateUserCommandHandler;
use Modules\UserManagement\Application\ReactivateUserCommandHandler;
use Modules\UserManagement\Domain\UserProfile;
use Modules\UserManagement\Infrastructure\Audit\UserManagementUndoer;
use Modules\Workspaces\Api\Exception\MemberAlreadyExistsException;
use Modules\Workspaces\Api\Exception\MemberNotFoundException;
use Modules\Workspaces\Application\AssignMemberCommandHandler;
use Modules\Workspaces\Application\ChangeMemberRoleCommandHandler;
use Modules\Workspaces\Application\RemoveMemberCommandHandler;
use Modules\Workspaces\Domain\WorkspaceMember;
use Modules\Workspaces\Domain\WorkspaceRole;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class UserManagementUndoerTest extends TestCase
{
    public function testShouldSupportAllSixUserManagementUndoableActions(): void
    {
        $sut = $this->buildUndoer();

        self::assertTrue($sut->supports(CreateUserCommandHandler::AUDIT_ACTION));
        self::assertTrue($sut->supports(DeactivateUserCommandHandler::AUDIT_ACTION));
        self::assertTrue($sut->supports(ReactivateUserCommandHandler::AUDIT_ACTION));
        self::assertTrue($sut->supports(AssignMemberCommandHandler::AUDIT_ACTION));
        self::assertTrue($sut->supports(RemoveMemberCommandHandler::AUDIT_ACTION));
        self::assertTrue($sut->supports(ChangeMemberRoleCommandHandler::AUDIT_ACTION));
        self::assertFalse($sut->supports('user_management.user.password_reset'));
        self::assertFalse($sut->supports('user_management.user.profile_updated'));
    }

    public function testShouldDeactivateUserAndClearMembershipsWhenUndoingUserCreated(): void
    {
        $userId = new UserId(Uuid::v7());
        $profiles = new InMemoryUserProfileRepository();
        $profiles->save(UserProfile::create($userId, 'Nina', '#abcdef', new DateTimeImmutable('2026-11-07T10:00:00+00:00')));

        $auth = new SpyAuthApi();
        $workspaces = new SpyWorkspacesApi();
        $now = new DateTimeImmutable('2026-11-07T18:00:00+00:00');
        $sut = new UserManagementUndoer($auth, $profiles, $workspaces, new FrozenClock($now));

        $sut->undo(
            $this->entry(CreateUserCommandHandler::AUDIT_ACTION, $userId),
            $this->actor(),
        );

        self::assertCount(1, $auth->markDeactivatedCalls);
        self::assertSame((string) $userId, (string) $auth->markDeactivatedCalls[0]);
        self::assertTrue($profiles->findById($userId)?->isDeactivated());
        self::assertCount(1, $workspaces->removeAllMembershipsOfCalls);
        self::assertSame((string) $userId, (string) $workspaces->removeAllMembershipsOfCalls[0]);
    }

    public function testShouldThrowAlreadyDeactivatedConflictWhenUndoingUserCreatedButUserIsDeactivated(): void
    {
        $userId = new UserId(Uuid::v7());
        $profiles = new InMemoryUserProfileRepository();
        $deactivation = new DateTimeImmutable('2026-11-06T12:00:00+00:00');
        $profiles->save(new UserProfile(
            $userId,
            null,
            '#abcdef',
            new DateTimeImmutable('2026-11-01T08:00:00+00:00'),
            $deactivation,
            $deactivation,
        ));
        $auth = new SpyAuthApi();
        $workspaces = new SpyWorkspacesApi();
        $sut = new UserManagementUndoer($auth, $profiles, $workspaces, new FrozenClock(new DateTimeImmutable('2026-11-07T18:00:00+00:00')));

        try {
            $sut->undo(
                $this->entry(CreateUserCommandHandler::AUDIT_ACTION, $userId),
                $this->actor(),
            );
            self::fail('Expected UndoConflictException');
        } catch (UndoConflictException $undoConflictException) {
            self::assertSame('already_deactivated', $undoConflictException->conflictCode());
        }

        self::assertSame([], $auth->markDeactivatedCalls);
        self::assertSame([], $workspaces->removeAllMembershipsOfCalls);
    }

    public function testShouldReactivateUserWhenUndoingUserDeactivated(): void
    {
        $userId = new UserId(Uuid::v7());
        $profiles = new InMemoryUserProfileRepository();
        $deactivation = new DateTimeImmutable('2026-11-06T12:00:00+00:00');
        $profiles->save(new UserProfile(
            $userId,
            null,
            '#abcdef',
            new DateTimeImmutable('2026-11-01T08:00:00+00:00'),
            $deactivation,
            $deactivation,
        ));
        $auth = new SpyAuthApi();
        $workspaces = new SpyWorkspacesApi();
        $sut = new UserManagementUndoer($auth, $profiles, $workspaces, new FrozenClock(new DateTimeImmutable('2026-11-07T18:00:00+00:00')));

        $sut->undo(
            $this->entry(DeactivateUserCommandHandler::AUDIT_ACTION, $userId),
            $this->actor(),
        );

        self::assertCount(1, $auth->markReactivatedCalls);
        self::assertSame((string) $userId, (string) $auth->markReactivatedCalls[0]);
        self::assertFalse($profiles->findById($userId)?->isDeactivated());
        self::assertSame([], $workspaces->removeAllMembershipsOfCalls);
    }

    public function testShouldThrowNotDeactivatedConflictWhenUndoingUserDeactivatedButUserAlreadyActive(): void
    {
        $userId = new UserId(Uuid::v7());
        $profiles = new InMemoryUserProfileRepository();
        $profiles->save(UserProfile::create($userId, null, '#abcdef', new DateTimeImmutable('2026-11-01T08:00:00+00:00')));

        $auth = new SpyAuthApi();
        $sut = new UserManagementUndoer($auth, $profiles, new SpyWorkspacesApi(), new FrozenClock(new DateTimeImmutable('2026-11-07T18:00:00+00:00')));

        try {
            $sut->undo(
                $this->entry(DeactivateUserCommandHandler::AUDIT_ACTION, $userId),
                $this->actor(),
            );
            self::fail('Expected UndoConflictException');
        } catch (UndoConflictException $undoConflictException) {
            self::assertSame('not_deactivated', $undoConflictException->conflictCode());
        }

        self::assertSame([], $auth->markReactivatedCalls);
    }

    public function testShouldDeactivateUserWhenUndoingUserReactivated(): void
    {
        $userId = new UserId(Uuid::v7());
        $profiles = new InMemoryUserProfileRepository();
        $profiles->save(UserProfile::create($userId, 'Nina', '#abcdef', new DateTimeImmutable('2026-11-07T10:00:00+00:00')));

        $auth = new SpyAuthApi();
        $sut = new UserManagementUndoer($auth, $profiles, new SpyWorkspacesApi(), new FrozenClock(new DateTimeImmutable('2026-11-07T18:00:00+00:00')));

        $sut->undo(
            $this->entry(ReactivateUserCommandHandler::AUDIT_ACTION, $userId),
            $this->actor(),
        );

        self::assertCount(1, $auth->markDeactivatedCalls);
        self::assertTrue($profiles->findById($userId)?->isDeactivated());
    }

    public function testShouldThrowAlreadyDeactivatedConflictWhenUndoingUserReactivatedButUserIsDeactivated(): void
    {
        $userId = new UserId(Uuid::v7());
        $profiles = new InMemoryUserProfileRepository();
        $deactivation = new DateTimeImmutable('2026-11-06T12:00:00+00:00');
        $profiles->save(new UserProfile(
            $userId,
            null,
            '#abcdef',
            new DateTimeImmutable('2026-11-01T08:00:00+00:00'),
            $deactivation,
            $deactivation,
        ));
        $auth = new SpyAuthApi();
        $sut = new UserManagementUndoer($auth, $profiles, new SpyWorkspacesApi(), new FrozenClock(new DateTimeImmutable('2026-11-07T18:00:00+00:00')));

        try {
            $sut->undo(
                $this->entry(ReactivateUserCommandHandler::AUDIT_ACTION, $userId),
                $this->actor(),
            );
            self::fail('Expected UndoConflictException');
        } catch (UndoConflictException $undoConflictException) {
            self::assertSame('already_deactivated', $undoConflictException->conflictCode());
        }

        self::assertSame([], $auth->markDeactivatedCalls);
    }

    public function testShouldThrowUserNotFoundConflictWhenProfileMissing(): void
    {
        $sut = $this->buildUndoer();

        $this->expectException(UndoConflictException::class);
        $this->expectExceptionMessage('user_not_found');

        $sut->undo(
            $this->entry(DeactivateUserCommandHandler::AUDIT_ACTION, new UserId(Uuid::v7())),
            $this->actor(),
        );
    }

    public function testShouldDelegateToInMemoryRepositoryWhenRemovingAllMembershipsOfUser(): void
    {
        $userId = new UserId(Uuid::v7());
        $otherUserId = new UserId(Uuid::v7());
        $workspaceA = new WorkspaceId(Uuid::v7());
        $workspaceB = new WorkspaceId(Uuid::v7());
        $joinedAt = new DateTimeImmutable('2026-11-01T08:00:00+00:00');
        $repository = new InMemoryWorkspaceRepository();
        $repository->saveMembership(WorkspaceMember::join($workspaceA, $userId, WorkspaceRole::Admin, $joinedAt));
        $repository->saveMembership(WorkspaceMember::join($workspaceB, $userId, WorkspaceRole::Member, $joinedAt));
        $repository->saveMembership(WorkspaceMember::join($workspaceA, $otherUserId, WorkspaceRole::Member, $joinedAt));

        $repository->removeAllMembershipsOf($userId);

        self::assertNull($repository->findMembership($workspaceA, $userId));
        self::assertNull($repository->findMembership($workspaceB, $userId));
        self::assertNotNull($repository->findMembership($workspaceA, $otherUserId));
    }

    public function testShouldRemoveMembershipAsSystemWhenUndoingMemberAssigned(): void
    {
        $workspaceId = new WorkspaceId(Uuid::v7());
        $userId = new UserId(Uuid::v7());
        $workspaces = new SpyWorkspacesApi();
        $sut = new UserManagementUndoer(
            new SpyAuthApi(),
            new InMemoryUserProfileRepository(),
            $workspaces,
            new FrozenClock(new DateTimeImmutable('2026-11-07T18:00:00+00:00')),
        );

        $sut->undo(
            $this->membershipEntry(
                AssignMemberCommandHandler::AUDIT_ACTION,
                afterState: [
                    'workspace_id' => (string) $workspaceId,
                    'user_id' => (string) $userId,
                    'role' => WorkspaceRole::Admin->value,
                ],
            ),
            $this->actor(),
        );

        self::assertCount(1, $workspaces->removeMembershipAsSystemCalls);
        self::assertSame((string) $workspaceId, (string) $workspaces->removeMembershipAsSystemCalls[0]['workspaceId']);
        self::assertSame((string) $userId, (string) $workspaces->removeMembershipAsSystemCalls[0]['userId']);
    }

    public function testShouldThrowNotAMemberAnymoreConflictWhenUndoingMemberAssignedButMemberIsGone(): void
    {
        $workspaceId = new WorkspaceId(Uuid::v7());
        $userId = new UserId(Uuid::v7());
        $workspaces = new ThrowingWorkspacesApi();
        $workspaces->throwOnRemoveMembershipAsSystem = MemberNotFoundException::forPair($workspaceId, $userId);

        $sut = new UserManagementUndoer(
            new SpyAuthApi(),
            new InMemoryUserProfileRepository(),
            $workspaces,
            new FrozenClock(new DateTimeImmutable('2026-11-07T18:00:00+00:00')),
        );

        try {
            $sut->undo(
                $this->membershipEntry(
                    AssignMemberCommandHandler::AUDIT_ACTION,
                    afterState: [
                        'workspace_id' => (string) $workspaceId,
                        'user_id' => (string) $userId,
                        'role' => WorkspaceRole::Member->value,
                    ],
                ),
                $this->actor(),
            );
            self::fail('Expected UndoConflictException');
        } catch (UndoConflictException $undoConflictException) {
            self::assertSame('not_a_member_anymore', $undoConflictException->conflictCode());
        }
    }

    public function testShouldRestoreMembershipAsSystemWhenUndoingMemberRemoved(): void
    {
        $workspaceId = new WorkspaceId(Uuid::v7());
        $userId = new UserId(Uuid::v7());
        $workspaces = new SpyWorkspacesApi();
        $sut = new UserManagementUndoer(
            new SpyAuthApi(),
            new InMemoryUserProfileRepository(),
            $workspaces,
            new FrozenClock(new DateTimeImmutable('2026-11-07T18:00:00+00:00')),
        );

        $sut->undo(
            $this->membershipEntry(
                RemoveMemberCommandHandler::AUDIT_ACTION,
                beforeState: [
                    'workspace_id' => (string) $workspaceId,
                    'user_id' => (string) $userId,
                    'role' => WorkspaceRole::Guest->value,
                ],
            ),
            $this->actor(),
        );

        self::assertCount(1, $workspaces->restoreMembershipAsSystemCalls);
        $call = $workspaces->restoreMembershipAsSystemCalls[0];
        self::assertSame((string) $workspaceId, (string) $call['workspaceId']);
        self::assertSame((string) $userId, (string) $call['userId']);
        self::assertSame(WorkspaceRole::Guest->value, $call['role']);
    }

    public function testShouldThrowAlreadyMemberAgainConflictWhenUndoingMemberRemovedButUserAlreadyAssigned(): void
    {
        $workspaceId = new WorkspaceId(Uuid::v7());
        $userId = new UserId(Uuid::v7());
        $workspaces = new ThrowingWorkspacesApi();
        $workspaces->throwOnRestoreMembershipAsSystem = MemberAlreadyExistsException::forPair($workspaceId, $userId);

        $sut = new UserManagementUndoer(
            new SpyAuthApi(),
            new InMemoryUserProfileRepository(),
            $workspaces,
            new FrozenClock(new DateTimeImmutable('2026-11-07T18:00:00+00:00')),
        );

        try {
            $sut->undo(
                $this->membershipEntry(
                    RemoveMemberCommandHandler::AUDIT_ACTION,
                    beforeState: [
                        'workspace_id' => (string) $workspaceId,
                        'user_id' => (string) $userId,
                        'role' => WorkspaceRole::Member->value,
                    ],
                ),
                $this->actor(),
            );
            self::fail('Expected UndoConflictException');
        } catch (UndoConflictException $undoConflictException) {
            self::assertSame('already_member_again', $undoConflictException->conflictCode());
        }
    }

    public function testShouldSetMembershipRoleAsSystemWhenUndoingRoleChanged(): void
    {
        $workspaceId = new WorkspaceId(Uuid::v7());
        $userId = new UserId(Uuid::v7());
        $workspaces = new SpyWorkspacesApi();
        $sut = new UserManagementUndoer(
            new SpyAuthApi(),
            new InMemoryUserProfileRepository(),
            $workspaces,
            new FrozenClock(new DateTimeImmutable('2026-11-07T18:00:00+00:00')),
        );

        $sut->undo(
            $this->membershipEntry(
                ChangeMemberRoleCommandHandler::AUDIT_ACTION,
                beforeState: ['role' => WorkspaceRole::Member->value],
                afterState: ['role' => WorkspaceRole::Admin->value],
                metadata: [
                    'workspace_id' => (string) $workspaceId,
                    'user_id' => (string) $userId,
                ],
            ),
            $this->actor(),
        );

        self::assertCount(1, $workspaces->setMembershipRoleAsSystemCalls);
        $call = $workspaces->setMembershipRoleAsSystemCalls[0];
        self::assertSame((string) $workspaceId, (string) $call['workspaceId']);
        self::assertSame((string) $userId, (string) $call['userId']);
        self::assertSame(WorkspaceRole::Member->value, $call['role']);
    }

    public function testShouldThrowMemberNotFoundConflictWhenUndoingRoleChangedButMemberGone(): void
    {
        $workspaceId = new WorkspaceId(Uuid::v7());
        $userId = new UserId(Uuid::v7());
        $workspaces = new ThrowingWorkspacesApi();
        $workspaces->throwOnSetMembershipRoleAsSystem = MemberNotFoundException::forPair($workspaceId, $userId);

        $sut = new UserManagementUndoer(
            new SpyAuthApi(),
            new InMemoryUserProfileRepository(),
            $workspaces,
            new FrozenClock(new DateTimeImmutable('2026-11-07T18:00:00+00:00')),
        );

        try {
            $sut->undo(
                $this->membershipEntry(
                    ChangeMemberRoleCommandHandler::AUDIT_ACTION,
                    beforeState: ['role' => WorkspaceRole::Member->value],
                    afterState: ['role' => WorkspaceRole::Admin->value],
                    metadata: [
                        'workspace_id' => (string) $workspaceId,
                        'user_id' => (string) $userId,
                    ],
                ),
                $this->actor(),
            );
            self::fail('Expected UndoConflictException');
        } catch (UndoConflictException $undoConflictException) {
            self::assertSame('member_not_found', $undoConflictException->conflictCode());
        }
    }

    private function buildUndoer(): UserManagementUndoer
    {
        return new UserManagementUndoer(
            new SpyAuthApi(),
            new InMemoryUserProfileRepository(),
            new SpyWorkspacesApi(),
            new FrozenClock(new DateTimeImmutable('2026-11-07T18:00:00+00:00')),
        );
    }

    private function entry(string $action, UserId $userId): AuditEntryDto
    {
        return new AuditEntryDto(
            id: AuditEntryId::generate(),
            actorUserId: $this->actor(),
            actorEmail: null,
            action: $action,
            resourceType: 'user',
            resourceId: (string) $userId,
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

    /**
     * @param array<string, mixed>|null $beforeState
     * @param array<string, mixed>|null $afterState
     * @param array<string, mixed>      $metadata
     */
    private function membershipEntry(
        string $action,
        ?array $beforeState = null,
        ?array $afterState = null,
        array $metadata = [],
    ): AuditEntryDto {
        return new AuditEntryDto(
            id: AuditEntryId::generate(),
            actorUserId: $this->actor(),
            actorEmail: null,
            action: $action,
            resourceType: 'workspace_member',
            resourceId: (string) Uuid::v7(),
            beforeState: $beforeState,
            afterState: $afterState,
            metadata: $metadata,
            occurredAt: new DateTimeImmutable('2026-11-07T15:00:00+00:00'),
            undoneAt: null,
            undoneBy: null,
            undoneByEmail: null,
            revertsEntryId: null,
        );
    }

    private function actor(): UserId
    {
        return new UserId(Uuid::v7());
    }
}
