<?php

declare(strict_types=1);

namespace App\Tests\Modules\Workspaces\Infrastructure\Http;

use DateTimeImmutable;
use Modules\Auth\Domain\User;
use Modules\Auth\Domain\UserRepository;
use Modules\Auth\Infrastructure\Security\HashablePlaintextUser;
use Modules\Organizations\Domain\Organization;
use Modules\Organizations\Domain\OrganizationRepository;
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

final class WorkspacesEndpointTest extends WebTestCase
{
    private const string DEFAULT_EMAIL = 'admin@example.com';

    private const string DEFAULT_PASSWORD = 'secret-pass-long-enough';

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->client->disableReboot();
    }

    public function testShouldReturn401OnListWithoutToken(): void
    {
        $this->client->request('GET', '/api/workspaces');

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturnEmptyListWhenCurrentUserHasNoWorkspaces(): void
    {
        $token = $this->seedAdminAndLogin();

        $this->get('/api/workspaces', $token);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        self::assertSame([], $this->decode());
    }

    public function testShouldListWorkspacesWhereCurrentUserIsMember(): void
    {
        [$userId, $token] = $this->seedAdminAndLoginWithId();
        $this->seedWorkspaceWithOwner($userId, 'Private', 'private');

        $this->get('/api/workspaces', $token);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $payload = $this->decode();
        self::assertCount(1, $payload);
        self::assertSame('Private', $payload[0]['name']);
        self::assertSame('private', $payload[0]['slug']);
        self::assertSame('#8B5CF6', $payload[0]['iconColor']);
        self::assertSame((string) $userId, $payload[0]['ownerId']);
        self::assertSame('owner', $payload[0]['role']);
    }

    public function testShouldReturnRolePerWorkspaceForCurrentUser(): void
    {
        [$adminUserId, $token] = $this->seedAdminAndLoginWithId();
        $ownerId = new UserId(Uuid::v7());
        $this->seedWorkspaceWithOwner($adminUserId, 'Mine', 'mine');
        $shared = $this->seedWorkspaceWithOwner($ownerId, 'Shared', 'shared');
        $this->workspaces()->saveMembership(
            WorkspaceMember::join($shared->id(), $adminUserId, WorkspaceRole::Admin, $this->now()),
        );

        $this->get('/api/workspaces', $token);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $payload = $this->decode();
        self::assertCount(2, $payload);
        $byName = [];
        foreach ($payload as $item) {
            $byName[$item['name']] = $item['role'];
        }

        self::assertSame('owner', $byName['Mine']);
        self::assertSame('admin', $byName['Shared']);
    }

    public function testShouldNotListDeletedWorkspaces(): void
    {
        [$userId, $token] = $this->seedAdminAndLoginWithId();
        $ws = $this->seedWorkspaceWithOwner($userId, 'Old', 'old-ws');
        $ws->softDelete(new DateTimeImmutable('2026-11-06T09:00:00+00:00'));
        $this->workspaces()->saveWorkspace($ws);

        $this->get('/api/workspaces', $token);

        self::assertSame([], $this->decode());
    }

    public function testShouldCreateWorkspaceWithCurrentUserAsOwner(): void
    {
        [$userId, $token] = $this->seedAdminAndLoginWithId();

        $this->postJson('/api/workspaces', ['name' => 'Private'], $token);

        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        $payload = $this->decode();
        self::assertSame('Private', $payload['name']);
        self::assertSame('private', $payload['slug']);
        self::assertSame('PR', $payload['iconInitials']);
        self::assertSame('#8B5CF6', $payload['iconColor']);
        self::assertSame((string) $userId, $payload['ownerId']);
    }

    public function testShouldCreateWorkspaceWithCustomIconColorAndInitials(): void
    {
        $token = $this->seedAdminAndLogin();

        $this->postJson('/api/workspaces', [
            'name' => 'Sport',
            'iconColor' => '#10B981',
            'iconInitials' => 'SP',
        ], $token);

        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        $payload = $this->decode();
        self::assertSame('#10B981', $payload['iconColor']);
        self::assertSame('SP', $payload['iconInitials']);
    }

    public function testShouldReturn401OnCreateWithoutToken(): void
    {
        $this->postJson('/api/workspaces', ['name' => 'Private'], null);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn422OnCreateWithoutName(): void
    {
        $token = $this->seedAdminAndLogin();

        $this->postJson('/api/workspaces', [], $token);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn422OnCreateWithInvalidIconColor(): void
    {
        $token = $this->seedAdminAndLogin();

        $this->postJson('/api/workspaces', [
            'name' => 'X',
            'iconColor' => 'fuchsia',
        ], $token);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldRenameWorkspaceWhenOwner(): void
    {
        [$userId, $token] = $this->seedAdminAndLoginWithId();
        $ws = $this->seedWorkspaceWithOwner($userId, 'Old', 'old-ws');

        $this->patchJson("/api/workspaces/{$ws->id()}", ['name' => 'New'], $token);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $payload = $this->decode();
        self::assertSame('New', $payload['name']);
        self::assertSame('old-ws', $payload['slug']);
    }

    public function testShouldChangeIconWhenOwner(): void
    {
        [$userId, $token] = $this->seedAdminAndLoginWithId();
        $ws = $this->seedWorkspaceWithOwner($userId, 'Private', 'private');

        $this->patchJson("/api/workspaces/{$ws->id()}", [
            'iconColor' => '#06B6D4',
            'iconInitials' => 'XX',
        ], $token);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $payload = $this->decode();
        self::assertSame('#06B6D4', $payload['iconColor']);
        self::assertSame('XX', $payload['iconInitials']);
    }

    public function testShouldAllowPatchFromAdminMember(): void
    {
        [$adminUserId, $token] = $this->seedAdminAndLoginWithId();
        $ownerId = new UserId(Uuid::v7());
        $ws = $this->seedWorkspaceWithOwner($ownerId, 'Shared', 'shared');
        $this->workspaces()->saveMembership(
            WorkspaceMember::join($ws->id(), $adminUserId, WorkspaceRole::Admin, $this->now()),
        );

        $this->patchJson("/api/workspaces/{$ws->id()}", ['name' => 'Shared+'], $token);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn403OnPatchWhenMemberRoleIsMember(): void
    {
        [$adminUserId, $token] = $this->seedAdminAndLoginWithId();
        $ownerId = new UserId(Uuid::v7());
        $ws = $this->seedWorkspaceWithOwner($ownerId, 'Shared', 'shared');
        $this->workspaces()->saveMembership(
            WorkspaceMember::join($ws->id(), $adminUserId, WorkspaceRole::Member, $this->now()),
        );

        $this->patchJson("/api/workspaces/{$ws->id()}", ['name' => 'Nope'], $token);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn404OnPatchWhenWorkspaceDoesNotExist(): void
    {
        $token = $this->seedAdminAndLogin();
        $missing = Uuid::v7()->toRfc4122();

        $this->patchJson("/api/workspaces/{$missing}", ['name' => 'X'], $token);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn403OnPatchWhenNotMember(): void
    {
        $token = $this->seedAdminAndLogin();
        $otherUserId = new UserId(Uuid::v7());
        $ws = $this->seedWorkspaceWithOwner($otherUserId, 'Other', 'other-ws');

        $this->patchJson("/api/workspaces/{$ws->id()}", ['name' => 'X'], $token);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn422OnPatchWithInvalidIconColor(): void
    {
        [$userId, $token] = $this->seedAdminAndLoginWithId();
        $ws = $this->seedWorkspaceWithOwner($userId, 'Private', 'private');

        $this->patchJson("/api/workspaces/{$ws->id()}", ['iconColor' => 'fuchsia'], $token);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldSoftDeleteWorkspaceWhenOwner(): void
    {
        [$userId, $token] = $this->seedAdminAndLoginWithId();
        $ws = $this->seedWorkspaceWithOwner($userId, 'Private', 'private');

        $this->client->request('DELETE', "/api/workspaces/{$ws->id()}", server: $this->auth($token));

        self::assertSame(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());
        $reloaded = $this->workspaces()->findById($ws->id());
        self::assertNotNull($reloaded);
        self::assertTrue($reloaded->isDeleted());
    }

    public function testShouldReturn404OnDeleteWhenWorkspaceDoesNotExist(): void
    {
        $token = $this->seedAdminAndLogin();
        $missing = Uuid::v7()->toRfc4122();

        $this->client->request('DELETE', "/api/workspaces/{$missing}", server: $this->auth($token));

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn403OnDeleteWhenMemberIsAdminNotOwner(): void
    {
        [$adminId, $token] = $this->seedAdminAndLoginWithId();
        $otherOwnerId = new UserId(Uuid::v7());
        $ws = $this->seedWorkspaceWithOwner($otherOwnerId, 'Shared', 'shared');
        $this->workspaces()->saveMembership(
            WorkspaceMember::join($ws->id(), $adminId, WorkspaceRole::Admin, $this->now()),
        );

        $this->client->request('DELETE', "/api/workspaces/{$ws->id()}", server: $this->auth($token));

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    private function seedAdminAndLogin(): string
    {
        $this->seedAdmin(self::DEFAULT_EMAIL, self::DEFAULT_PASSWORD);

        return $this->login(self::DEFAULT_EMAIL, self::DEFAULT_PASSWORD);
    }

    /**
     * @return array{0: UserId, 1: string}
     */
    private function seedAdminAndLoginWithId(): array
    {
        $user = $this->seedAdmin(self::DEFAULT_EMAIL, self::DEFAULT_PASSWORD);
        $token = $this->login(self::DEFAULT_EMAIL, self::DEFAULT_PASSWORD);

        return [$user->id(), $token];
    }

    private function seedAdmin(string $email, string $plaintextPassword): User
    {
        /** @var UserPasswordHasherInterface $hasher */
        $hasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        $passwordHash = $hasher->hashPassword(new HashablePlaintextUser(), $plaintextPassword);

        $user = User::createAdmin(
            new UserId(Uuid::v7()),
            $email,
            $passwordHash,
            $this->now(),
        );

        /** @var UserRepository $users */
        $users = self::getContainer()->get(UserRepository::class);
        $users->save($user);

        $this->seedOrganizationForOwner($user->id());

        return $user;
    }

    private function seedOrganizationForOwner(UserId $ownerId): Organization
    {
        $organization = Organization::create(
            OrganizationId::generate(),
            "Admin's Organization",
            'org-'.substr($ownerId->value->toRfc4122(), 0, 8),
            $ownerId,
            $this->now(),
        );
        /** @var OrganizationRepository $organizations */
        $organizations = self::getContainer()->get(OrganizationRepository::class);
        $organizations->save($organization);

        return $organization;
    }

    private function seedWorkspaceWithOwner(UserId $ownerId, string $name, string $slug): Workspace
    {
        $now = $this->now();
        $workspace = Workspace::create(WorkspaceId::generate(), $name, $slug, '#8B5CF6', $ownerId, new OrganizationId(Uuid::v7()), $now);
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

    private function login(string $email, string $plaintextPassword): string
    {
        $this->postJson('/api/auth/login', ['email' => $email, 'password' => $plaintextPassword], null);
        $payload = $this->decode();

        return (string) $payload['token'];
    }

    private function get(string $path, ?string $token = null): void
    {
        $server = $token !== null ? $this->auth($token) : [];
        $this->client->request('GET', $path, server: $server);
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
     * @return array<string, mixed>|list<array<string, mixed>>
     */
    private function decode(): array
    {
        /** @var array<string, mixed>|list<array<string, mixed>> $decoded */
        $decoded = json_decode((string) $this->client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);

        return $decoded;
    }

    private function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-11-06T09:00:00+00:00');
    }
}
