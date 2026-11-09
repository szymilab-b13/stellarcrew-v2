<?php

declare(strict_types=1);

namespace App\Tests\Modules\Projects\Infrastructure\Http;

use DateTimeImmutable;
use Modules\Auth\Domain\User;
use Modules\Auth\Domain\UserRepository;
use Modules\Auth\Infrastructure\Security\HashablePlaintextUser;
use Modules\Projects\Domain\ProjectRepository;
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

final class ProjectsEndpointTest extends WebTestCase
{
    private const string DEFAULT_EMAIL = 'admin@example.com';

    private const string DEFAULT_PASSWORD = 'secret-pass-long-enough';

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->client->disableReboot();
    }

    public function testShouldReturn401OnCreateWithoutToken(): void
    {
        $workspaceId = Uuid::v7()->toRfc4122();

        $this->postJson("/api/workspaces/{$workspaceId}/projects", ['name' => 'Alpha'], null);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn404WhenWorkspaceDoesNotExist(): void
    {
        $token = $this->seedAdminAndLogin();
        $missing = Uuid::v7()->toRfc4122();

        $this->postJson("/api/workspaces/{$missing}/projects", ['name' => 'Alpha'], $token);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn403WhenCallerIsNotWorkspaceMember(): void
    {
        $token = $this->seedAdminAndLogin();
        $otherOwnerId = new UserId(Uuid::v7());
        $ws = $this->seedWorkspaceWithOwner($otherOwnerId, 'Other', 'other-ws');

        $this->postJson("/api/workspaces/{$ws->id()}/projects", ['name' => 'Alpha'], $token);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn422WhenNameMissing(): void
    {
        [$userId, $token] = $this->seedAdminAndLoginWithId();
        $ws = $this->seedWorkspaceWithOwner($userId, 'Mine', 'mine');

        $this->postJson("/api/workspaces/{$ws->id()}/projects", [], $token);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn422WhenSlugIsNotKebabCase(): void
    {
        [$userId, $token] = $this->seedAdminAndLoginWithId();
        $ws = $this->seedWorkspaceWithOwner($userId, 'Mine', 'mine');

        $this->postJson("/api/workspaces/{$ws->id()}/projects", [
            'name' => 'Alpha',
            'slug' => 'Not Kebab',
        ], $token);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldCreateProjectWithAutoSlugWhenOwner(): void
    {
        [$userId, $token] = $this->seedAdminAndLoginWithId();
        $ws = $this->seedWorkspaceWithOwner($userId, 'Mine', 'mine');

        $this->postJson("/api/workspaces/{$ws->id()}/projects", ['name' => 'Talent Bridge'], $token);

        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        $payload = $this->decode();
        self::assertSame('Talent Bridge', $payload['name']);
        self::assertSame('talent-bridge', $payload['slug']);
        self::assertNull($payload['description']);
        self::assertSame((string) $ws->id(), $payload['workspaceId']);
        self::assertSame(1, $this->projects()->findByWorkspace($ws->id()) === [] ? 0 : 1);
    }

    public function testShouldCreateProjectWithProvidedSlugAndDescription(): void
    {
        [$userId, $token] = $this->seedAdminAndLoginWithId();
        $ws = $this->seedWorkspaceWithOwner($userId, 'Mine', 'mine');

        $this->postJson("/api/workspaces/{$ws->id()}/projects", [
            'name' => 'MVP',
            'slug' => 'mvp-v1',
            'description' => 'First milestone',
        ], $token);

        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        $payload = $this->decode();
        self::assertSame('mvp-v1', $payload['slug']);
        self::assertSame('First milestone', $payload['description']);
    }

    public function testShouldAllowMemberWithRoleMemberToCreateProject(): void
    {
        [$adminUserId, $token] = $this->seedAdminAndLoginWithId();
        $ownerId = new UserId(Uuid::v7());
        $ws = $this->seedWorkspaceWithOwner($ownerId, 'Shared', 'shared');
        $this->workspaces()->saveMembership(
            WorkspaceMember::join($ws->id(), $adminUserId, WorkspaceRole::Member, $this->now()),
        );

        $this->postJson("/api/workspaces/{$ws->id()}/projects", ['name' => 'Alpha'], $token);

        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn403WhenMemberRoleIsGuest(): void
    {
        [$adminUserId, $token] = $this->seedAdminAndLoginWithId();
        $ownerId = new UserId(Uuid::v7());
        $ws = $this->seedWorkspaceWithOwner($ownerId, 'Shared', 'shared');
        $this->workspaces()->saveMembership(
            WorkspaceMember::join($ws->id(), $adminUserId, WorkspaceRole::Guest, $this->now()),
        );

        $this->postJson("/api/workspaces/{$ws->id()}/projects", ['name' => 'Alpha'], $token);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldAppendRandomSuffixWhenSlugAlreadyTakenInWorkspace(): void
    {
        [$userId, $token] = $this->seedAdminAndLoginWithId();
        $ws = $this->seedWorkspaceWithOwner($userId, 'Mine', 'mine');

        $this->postJson("/api/workspaces/{$ws->id()}/projects", ['name' => 'Alpha'], $token);
        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());

        $this->postJson("/api/workspaces/{$ws->id()}/projects", ['name' => 'Alpha'], $token);

        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        $payload = $this->decode();
        self::assertNotSame('alpha', $payload['slug']);
        self::assertStringStartsWith('alpha-', $payload['slug']);
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

        return $user;
    }

    private function seedWorkspaceWithOwner(UserId $ownerId, string $name, string $slug): Workspace
    {
        $now = $this->now();
        $workspace = Workspace::create(WorkspaceId::generate(), $name, $slug, '#8B5CF6', $ownerId, $now);
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

    private function projects(): ProjectRepository
    {
        /** @var ProjectRepository $repo */
        $repo = self::getContainer()->get(ProjectRepository::class);

        return $repo;
    }

    private function login(string $email, string $plaintextPassword): string
    {
        $this->postJson('/api/auth/login', ['email' => $email, 'password' => $plaintextPassword], null);
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
        return new DateTimeImmutable('2026-11-09T10:00:00+00:00');
    }
}
