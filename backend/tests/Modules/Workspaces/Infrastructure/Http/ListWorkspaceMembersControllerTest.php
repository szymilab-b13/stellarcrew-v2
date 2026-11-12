<?php

declare(strict_types=1);

namespace App\Tests\Modules\Workspaces\Infrastructure\Http;

use App\Tests\Fixtures\Workspaces\InMemoryWorkspaceMembersReadModel;
use DateTimeImmutable;

use function json_decode;
use function json_encode;

use Modules\Auth\Domain\User;
use Modules\Auth\Domain\UserRepository;
use Modules\Auth\Domain\UserRole;
use Modules\Auth\Infrastructure\Security\HashablePlaintextUser;
use Modules\Shared\Kernel\OrganizationId;
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

final class ListWorkspaceMembersControllerTest extends WebTestCase
{
    private const string PASSWORD = 'secret-pass-long-enough';

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->client->disableReboot();
    }

    public function testShould401WhenUnauthenticated(): void
    {
        $workspaceId = Uuid::v7()->toRfc4122();

        $this->client->request('GET', "/api/workspaces/{$workspaceId}/members");

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function testShould404WhenWorkspaceDoesNotExist(): void
    {
        $token = $this->seedUserAndLogin('admin@example.com', UserRole::Admin);
        $missingWorkspaceId = Uuid::v7()->toRfc4122();

        $this->getJson("/api/workspaces/{$missingWorkspaceId}/members", $token);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testShould403WhenAuthenticatedUserIsNotMember(): void
    {
        $this->seedUser('outsider@example.com', UserRole::User);
        $token = $this->login('outsider@example.com');
        $workspace = $this->seedWorkspaceWithOwner(new UserId(Uuid::v7()));

        $this->getJson("/api/workspaces/{$workspace->id()}/members", $token);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testShould200WhenMemberListsOwnWorkspaceMembers(): void
    {
        $memberUser = $this->seedUser('member@example.com', UserRole::User);
        $token = $this->login('member@example.com');
        $workspace = $this->seedWorkspaceWithOwner(new UserId(Uuid::v7()), 'owner@example.com');
        $this->seedMembership($workspace, $memberUser->id(), 'member@example.com', WorkspaceRole::Member);

        $this->getJson("/api/workspaces/{$workspace->id()}/members", $token);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $payload = $this->decode();
        self::assertCount(2, $payload['items']);
    }

    public function testShould200WhenSuperAdminListsMembersOfAnyWorkspace(): void
    {
        $token = $this->seedUserAndLogin('super@example.com', UserRole::Admin);
        $workspace = $this->seedWorkspaceWithOwner(new UserId(Uuid::v7()), 'owner@example.com');
        $memberId = new UserId(Uuid::v7());
        $this->seedMembership($workspace, $memberId, 'member@example.com', WorkspaceRole::Member);

        $this->getJson("/api/workspaces/{$workspace->id()}/members", $token);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $payload = $this->decode();
        self::assertCount(2, $payload['items']);
    }

    public function testShouldReturnItemsSortedByJoinedAtAscendingWithOwnerFirst(): void
    {
        $token = $this->seedUserAndLogin('super@example.com', UserRole::Admin);
        $workspace = $this->seedWorkspaceWithOwner(
            new UserId(Uuid::v7()),
            'owner@example.com',
            new DateTimeImmutable('2026-11-01T10:00:00+00:00'),
        );
        $this->seedMembership(
            $workspace,
            new UserId(Uuid::v7()),
            'later@example.com',
            WorkspaceRole::Member,
            new DateTimeImmutable('2026-11-03T10:00:00+00:00'),
        );
        $this->seedMembership(
            $workspace,
            new UserId(Uuid::v7()),
            'middle@example.com',
            WorkspaceRole::Admin,
            new DateTimeImmutable('2026-11-02T10:00:00+00:00'),
        );

        $this->getJson("/api/workspaces/{$workspace->id()}/members", $token);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $items = $this->decode()['items'];
        self::assertSame('owner@example.com', $items[0]['email']);
        self::assertSame('owner', $items[0]['role']);
        self::assertSame('middle@example.com', $items[1]['email']);
        self::assertSame('admin', $items[1]['role']);
        self::assertSame('later@example.com', $items[2]['email']);
        self::assertSame('member', $items[2]['role']);
    }

    public function testShouldReturnUserIdEmailRoleAndJoinedAtPerItem(): void
    {
        $token = $this->seedUserAndLogin('super@example.com', UserRole::Admin);
        $ownerId = new UserId(Uuid::v7());
        $joinedAt = new DateTimeImmutable('2026-11-01T10:00:00+00:00');
        $workspace = $this->seedWorkspaceWithOwner($ownerId, 'owner@example.com', $joinedAt);

        $this->getJson("/api/workspaces/{$workspace->id()}/members", $token);

        $item = $this->decode()['items'][0];
        self::assertSame((string) $ownerId, $item['userId']);
        self::assertSame('owner@example.com', $item['email']);
        self::assertSame('owner', $item['role']);
        self::assertSame($joinedAt->format(DATE_ATOM), $item['joinedAt']);
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
        $hash = $hasher->hashPassword(new HashablePlaintextUser(), self::PASSWORD);

        $user = User::createWithRole(
            new UserId(Uuid::v7()),
            $email,
            $hash,
            $role,
            $this->now(),
        );

        /** @var UserRepository $users */
        $users = self::getContainer()->get(UserRepository::class);
        $users->save($user);

        return $user;
    }

    private function seedWorkspaceWithOwner(
        UserId $ownerId,
        string $ownerEmail = 'owner@example.com',
        ?DateTimeImmutable $joinedAt = null,
    ): Workspace {
        $joinedAt ??= $this->now();
        $workspace = Workspace::create(
            WorkspaceId::generate(),
            'Shared',
            'shared-'.Uuid::v7(),
            '#8B5CF6',
            $ownerId,
            new OrganizationId(Uuid::v7()),
            $joinedAt,
        );
        $repo = $this->workspaces();
        $repo->saveWorkspace($workspace);
        $repo->saveMembership(WorkspaceMember::join($workspace->id(), $ownerId, WorkspaceRole::Owner, $joinedAt));
        $this->members()->seedMember(
            (string) $workspace->id(),
            (string) $ownerId,
            $ownerEmail,
            WorkspaceRole::Owner->value,
            $joinedAt,
        );

        return $workspace;
    }

    private function seedMembership(
        Workspace $workspace,
        UserId $userId,
        string $email,
        WorkspaceRole $role,
        ?DateTimeImmutable $joinedAt = null,
    ): void {
        $joinedAt ??= $this->now();
        $this->workspaces()->saveMembership(
            WorkspaceMember::join($workspace->id(), $userId, $role, $joinedAt),
        );
        $this->members()->seedMember(
            (string) $workspace->id(),
            (string) $userId,
            $email,
            $role->value,
            $joinedAt,
        );
    }

    private function workspaces(): WorkspaceRepository
    {
        /** @var WorkspaceRepository $repo */
        $repo = self::getContainer()->get(WorkspaceRepository::class);

        return $repo;
    }

    private function members(): InMemoryWorkspaceMembersReadModel
    {
        /** @var InMemoryWorkspaceMembersReadModel $members */
        $members = self::getContainer()->get(InMemoryWorkspaceMembersReadModel::class);

        return $members;
    }

    private function login(string $email): string
    {
        $this->client->request(
            'POST',
            '/api/auth/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['email' => $email, 'password' => self::PASSWORD], JSON_THROW_ON_ERROR),
        );

        $payload = $this->decode();

        return (string) $payload['token'];
    }

    private function getJson(string $path, string $token): void
    {
        $this->client->request('GET', $path, server: [
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
        ]);
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
