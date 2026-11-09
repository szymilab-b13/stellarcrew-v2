<?php

declare(strict_types=1);

namespace App\Tests\Modules\Projects\Infrastructure\Http;

use DateTimeImmutable;
use Modules\Auth\Domain\User;
use Modules\Auth\Domain\UserRepository;
use Modules\Auth\Infrastructure\Security\HashablePlaintextUser;
use Modules\Projects\Domain\ProjectRepository;
use Modules\Shared\Kernel\ProjectId;
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

    public function testShouldReturn401OnListWithoutToken(): void
    {
        $workspaceId = Uuid::v7()->toRfc4122();

        $this->getJson("/api/workspaces/{$workspaceId}/projects", null);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn404OnListWhenWorkspaceDoesNotExist(): void
    {
        $token = $this->seedAdminAndLogin();
        $missing = Uuid::v7()->toRfc4122();

        $this->getJson("/api/workspaces/{$missing}/projects", $token);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn403OnListWhenCallerIsNotWorkspaceMember(): void
    {
        $token = $this->seedAdminAndLogin();
        $otherOwnerId = new UserId(Uuid::v7());
        $ws = $this->seedWorkspaceWithOwner($otherOwnerId, 'Other', 'other-ws');

        $this->getJson("/api/workspaces/{$ws->id()}/projects", $token);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturnEmptyListWhenWorkspaceHasNoProjects(): void
    {
        [$userId, $token] = $this->seedAdminAndLoginWithId();
        $ws = $this->seedWorkspaceWithOwner($userId, 'Mine', 'mine');

        $this->getJson("/api/workspaces/{$ws->id()}/projects", $token);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $payload = $this->decode();
        self::assertSame(['items' => []], $payload);
    }

    public function testShouldReturnProjectsOfWorkspaceAsOwner(): void
    {
        [$userId, $token] = $this->seedAdminAndLoginWithId();
        $ws = $this->seedWorkspaceWithOwner($userId, 'Mine', 'mine');
        $this->postJson("/api/workspaces/{$ws->id()}/projects", ['name' => 'Alpha'], $token);
        $this->postJson("/api/workspaces/{$ws->id()}/projects", ['name' => 'Beta'], $token);

        $this->getJson("/api/workspaces/{$ws->id()}/projects", $token);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $payload = $this->decode();
        self::assertCount(2, $payload['items']);
        $slugs = array_map(static fn (array $item): string => (string) $item['slug'], $payload['items']);
        self::assertSame(['alpha', 'beta'], $slugs);
        self::assertSame((string) $ws->id(), $payload['items'][0]['workspaceId']);
    }

    public function testShouldAllowGuestMemberToListProjects(): void
    {
        [$ownerId, $ownerToken] = $this->seedAdminAndLoginWithId();
        $ws = $this->seedWorkspaceWithOwner($ownerId, 'Shared', 'shared');
        $this->postJson("/api/workspaces/{$ws->id()}/projects", ['name' => 'Alpha'], $ownerToken);

        $guestEmail = 'guest@example.com';
        $guest = $this->seedAdmin($guestEmail, self::DEFAULT_PASSWORD);
        $this->workspaces()->saveMembership(
            WorkspaceMember::join($ws->id(), $guest->id(), WorkspaceRole::Guest, $this->now()),
        );
        $guestToken = $this->login($guestEmail, self::DEFAULT_PASSWORD);

        $this->getJson("/api/workspaces/{$ws->id()}/projects", $guestToken);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $payload = $this->decode();
        self::assertCount(1, $payload['items']);
        self::assertSame('alpha', $payload['items'][0]['slug']);
    }

    public function testShouldReturn401OnGetWithoutToken(): void
    {
        $projectId = Uuid::v7()->toRfc4122();

        $this->getJson("/api/projects/{$projectId}", null);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn404OnGetWhenProjectDoesNotExist(): void
    {
        $token = $this->seedAdminAndLogin();
        $missing = Uuid::v7()->toRfc4122();

        $this->getJson("/api/projects/{$missing}", $token);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn403OnGetWhenCallerIsNotWorkspaceMember(): void
    {
        $token = $this->seedAdminAndLogin();
        $otherOwnerId = new UserId(Uuid::v7());
        $ws = $this->seedWorkspaceWithOwner($otherOwnerId, 'Other', 'other-ws');
        $otherOwner = $this->seedAdmin('other@example.com', self::DEFAULT_PASSWORD);
        $otherToken = $this->login('other@example.com', self::DEFAULT_PASSWORD);
        $this->workspaces()->saveMembership(
            WorkspaceMember::join($ws->id(), $otherOwner->id(), WorkspaceRole::Owner, $this->now()),
        );
        $this->postJson("/api/workspaces/{$ws->id()}/projects", ['name' => 'Alpha'], $otherToken);
        $created = $this->decode();

        $this->getJson("/api/projects/{$created['id']}", $token);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturnProjectAsOwner(): void
    {
        [$userId, $token] = $this->seedAdminAndLoginWithId();
        $ws = $this->seedWorkspaceWithOwner($userId, 'Mine', 'mine');
        $this->postJson("/api/workspaces/{$ws->id()}/projects", [
            'name' => 'Talent Bridge',
            'description' => 'Flagship',
        ], $token);
        $created = $this->decode();

        $this->getJson("/api/projects/{$created['id']}", $token);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $payload = $this->decode();
        self::assertSame($created['id'], $payload['id']);
        self::assertSame((string) $ws->id(), $payload['workspaceId']);
        self::assertSame('Talent Bridge', $payload['name']);
        self::assertSame('talent-bridge', $payload['slug']);
        self::assertSame('Flagship', $payload['description']);
    }

    public function testShouldReturnProjectAsGuestMember(): void
    {
        [$ownerId, $ownerToken] = $this->seedAdminAndLoginWithId();
        $ws = $this->seedWorkspaceWithOwner($ownerId, 'Shared', 'shared');
        $this->postJson("/api/workspaces/{$ws->id()}/projects", ['name' => 'Alpha'], $ownerToken);
        $created = $this->decode();

        $guestEmail = 'guest@example.com';
        $guest = $this->seedAdmin($guestEmail, self::DEFAULT_PASSWORD);
        $this->workspaces()->saveMembership(
            WorkspaceMember::join($ws->id(), $guest->id(), WorkspaceRole::Guest, $this->now()),
        );
        $guestToken = $this->login($guestEmail, self::DEFAULT_PASSWORD);

        $this->getJson("/api/projects/{$created['id']}", $guestToken);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $payload = $this->decode();
        self::assertSame('alpha', $payload['slug']);
    }

    public function testShouldReturn404OnGetWhenProjectIsSoftDeleted(): void
    {
        [$userId, $token] = $this->seedAdminAndLoginWithId();
        $ws = $this->seedWorkspaceWithOwner($userId, 'Mine', 'mine');
        $this->postJson("/api/workspaces/{$ws->id()}/projects", ['name' => 'Alpha'], $token);
        $created = $this->decode();
        $project = $this->projects()->findById(ProjectId::fromString((string) $created['id']));
        self::assertNotNull($project);
        $project->softDelete($this->now());
        $this->projects()->save($project);

        $this->getJson("/api/projects/{$created['id']}", $token);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn401OnUpdateWithoutToken(): void
    {
        $projectId = Uuid::v7()->toRfc4122();

        $this->putJson("/api/projects/{$projectId}", ['name' => 'X'], null);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn404OnUpdateWhenProjectDoesNotExist(): void
    {
        $token = $this->seedAdminAndLogin();
        $missing = Uuid::v7()->toRfc4122();

        $this->putJson("/api/projects/{$missing}", ['name' => 'X'], $token);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn404OnUpdateWhenProjectIsSoftDeleted(): void
    {
        [$userId, $token] = $this->seedAdminAndLoginWithId();
        $ws = $this->seedWorkspaceWithOwner($userId, 'Mine', 'mine');
        $this->postJson("/api/workspaces/{$ws->id()}/projects", ['name' => 'Alpha'], $token);
        $created = $this->decode();
        $project = $this->projects()->findById(ProjectId::fromString((string) $created['id']));
        self::assertNotNull($project);
        $project->softDelete($this->now());
        $this->projects()->save($project);

        $this->putJson("/api/projects/{$created['id']}", ['name' => 'Renamed'], $token);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn403OnUpdateWhenCallerIsNotWorkspaceMember(): void
    {
        [, $token] = $this->seedAdminAndLoginWithId();
        $otherOwnerEmail = 'other@example.com';
        $otherOwner = $this->seedAdmin($otherOwnerEmail, self::DEFAULT_PASSWORD);
        $otherToken = $this->login($otherOwnerEmail, self::DEFAULT_PASSWORD);
        $ws = $this->seedWorkspaceWithOwner($otherOwner->id(), 'Other', 'other-ws');
        $this->postJson("/api/workspaces/{$ws->id()}/projects", ['name' => 'Alpha'], $otherToken);
        $created = $this->decode();

        $this->putJson("/api/projects/{$created['id']}", ['name' => 'Renamed'], $token);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn403OnUpdateWhenMemberRoleIsGuest(): void
    {
        [$ownerId, $ownerToken] = $this->seedAdminAndLoginWithId();
        $ws = $this->seedWorkspaceWithOwner($ownerId, 'Shared', 'shared');
        $this->postJson("/api/workspaces/{$ws->id()}/projects", ['name' => 'Alpha'], $ownerToken);
        $created = $this->decode();

        $guestEmail = 'guest@example.com';
        $guest = $this->seedAdmin($guestEmail, self::DEFAULT_PASSWORD);
        $this->workspaces()->saveMembership(
            WorkspaceMember::join($ws->id(), $guest->id(), WorkspaceRole::Guest, $this->now()),
        );
        $guestToken = $this->login($guestEmail, self::DEFAULT_PASSWORD);

        $this->putJson("/api/projects/{$created['id']}", ['name' => 'Renamed'], $guestToken);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn422OnUpdateWhenSlugIsNotKebabCase(): void
    {
        [$userId, $token] = $this->seedAdminAndLoginWithId();
        $ws = $this->seedWorkspaceWithOwner($userId, 'Mine', 'mine');
        $this->postJson("/api/workspaces/{$ws->id()}/projects", ['name' => 'Alpha'], $token);
        $created = $this->decode();

        $this->putJson("/api/projects/{$created['id']}", ['slug' => 'Not Kebab'], $token);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn422OnUpdateWhenNameIsBlank(): void
    {
        [$userId, $token] = $this->seedAdminAndLoginWithId();
        $ws = $this->seedWorkspaceWithOwner($userId, 'Mine', 'mine');
        $this->postJson("/api/workspaces/{$ws->id()}/projects", ['name' => 'Alpha'], $token);
        $created = $this->decode();

        $this->putJson("/api/projects/{$created['id']}", ['name' => ''], $token);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldUpdateNameAsOwner(): void
    {
        [$userId, $token] = $this->seedAdminAndLoginWithId();
        $ws = $this->seedWorkspaceWithOwner($userId, 'Mine', 'mine');
        $this->postJson("/api/workspaces/{$ws->id()}/projects", ['name' => 'Alpha'], $token);
        $created = $this->decode();

        $this->putJson("/api/projects/{$created['id']}", ['name' => 'Renamed'], $token);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $payload = $this->decode();
        self::assertSame('Renamed', $payload['name']);
        self::assertSame('alpha', $payload['slug']);
    }

    public function testShouldUpdateSlugAsOwner(): void
    {
        [$userId, $token] = $this->seedAdminAndLoginWithId();
        $ws = $this->seedWorkspaceWithOwner($userId, 'Mine', 'mine');
        $this->postJson("/api/workspaces/{$ws->id()}/projects", ['name' => 'Alpha'], $token);
        $created = $this->decode();

        $this->putJson("/api/projects/{$created['id']}", ['slug' => 'brand-new'], $token);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $payload = $this->decode();
        self::assertSame('brand-new', $payload['slug']);
    }

    public function testShouldUpdateDescriptionAsOwner(): void
    {
        [$userId, $token] = $this->seedAdminAndLoginWithId();
        $ws = $this->seedWorkspaceWithOwner($userId, 'Mine', 'mine');
        $this->postJson("/api/workspaces/{$ws->id()}/projects", ['name' => 'Alpha'], $token);
        $created = $this->decode();

        $this->putJson("/api/projects/{$created['id']}", ['description' => 'New scope'], $token);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $payload = $this->decode();
        self::assertSame('New scope', $payload['description']);
    }

    public function testShouldUpdateAllThreeFieldsAtOnce(): void
    {
        [$userId, $token] = $this->seedAdminAndLoginWithId();
        $ws = $this->seedWorkspaceWithOwner($userId, 'Mine', 'mine');
        $this->postJson("/api/workspaces/{$ws->id()}/projects", ['name' => 'Alpha'], $token);
        $created = $this->decode();

        $this->putJson("/api/projects/{$created['id']}", [
            'name' => 'Renamed',
            'slug' => 'renamed-slug',
            'description' => 'Full update',
        ], $token);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $payload = $this->decode();
        self::assertSame('Renamed', $payload['name']);
        self::assertSame('renamed-slug', $payload['slug']);
        self::assertSame('Full update', $payload['description']);
    }

    public function testShouldAllowMemberRoleToUpdateProject(): void
    {
        [$ownerId, $ownerToken] = $this->seedAdminAndLoginWithId();
        $ws = $this->seedWorkspaceWithOwner($ownerId, 'Shared', 'shared');
        $this->postJson("/api/workspaces/{$ws->id()}/projects", ['name' => 'Alpha'], $ownerToken);
        $created = $this->decode();

        $memberEmail = 'member@example.com';
        $member = $this->seedAdmin($memberEmail, self::DEFAULT_PASSWORD);
        $this->workspaces()->saveMembership(
            WorkspaceMember::join($ws->id(), $member->id(), WorkspaceRole::Member, $this->now()),
        );
        $memberToken = $this->login($memberEmail, self::DEFAULT_PASSWORD);

        $this->putJson("/api/projects/{$created['id']}", ['name' => 'Renamed'], $memberToken);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldAppendRandomSuffixOnUpdateWhenNewSlugTakenByAnotherProject(): void
    {
        [$userId, $token] = $this->seedAdminAndLoginWithId();
        $ws = $this->seedWorkspaceWithOwner($userId, 'Mine', 'mine');
        $this->postJson("/api/workspaces/{$ws->id()}/projects", ['name' => 'Alpha'], $token);
        $this->postJson("/api/workspaces/{$ws->id()}/projects", ['name' => 'Beta'], $token);
        $beta = $this->decode();

        $this->putJson("/api/projects/{$beta['id']}", ['slug' => 'alpha'], $token);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $payload = $this->decode();
        self::assertNotSame('alpha', $payload['slug']);
        self::assertStringStartsWith('alpha-', $payload['slug']);
    }

    public function testShouldReturn401OnDeleteWithoutToken(): void
    {
        $projectId = Uuid::v7()->toRfc4122();

        $this->deleteJson("/api/projects/{$projectId}", null);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn404OnDeleteWhenProjectDoesNotExist(): void
    {
        $token = $this->seedAdminAndLogin();
        $missing = Uuid::v7()->toRfc4122();

        $this->deleteJson("/api/projects/{$missing}", $token);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn404OnDeleteWhenProjectAlreadySoftDeleted(): void
    {
        [$userId, $token] = $this->seedAdminAndLoginWithId();
        $ws = $this->seedWorkspaceWithOwner($userId, 'Mine', 'mine');
        $this->postJson("/api/workspaces/{$ws->id()}/projects", ['name' => 'Alpha'], $token);
        $created = $this->decode();
        $project = $this->projects()->findById(ProjectId::fromString((string) $created['id']));
        self::assertNotNull($project);
        $project->softDelete($this->now());
        $this->projects()->save($project);

        $this->deleteJson("/api/projects/{$created['id']}", $token);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn403OnDeleteWhenCallerIsNotWorkspaceMember(): void
    {
        [, $token] = $this->seedAdminAndLoginWithId();
        $otherOwnerEmail = 'other@example.com';
        $otherOwner = $this->seedAdmin($otherOwnerEmail, self::DEFAULT_PASSWORD);
        $otherToken = $this->login($otherOwnerEmail, self::DEFAULT_PASSWORD);
        $ws = $this->seedWorkspaceWithOwner($otherOwner->id(), 'Other', 'other-ws');
        $this->postJson("/api/workspaces/{$ws->id()}/projects", ['name' => 'Alpha'], $otherToken);
        $created = $this->decode();

        $this->deleteJson("/api/projects/{$created['id']}", $token);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn403OnDeleteWhenMemberRoleIsGuest(): void
    {
        [$ownerId, $ownerToken] = $this->seedAdminAndLoginWithId();
        $ws = $this->seedWorkspaceWithOwner($ownerId, 'Shared', 'shared');
        $this->postJson("/api/workspaces/{$ws->id()}/projects", ['name' => 'Alpha'], $ownerToken);
        $created = $this->decode();

        $guestEmail = 'guest@example.com';
        $guest = $this->seedAdmin($guestEmail, self::DEFAULT_PASSWORD);
        $this->workspaces()->saveMembership(
            WorkspaceMember::join($ws->id(), $guest->id(), WorkspaceRole::Guest, $this->now()),
        );
        $guestToken = $this->login($guestEmail, self::DEFAULT_PASSWORD);

        $this->deleteJson("/api/projects/{$created['id']}", $guestToken);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldSoftDeleteProjectAsOwner(): void
    {
        [$userId, $token] = $this->seedAdminAndLoginWithId();
        $ws = $this->seedWorkspaceWithOwner($userId, 'Mine', 'mine');
        $this->postJson("/api/workspaces/{$ws->id()}/projects", ['name' => 'Alpha'], $token);
        $created = $this->decode();

        $this->deleteJson("/api/projects/{$created['id']}", $token);

        self::assertSame(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());
        $project = $this->projects()->findById(ProjectId::fromString((string) $created['id']));
        self::assertNotNull($project);
        self::assertTrue($project->isDeleted());
    }

    public function testShouldAllowMemberRoleToDeleteProject(): void
    {
        [$ownerId, $ownerToken] = $this->seedAdminAndLoginWithId();
        $ws = $this->seedWorkspaceWithOwner($ownerId, 'Shared', 'shared');
        $this->postJson("/api/workspaces/{$ws->id()}/projects", ['name' => 'Alpha'], $ownerToken);
        $created = $this->decode();

        $memberEmail = 'member@example.com';
        $member = $this->seedAdmin($memberEmail, self::DEFAULT_PASSWORD);
        $this->workspaces()->saveMembership(
            WorkspaceMember::join($ws->id(), $member->id(), WorkspaceRole::Member, $this->now()),
        );
        $memberToken = $this->login($memberEmail, self::DEFAULT_PASSWORD);

        $this->deleteJson("/api/projects/{$created['id']}", $memberToken);

        self::assertSame(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn401OnRestoreWithoutToken(): void
    {
        $projectId = Uuid::v7()->toRfc4122();

        $this->postJson("/api/projects/{$projectId}/restore", [], null);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn404OnRestoreWhenProjectDoesNotExist(): void
    {
        $token = $this->seedAdminAndLogin();
        $missing = Uuid::v7()->toRfc4122();

        $this->postJson("/api/projects/{$missing}/restore", [], $token);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn403OnRestoreWhenCallerIsNotWorkspaceMember(): void
    {
        [, $token] = $this->seedAdminAndLoginWithId();
        $otherOwnerEmail = 'other@example.com';
        $otherOwner = $this->seedAdmin($otherOwnerEmail, self::DEFAULT_PASSWORD);
        $otherToken = $this->login($otherOwnerEmail, self::DEFAULT_PASSWORD);
        $ws = $this->seedWorkspaceWithOwner($otherOwner->id(), 'Other', 'other-ws');
        $this->postJson("/api/workspaces/{$ws->id()}/projects", ['name' => 'Alpha'], $otherToken);
        $created = $this->decode();
        $project = $this->projects()->findById(ProjectId::fromString((string) $created['id']));
        self::assertNotNull($project);
        $project->softDelete($this->now());
        $this->projects()->save($project);

        $this->postJson("/api/projects/{$created['id']}/restore", [], $token);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn403OnRestoreWhenMemberRoleIsGuest(): void
    {
        [$ownerId, $ownerToken] = $this->seedAdminAndLoginWithId();
        $ws = $this->seedWorkspaceWithOwner($ownerId, 'Shared', 'shared');
        $this->postJson("/api/workspaces/{$ws->id()}/projects", ['name' => 'Alpha'], $ownerToken);
        $created = $this->decode();
        $project = $this->projects()->findById(ProjectId::fromString((string) $created['id']));
        self::assertNotNull($project);
        $project->softDelete($this->now());
        $this->projects()->save($project);

        $guestEmail = 'guest@example.com';
        $guest = $this->seedAdmin($guestEmail, self::DEFAULT_PASSWORD);
        $this->workspaces()->saveMembership(
            WorkspaceMember::join($ws->id(), $guest->id(), WorkspaceRole::Guest, $this->now()),
        );
        $guestToken = $this->login($guestEmail, self::DEFAULT_PASSWORD);

        $this->postJson("/api/projects/{$created['id']}/restore", [], $guestToken);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldRestoreSoftDeletedProjectAsOwner(): void
    {
        [$userId, $token] = $this->seedAdminAndLoginWithId();
        $ws = $this->seedWorkspaceWithOwner($userId, 'Mine', 'mine');
        $this->postJson("/api/workspaces/{$ws->id()}/projects", ['name' => 'Alpha'], $token);
        $created = $this->decode();
        $project = $this->projects()->findById(ProjectId::fromString((string) $created['id']));
        self::assertNotNull($project);
        $project->softDelete($this->now());
        $this->projects()->save($project);

        $this->postJson("/api/projects/{$created['id']}/restore", [], $token);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $payload = $this->decode();
        self::assertSame($created['id'], $payload['id']);
        self::assertSame('alpha', $payload['slug']);
        $stored = $this->projects()->findById(ProjectId::fromString((string) $created['id']));
        self::assertNotNull($stored);
        self::assertFalse($stored->isDeleted());
    }

    public function testShouldReturnActiveProjectWhenRestoreCalledOnNonDeletedProject(): void
    {
        [$userId, $token] = $this->seedAdminAndLoginWithId();
        $ws = $this->seedWorkspaceWithOwner($userId, 'Mine', 'mine');
        $this->postJson("/api/workspaces/{$ws->id()}/projects", ['name' => 'Alpha'], $token);
        $created = $this->decode();

        $this->postJson("/api/projects/{$created['id']}/restore", [], $token);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $payload = $this->decode();
        self::assertSame($created['id'], $payload['id']);
        self::assertSame('alpha', $payload['slug']);
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

    private function getJson(string $path, ?string $token): void
    {
        $server = ['CONTENT_TYPE' => 'application/json'];
        if ($token !== null) {
            $server['HTTP_AUTHORIZATION'] = 'Bearer '.$token;
        }

        $this->client->request('GET', $path, server: $server);
    }

    /**
     * @param array<string, mixed> $body
     */
    private function putJson(string $path, array $body, ?string $token): void
    {
        $server = ['CONTENT_TYPE' => 'application/json'];
        if ($token !== null) {
            $server['HTTP_AUTHORIZATION'] = 'Bearer '.$token;
        }

        $this->client->request('PUT', $path, server: $server, content: json_encode($body, JSON_THROW_ON_ERROR));
    }

    private function deleteJson(string $path, ?string $token): void
    {
        $server = ['CONTENT_TYPE' => 'application/json'];
        if ($token !== null) {
            $server['HTTP_AUTHORIZATION'] = 'Bearer '.$token;
        }

        $this->client->request('DELETE', $path, server: $server);
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
