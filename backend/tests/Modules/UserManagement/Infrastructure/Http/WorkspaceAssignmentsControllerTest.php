<?php

declare(strict_types=1);

namespace App\Tests\Modules\UserManagement\Infrastructure\Http;

use DateTimeImmutable;

use function json_decode;
use function json_encode;

use Modules\Auth\Domain\User;
use Modules\Auth\Domain\UserRepository;
use Modules\Auth\Domain\UserRole;
use Modules\Auth\Infrastructure\Security\HashablePlaintextUser;
use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\WorkspaceId;
use Modules\Workspaces\Domain\Workspace;
use Modules\Workspaces\Domain\WorkspaceMember;
use Modules\Workspaces\Domain\WorkspaceRepository;
use Modules\Workspaces\Domain\WorkspaceRole;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;

final class WorkspaceAssignmentsControllerTest extends WebTestCase
{
    private const string ADMIN_EMAIL = 'admin@example.com';

    private const string OWNER_EMAIL = 'owner@example.com';

    private const string MEMBER_EMAIL = 'member@example.com';

    private const string PASSWORD = 'secret-pass-long-enough';

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->client->disableReboot();
    }

    public function testShould401WhenUnauthenticated(): void
    {
        $targetUserId = Uuid::v7()->toRfc4122();
        $workspaceId = Uuid::v7()->toRfc4122();

        $this->postJson(
            "/api/user-management/users/{$targetUserId}/workspace-assignments",
            ['workspaceId' => $workspaceId, 'role' => 'member'],
            null,
        );

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function testShould201WhenSuperAdminAssignsMember(): void
    {
        $token = $this->seedUserAndLogin(self::ADMIN_EMAIL, UserRole::Admin);
        $ownerId = new UserId(Uuid::v7());
        $workspace = $this->seedWorkspaceWithOwner($ownerId);
        $targetUserId = new UserId(Uuid::v7());

        $this->postJson(
            "/api/user-management/users/{$targetUserId}/workspace-assignments",
            ['workspaceId' => (string) $workspace->id(), 'role' => 'member'],
            $token,
        );

        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        $payload = $this->decode();
        self::assertSame((string) $workspace->id(), $payload['workspaceId']);
        self::assertSame((string) $targetUserId, $payload['userId']);
        self::assertSame('member', $payload['role']);
    }

    public function testShould201WhenWorkspaceOwnerAssignsMember(): void
    {
        $ownerUser = $this->seedUser(self::OWNER_EMAIL, UserRole::User);
        $token = $this->login(self::OWNER_EMAIL);
        $workspace = $this->seedWorkspaceWithOwner($ownerUser->id());
        $targetUserId = new UserId(Uuid::v7());

        $this->postJson(
            "/api/user-management/users/{$targetUserId}/workspace-assignments",
            ['workspaceId' => (string) $workspace->id(), 'role' => 'member'],
            $token,
        );

        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
    }

    public function testShould403WhenRegularMemberAttemptsAssign(): void
    {
        $memberUser = $this->seedUser(self::MEMBER_EMAIL, UserRole::User);
        $token = $this->login(self::MEMBER_EMAIL);
        $ownerId = new UserId(Uuid::v7());
        $workspace = $this->seedWorkspaceWithOwner($ownerId);
        $this->workspaces()->saveMembership(
            WorkspaceMember::join($workspace->id(), $memberUser->id(), WorkspaceRole::Member, $this->now()),
        );
        $targetUserId = new UserId(Uuid::v7());

        $this->postJson(
            "/api/user-management/users/{$targetUserId}/workspace-assignments",
            ['workspaceId' => (string) $workspace->id(), 'role' => 'member'],
            $token,
        );

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testShould422WhenAssigningOwnerRole(): void
    {
        $token = $this->seedUserAndLogin(self::ADMIN_EMAIL, UserRole::Admin);
        $workspace = $this->seedWorkspaceWithOwner(new UserId(Uuid::v7()));
        $targetUserId = new UserId(Uuid::v7());

        $this->postJson(
            "/api/user-management/users/{$targetUserId}/workspace-assignments",
            ['workspaceId' => (string) $workspace->id(), 'role' => 'owner'],
            $token,
        );

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
    }

    public function testShould409WhenUserAlreadyInWorkspace(): void
    {
        $token = $this->seedUserAndLogin(self::ADMIN_EMAIL, UserRole::Admin);
        $workspace = $this->seedWorkspaceWithOwner(new UserId(Uuid::v7()));
        $existingMember = new UserId(Uuid::v7());
        $this->workspaces()->saveMembership(
            WorkspaceMember::join($workspace->id(), $existingMember, WorkspaceRole::Member, $this->now()),
        );

        $this->postJson(
            "/api/user-management/users/{$existingMember}/workspace-assignments",
            ['workspaceId' => (string) $workspace->id(), 'role' => 'member'],
            $token,
        );

        self::assertSame(Response::HTTP_CONFLICT, $this->client->getResponse()->getStatusCode());
        self::assertSame('ALREADY_MEMBER', $this->decode()['error']['code']);
    }

    public function testShould200WhenChangingRoleFromAdminToMember(): void
    {
        $token = $this->seedUserAndLogin(self::ADMIN_EMAIL, UserRole::Admin);
        $workspace = $this->seedWorkspaceWithOwner(new UserId(Uuid::v7()));
        $memberId = new UserId(Uuid::v7());
        $this->workspaces()->saveMembership(
            WorkspaceMember::join($workspace->id(), $memberId, WorkspaceRole::Admin, $this->now()),
        );

        $this->patchJson(
            "/api/user-management/users/{$memberId}/workspace-assignments/{$workspace->id()}",
            ['role' => 'member'],
            $token,
        );

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        self::assertSame('member', $this->decode()['role']);
    }

    public function testShould204WhenDeletingNonAdminMember(): void
    {
        $token = $this->seedUserAndLogin(self::ADMIN_EMAIL, UserRole::Admin);
        $workspace = $this->seedWorkspaceWithOwner(new UserId(Uuid::v7()));
        $memberId = new UserId(Uuid::v7());
        $this->workspaces()->saveMembership(
            WorkspaceMember::join($workspace->id(), $memberId, WorkspaceRole::Member, $this->now()),
        );

        $this->client->request(
            'DELETE',
            "/api/user-management/users/{$memberId}/workspace-assignments/{$workspace->id()}",
            server: $this->auth($token),
        );

        self::assertSame(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());
        self::assertNull($this->workspaces()->findMembership($workspace->id(), $memberId));
    }

    public function testShould422WhenDeletingAdmin(): void
    {
        $token = $this->seedUserAndLogin(self::ADMIN_EMAIL, UserRole::Admin);
        $workspace = $this->seedWorkspaceWithOwner(new UserId(Uuid::v7()));
        $adminMemberId = new UserId(Uuid::v7());
        $this->workspaces()->saveMembership(
            WorkspaceMember::join($workspace->id(), $adminMemberId, WorkspaceRole::Admin, $this->now()),
        );

        $this->client->request(
            'DELETE',
            "/api/user-management/users/{$adminMemberId}/workspace-assignments/{$workspace->id()}",
            server: $this->auth($token),
        );

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
        self::assertSame('ADMIN_MUST_BE_DOWNGRADED_FIRST', $this->decode()['error']['code']);
    }

    private function seedUserAndLogin(string $email, UserRole $role): string
    {
        $this->seedUser($email, $role);

        return $this->login($email);
    }

    private function seedUser(string $email, UserRole $role): User
    {
        /** @var UserPasswordHasherInterface $hasher */
        $hasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        $passwordHash = $hasher->hashPassword(new HashablePlaintextUser(), self::PASSWORD);

        $user = User::createWithRole(
            new UserId(Uuid::v7()),
            $email,
            $passwordHash,
            $role,
            $this->now(),
        );

        /** @var UserRepository $users */
        $users = self::getContainer()->get(UserRepository::class);
        $users->save($user);

        return $user;
    }

    private function seedWorkspaceWithOwner(UserId $ownerId): Workspace
    {
        $now = $this->now();
        $workspace = Workspace::create(WorkspaceId::generate(), 'Shared', 'shared-'.Uuid::v7(), '#8B5CF6', $ownerId, $now);
        $repo = $this->workspaces();
        $repo->saveWorkspace($workspace);
        $repo->saveMembership(WorkspaceMember::join($workspace->id(), $ownerId, WorkspaceRole::Owner, $now));

        return $workspace;
    }

    private function workspaces(): WorkspaceRepository
    {
        /** @var WorkspaceRepository $repo */
        $repo = self::getContainer()->get(WorkspaceRepository::class);

        return $repo;
    }

    private function login(string $email): string
    {
        $this->postJson('/api/auth/login', ['email' => $email, 'password' => self::PASSWORD], null);
        $payload = $this->decode();

        return (string) $payload['token'];
    }

    /**
     * @param array<string, mixed> $body
     */
    private function postJson(string $path, array $body, ?string $token): void
    {
        $server = ['CONTENT_TYPE' => 'application/json'];
        if ($token !== null) {
            $server['HTTP_AUTHORIZATION'] = 'Bearer '.$token;
        }

        $this->client->request('POST', $path, server: $server, content: json_encode($body, JSON_THROW_ON_ERROR));
    }

    /**
     * @param array<string, mixed> $body
     */
    private function patchJson(string $path, array $body, string $token): void
    {
        $this->client->request(
            'PATCH',
            $path,
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer '.$token,
            ],
            content: json_encode($body, JSON_THROW_ON_ERROR),
        );
    }

    /**
     * @return array<string, string>
     */
    private function auth(string $token): array
    {
        return ['HTTP_AUTHORIZATION' => 'Bearer '.$token];
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(): array
    {
        /** @var array<string, mixed> $decoded */
        $decoded = json_decode((string) $this->client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);

        return $decoded;
    }

    private function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-11-07T14:00:00+00:00');
    }
}
