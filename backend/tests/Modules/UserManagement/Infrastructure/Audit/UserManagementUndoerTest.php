<?php

declare(strict_types=1);

namespace App\Tests\Modules\UserManagement\Infrastructure\Audit;

use App\Tests\Fixtures\Auth\SpyAuthApi;
use App\Tests\Fixtures\UserManagement\InMemoryUserProfileRepository;
use App\Tests\Fixtures\Workspaces\InMemoryWorkspaceRepository;
use App\Tests\Fixtures\Workspaces\SpyWorkspacesApi;
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
use Modules\Workspaces\Domain\WorkspaceMember;
use Modules\Workspaces\Domain\WorkspaceRole;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class UserManagementUndoerTest extends TestCase
{
    public function testShouldSupportUserCreatedDeactivatedAndReactivatedActions(): void
    {
        $sut = $this->buildUndoer();

        self::assertTrue($sut->supports(CreateUserCommandHandler::AUDIT_ACTION));
        self::assertTrue($sut->supports(DeactivateUserCommandHandler::AUDIT_ACTION));
        self::assertTrue($sut->supports(ReactivateUserCommandHandler::AUDIT_ACTION));
        self::assertFalse($sut->supports('workspace_member.assigned'));
        self::assertFalse($sut->supports('user_management.user.password_reset'));
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

    private function actor(): UserId
    {
        return new UserId(Uuid::v7());
    }
}
