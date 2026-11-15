<?php

declare(strict_types=1);

namespace App\Tests\Modules\Agents\Infrastructure\Http;

use DateTimeImmutable;
use Modules\Agents\Domain\AgentMember;
use Modules\Agents\Domain\AgentMemberRepository;
use Modules\Agents\Domain\AgentRepository;
use Modules\Agents\Domain\AgentTemplate;
use Modules\Agents\Domain\AgentTemplateRepository;
use Modules\Auth\Domain\User;
use Modules\Auth\Domain\UserRepository;
use Modules\Auth\Infrastructure\Security\HashablePlaintextUser;
use Modules\Organizations\Api\OrganizationsApi;
use Modules\Shared\Kernel\AgentId;
use Modules\Shared\Kernel\AgentTemplateId;
use Modules\Shared\Kernel\OrganizationId;
use Modules\Shared\Kernel\UserId;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;

final class AgentsEndpointTest extends WebTestCase
{
    private const string DEFAULT_EMAIL = 'agent-owner@example.com';

    private const string DEFAULT_PASSWORD = 'secret-pass-long-enough';

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->client->disableReboot();
    }

    public function testShouldReturn401WhenTokenMissing(): void
    {
        $this->postJson('/api/agents', $this->validBody(), null);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn422WhenNameMissing(): void
    {
        $token = $this->seedUserWithOrganization();

        $body = $this->validBody();
        unset($body['name']);
        $this->postJson('/api/agents', $body, $token);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn422WhenRoleMissing(): void
    {
        $token = $this->seedUserWithOrganization();

        $body = $this->validBody();
        unset($body['role']);
        $this->postJson('/api/agents', $body, $token);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn422WhenRoleDescriptionMissing(): void
    {
        $token = $this->seedUserWithOrganization();

        $body = $this->validBody();
        unset($body['roleDescription']);
        $this->postJson('/api/agents', $body, $token);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn422WhenExpertiseMissing(): void
    {
        $token = $this->seedUserWithOrganization();

        $body = $this->validBody();
        unset($body['expertise']);
        $this->postJson('/api/agents', $body, $token);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn422WhenSlugIsNotKebabCase(): void
    {
        $token = $this->seedUserWithOrganization();

        $this->postJson('/api/agents', $this->validBody(['slug' => 'Not Kebab']), $token);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn409WhenOwnerHasNoOrganization(): void
    {
        $this->seedAdmin(self::DEFAULT_EMAIL, self::DEFAULT_PASSWORD);
        $token = $this->login(self::DEFAULT_EMAIL, self::DEFAULT_PASSWORD);

        $this->postJson('/api/agents', $this->validBody(), $token);

        self::assertSame(Response::HTTP_CONFLICT, $this->client->getResponse()->getStatusCode());
        self::assertSame('OWNER_HAS_NO_ORGANIZATION', $this->decode()['error']['code']);
    }

    public function testShouldCreateAgentWithAutoSlugAsCurrentUser(): void
    {
        [$userId, $token] = $this->seedUserWithOrganizationAndId();

        $this->postJson('/api/agents', $this->validBody(['name' => 'Maja PM']), $token);

        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        $payload = $this->decode();
        self::assertSame('Maja PM', $payload['name']);
        self::assertSame('maja-pm', $payload['slug']);
        self::assertSame((string) $userId, $payload['ownerUserId']);
        self::assertTrue($payload['isActive']);
        self::assertNull($payload['sourceTemplateId']);
    }

    public function testShouldCreateAgentWithProvidedSlug(): void
    {
        $token = $this->seedUserWithOrganization();

        $this->postJson('/api/agents', $this->validBody(['slug' => 'viktor']), $token);

        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        self::assertSame('viktor', $this->decode()['slug']);
    }

    public function testShouldReturn409WhenProvidedSlugAlreadyTaken(): void
    {
        $token = $this->seedUserWithOrganization();
        $this->postJson('/api/agents', $this->validBody(['slug' => 'duplicate']), $token);
        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());

        $this->postJson('/api/agents', $this->validBody(['name' => 'Second', 'slug' => 'duplicate']), $token);

        self::assertSame(Response::HTTP_CONFLICT, $this->client->getResponse()->getStatusCode());
        self::assertSame('AGENT_SLUG_ALREADY_TAKEN', $this->decode()['error']['code']);
    }

    public function testShouldAppendRandomSuffixOnAutoSlugClash(): void
    {
        $token = $this->seedUserWithOrganization();
        $this->postJson('/api/agents', $this->validBody(['name' => 'Alpha']), $token);
        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());

        $this->postJson('/api/agents', $this->validBody(['name' => 'Alpha']), $token);

        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        $payload = $this->decode();
        self::assertNotSame('alpha', $payload['slug']);
        self::assertStringStartsWith('alpha-', $payload['slug']);
    }

    public function testShouldReturn409WhenAgentLimitReached(): void
    {
        [$userId, $token] = $this->seedUserWithOrganizationAndId();
        $max = (int) $_ENV['AGENTS_MAX_PER_ORGANIZATION'];
        for ($i = 0; $i < $max; ++$i) {
            $this->postJson('/api/agents', $this->validBody(['name' => 'Agent '.$i, 'slug' => 'agent-'.$i]), $token);
            self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        }

        $this->postJson('/api/agents', $this->validBody(['name' => 'Overflow', 'slug' => 'overflow']), $token);

        self::assertSame(Response::HTTP_CONFLICT, $this->client->getResponse()->getStatusCode());
        self::assertSame('AGENT_LIMIT_REACHED', $this->decode()['error']['code']);
        self::assertSame($max, $this->agents()->countActiveFor(
            $this->organizationIdFor($userId),
        ));
    }

    public function testShouldGrantOwnerAgentMembershipOnCreate(): void
    {
        [$userId, $token] = $this->seedUserWithOrganizationAndId();

        $this->postJson('/api/agents', $this->validBody(), $token);
        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        $agentId = AgentId::fromString((string) $this->decode()['id']);

        self::assertTrue($this->agentMembers()->exists($agentId, $userId));
    }

    public function testShouldReturn401OnListWhenTokenMissing(): void
    {
        $this->getJson('/api/agents', null);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturnEmptyArrayWhenUserHasNoAgents(): void
    {
        $this->seedAdmin(self::DEFAULT_EMAIL, self::DEFAULT_PASSWORD);
        $token = $this->login(self::DEFAULT_EMAIL, self::DEFAULT_PASSWORD);

        $this->getJson('/api/agents', $token);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        self::assertSame([], $this->decodeList());
    }

    public function testShouldReturnOwnedAgentsSortedByName(): void
    {
        [, $token] = $this->seedUserWithOrganizationAndId();
        $this->postJson('/api/agents', $this->validBody(['name' => 'Viktor', 'slug' => 'viktor']), $token);
        $this->postJson('/api/agents', $this->validBody(['name' => 'Maja', 'slug' => 'maja']), $token);

        $this->getJson('/api/agents', $token);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $payload = $this->decodeList();
        self::assertCount(2, $payload);
        self::assertSame(['Maja', 'Viktor'], array_map(static fn (array $a): string => (string) $a['name'], $payload));
    }

    public function testShouldReturnSharedAgentFromDifferentOwner(): void
    {
        [$ownerId, $ownerToken] = $this->seedUserWithOrganizationAndId();
        $this->postJson('/api/agents', $this->validBody(['name' => 'Shared', 'slug' => 'shared']), $ownerToken);
        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        $sharedId = AgentId::fromString((string) $this->decode()['id']);

        $invited = $this->seedAdmin('invited@example.com', 'secret-pass-long-enough');
        $this->agentMembers()->save(AgentMember::grant(
            $sharedId,
            $invited->id(),
            $ownerId,
            new DateTimeImmutable('2026-11-13T15:00:00+00:00'),
        ));
        $invitedToken = $this->login('invited@example.com', 'secret-pass-long-enough');

        $this->getJson('/api/agents', $invitedToken);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $payload = $this->decodeList();
        self::assertCount(1, $payload);
        self::assertSame('Shared', $payload[0]['name']);
        self::assertSame((string) $sharedId, $payload[0]['id']);
    }

    public function testShouldReturn401OnGetWhenTokenMissing(): void
    {
        $this->getJson('/api/agents/'.Uuid::v7()->toRfc4122(), null);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn404OnGetWhenAgentDoesNotExist(): void
    {
        $token = $this->seedUserWithOrganization();

        $this->getJson('/api/agents/'.Uuid::v7()->toRfc4122(), $token);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn200OnGetForOwner(): void
    {
        $token = $this->seedUserWithOrganization();
        $this->postJson('/api/agents', $this->validBody(['name' => 'Owned', 'slug' => 'owned']), $token);
        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        $agentId = (string) $this->decode()['id'];

        $this->getJson('/api/agents/'.$agentId, $token);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $payload = $this->decode();
        self::assertSame($agentId, $payload['id']);
        self::assertSame('Owned', $payload['name']);
        self::assertSame('owned', $payload['slug']);
    }

    public function testShouldReturn200OnGetForSharedMember(): void
    {
        [$ownerId, $ownerToken] = $this->seedUserWithOrganizationAndId();
        $this->postJson('/api/agents', $this->validBody(['name' => 'Shared', 'slug' => 'shared']), $ownerToken);
        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        $sharedId = AgentId::fromString((string) $this->decode()['id']);
        $invited = $this->seedAdmin('invited@example.com', 'secret-pass-long-enough');
        $this->agentMembers()->save(AgentMember::grant(
            $sharedId,
            $invited->id(),
            $ownerId,
            new DateTimeImmutable('2026-11-13T15:00:00+00:00'),
        ));
        $invitedToken = $this->login('invited@example.com', 'secret-pass-long-enough');

        $this->getJson('/api/agents/'.$sharedId, $invitedToken);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        self::assertSame((string) $sharedId, $this->decode()['id']);
        self::assertSame('Shared', $this->decode()['name']);
    }

    public function testShouldReturn403OnGetWhenActorIsNotMember(): void
    {
        [, $ownerToken] = $this->seedUserWithOrganizationAndId();
        $this->postJson('/api/agents', $this->validBody(['name' => 'Private', 'slug' => 'private']), $ownerToken);
        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        $privateId = (string) $this->decode()['id'];
        $this->seedAdmin('foreign@example.com', 'secret-pass-long-enough');
        $foreignToken = $this->login('foreign@example.com', 'secret-pass-long-enough');

        $this->getJson('/api/agents/'.$privateId, $foreignToken);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn401OnUpdateWhenTokenMissing(): void
    {
        $this->putJson('/api/agents/'.Uuid::v7()->toRfc4122(), ['name' => 'any'], null);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn404OnUpdateWhenAgentDoesNotExist(): void
    {
        $token = $this->seedUserWithOrganization();

        $this->putJson('/api/agents/'.Uuid::v7()->toRfc4122(), ['name' => 'any'], $token);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn403OnUpdateWhenForeignUserIsNotOwner(): void
    {
        [, $ownerToken] = $this->seedUserWithOrganizationAndId();
        $this->postJson('/api/agents', $this->validBody(['name' => 'Private', 'slug' => 'private']), $ownerToken);
        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        $privateId = (string) $this->decode()['id'];
        $this->seedAdmin('foreign@example.com', 'secret-pass-long-enough');
        $foreignToken = $this->login('foreign@example.com', 'secret-pass-long-enough');

        $this->putJson('/api/agents/'.$privateId, ['name' => 'Hijacked'], $foreignToken);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn403OnUpdateWhenSharedMemberIsNotOwner(): void
    {
        [$ownerId, $ownerToken] = $this->seedUserWithOrganizationAndId();
        $this->postJson('/api/agents', $this->validBody(['name' => 'Shared', 'slug' => 'shared']), $ownerToken);
        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        $sharedId = AgentId::fromString((string) $this->decode()['id']);
        $invited = $this->seedAdmin('invited@example.com', 'secret-pass-long-enough');
        $this->agentMembers()->save(AgentMember::grant(
            $sharedId,
            $invited->id(),
            $ownerId,
            new DateTimeImmutable('2026-11-13T15:00:00+00:00'),
        ));
        $invitedToken = $this->login('invited@example.com', 'secret-pass-long-enough');

        $this->putJson('/api/agents/'.$sharedId, ['name' => 'Hijacked'], $invitedToken);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn422OnUpdateWhenNameIsBlank(): void
    {
        $token = $this->seedUserWithOrganization();
        $this->postJson('/api/agents', $this->validBody(['name' => 'Target', 'slug' => 'target']), $token);
        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        $agentId = (string) $this->decode()['id'];

        $this->putJson('/api/agents/'.$agentId, ['name' => ''], $token);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn422OnUpdateWhenRoleExceedsMaxLength(): void
    {
        $token = $this->seedUserWithOrganization();
        $this->postJson('/api/agents', $this->validBody(['name' => 'Target', 'slug' => 'target']), $token);
        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        $agentId = (string) $this->decode()['id'];

        $this->putJson('/api/agents/'.$agentId, ['role' => str_repeat('a', 121)], $token);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn200OnUpdateForOwnerWithAllFields(): void
    {
        $token = $this->seedUserWithOrganization();
        $this->postJson('/api/agents', $this->validBody(['name' => 'Maja PM', 'slug' => 'maja-pm']), $token);
        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        $agentId = (string) $this->decode()['id'];

        $this->putJson('/api/agents/'.$agentId, [
            'name' => 'Viktor Architect',
            'role' => 'Architect',
            'roleDescription' => 'You are Viktor.',
            'expertise' => '- Architecture.',
            'workflow' => '- Diagrams first.',
            'style' => '- Precise.',
            'outputFormat' => '- One ADR per decision.',
        ], $token);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $payload = $this->decode();
        self::assertSame('Viktor Architect', $payload['name']);
        self::assertSame('Architect', $payload['role']);
        self::assertSame('You are Viktor.', $payload['roleDescription']);
        self::assertSame('- Architecture.', $payload['expertise']);
        self::assertSame('- Diagrams first.', $payload['workflow']);
        self::assertSame('- Precise.', $payload['style']);
        self::assertSame('- One ADR per decision.', $payload['outputFormat']);
        self::assertSame('maja-pm', $payload['slug']);
    }

    public function testShouldReturn200OnUpdateForOwnerWithOnlyName(): void
    {
        $token = $this->seedUserWithOrganization();
        $this->postJson('/api/agents', $this->validBody(['name' => 'Maja PM', 'slug' => 'maja-pm']), $token);
        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        $agentId = (string) $this->decode()['id'];

        $this->putJson('/api/agents/'.$agentId, ['name' => 'Renamed Maja'], $token);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $payload = $this->decode();
        self::assertSame('Renamed Maja', $payload['name']);
        self::assertSame('Project Manager', $payload['role']);
        self::assertSame('You are Maja, the PM.', $payload['roleDescription']);
        self::assertSame('- Coordination.', $payload['expertise']);
    }

    public function testShouldReturn401OnDeleteWhenTokenMissing(): void
    {
        $this->deleteJson('/api/agents/'.Uuid::v7()->toRfc4122(), null);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn404OnDeleteWhenAgentDoesNotExist(): void
    {
        $token = $this->seedUserWithOrganization();

        $this->deleteJson('/api/agents/'.Uuid::v7()->toRfc4122(), $token);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn403OnDeleteWhenForeignUserIsNotOwner(): void
    {
        [, $ownerToken] = $this->seedUserWithOrganizationAndId();
        $this->postJson('/api/agents', $this->validBody(['name' => 'Private', 'slug' => 'private']), $ownerToken);
        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        $privateId = (string) $this->decode()['id'];
        $this->seedAdmin('foreign@example.com', 'secret-pass-long-enough');
        $foreignToken = $this->login('foreign@example.com', 'secret-pass-long-enough');

        $this->deleteJson('/api/agents/'.$privateId, $foreignToken);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn403OnDeleteWhenSharedMemberIsNotOwner(): void
    {
        [$ownerId, $ownerToken] = $this->seedUserWithOrganizationAndId();
        $this->postJson('/api/agents', $this->validBody(['name' => 'Shared', 'slug' => 'shared']), $ownerToken);
        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        $sharedId = AgentId::fromString((string) $this->decode()['id']);
        $invited = $this->seedAdmin('invited@example.com', 'secret-pass-long-enough');
        $this->agentMembers()->save(AgentMember::grant(
            $sharedId,
            $invited->id(),
            $ownerId,
            new DateTimeImmutable('2026-11-13T15:00:00+00:00'),
        ));
        $invitedToken = $this->login('invited@example.com', 'secret-pass-long-enough');

        $this->deleteJson('/api/agents/'.$sharedId, $invitedToken);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn204OnDeleteForOwner(): void
    {
        $token = $this->seedUserWithOrganization();
        $this->postJson('/api/agents', $this->validBody(['name' => 'Target', 'slug' => 'target']), $token);
        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        $agentId = (string) $this->decode()['id'];

        $this->deleteJson('/api/agents/'.$agentId, $token);

        self::assertSame(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());
        self::assertSame('', (string) $this->client->getResponse()->getContent());
    }

    public function testShouldReturn404OnGetAfterDelete(): void
    {
        $token = $this->seedUserWithOrganization();
        $this->postJson('/api/agents', $this->validBody(['name' => 'Target', 'slug' => 'target']), $token);
        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        $agentId = (string) $this->decode()['id'];
        $this->deleteJson('/api/agents/'.$agentId, $token);
        self::assertSame(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());

        $this->getJson('/api/agents/'.$agentId, $token);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldExcludeSoftDeletedAgentFromList(): void
    {
        $token = $this->seedUserWithOrganization();
        $this->postJson('/api/agents', $this->validBody(['name' => 'Keep', 'slug' => 'keep']), $token);
        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        $this->postJson('/api/agents', $this->validBody(['name' => 'Drop', 'slug' => 'drop']), $token);
        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        $dropId = (string) $this->decode()['id'];

        $this->deleteJson('/api/agents/'.$dropId, $token);
        self::assertSame(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());

        $this->getJson('/api/agents', $token);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $payload = $this->decodeList();
        self::assertCount(1, $payload);
        self::assertSame('Keep', $payload[0]['name']);
    }

    public function testShouldBeIdempotentOnSecondDelete(): void
    {
        $token = $this->seedUserWithOrganization();
        $this->postJson('/api/agents', $this->validBody(['name' => 'Target', 'slug' => 'target']), $token);
        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        $agentId = (string) $this->decode()['id'];
        $this->deleteJson('/api/agents/'.$agentId, $token);
        self::assertSame(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());

        $this->deleteJson('/api/agents/'.$agentId, $token);

        self::assertSame(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn401OnFromTemplateWhenTokenMissing(): void
    {
        $this->postJson('/api/agents/from-template', ['templateSlug' => 'maja'], null);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn422OnFromTemplateWhenTemplateSlugMissing(): void
    {
        $token = $this->seedUserWithOrganization();

        $this->postJson('/api/agents/from-template', [], $token);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn404OnFromTemplateWhenTemplateUnknown(): void
    {
        $token = $this->seedUserWithOrganization();

        $this->postJson('/api/agents/from-template', ['templateSlug' => 'ghost'], $token);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn409OnFromTemplateWhenOwnerHasNoOrganization(): void
    {
        $this->seedAdmin(self::DEFAULT_EMAIL, self::DEFAULT_PASSWORD);
        $token = $this->login(self::DEFAULT_EMAIL, self::DEFAULT_PASSWORD);
        $this->seedTemplate('maja', 'Maja', 'Project Manager');

        $this->postJson('/api/agents/from-template', ['templateSlug' => 'maja'], $token);

        self::assertSame(Response::HTTP_CONFLICT, $this->client->getResponse()->getStatusCode());
        self::assertSame('OWNER_HAS_NO_ORGANIZATION', $this->decode()['error']['code']);
    }

    public function testShouldReturn201OnFromTemplateDeepCopyingAllFields(): void
    {
        [, $token] = $this->seedUserWithOrganizationAndId();
        $template = $this->seedTemplate('maja', 'Maja', 'Project Manager');

        $this->postJson('/api/agents/from-template', ['templateSlug' => 'maja'], $token);

        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        $payload = $this->decode();
        self::assertSame('Maja', $payload['name']);
        self::assertSame('Project Manager', $payload['role']);
        self::assertSame('maja', $payload['slug']);
        self::assertSame((string) $template->id(), $payload['sourceTemplateId']);
        self::assertSame($template->roleDescription(), $payload['roleDescription']);
        self::assertSame($template->expertise(), $payload['expertise']);
        self::assertSame($template->workflow(), $payload['workflow']);
        self::assertSame($template->style(), $payload['style']);
        self::assertSame($template->outputFormat(), $payload['outputFormat']);
    }

    public function testShouldReturn201OnFromTemplateWithNameOverride(): void
    {
        $token = $this->seedUserWithOrganization();
        $this->seedTemplate('maja', 'Maja', 'Project Manager');

        $this->postJson('/api/agents/from-template', [
            'templateSlug' => 'maja',
            'name' => 'Custom Maja',
        ], $token);

        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        $payload = $this->decode();
        self::assertSame('Custom Maja', $payload['name']);
        self::assertSame('custom-maja', $payload['slug']);
    }

    public function testShouldReturn201OnFromTemplateWithSectionOverrides(): void
    {
        $token = $this->seedUserWithOrganization();
        $this->seedTemplate('maja', 'Maja', 'Project Manager');

        $this->postJson('/api/agents/from-template', [
            'templateSlug' => 'maja',
            'roleDescription' => 'Custom role description.',
            'workflow' => '- Custom workflow bullet.',
        ], $token);

        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        $payload = $this->decode();
        self::assertSame('Custom role description.', $payload['roleDescription']);
        self::assertSame('- Custom workflow bullet.', $payload['workflow']);
        self::assertSame('- Expertise bullet.', $payload['expertise']);
    }

    public function testShouldReturn409OnFromTemplateWhenAgentLimitReached(): void
    {
        [$userId, $token] = $this->seedUserWithOrganizationAndId();
        $this->seedTemplate('maja', 'Maja', 'Project Manager');
        $max = (int) $_ENV['AGENTS_MAX_PER_ORGANIZATION'];
        for ($i = 0; $i < $max; ++$i) {
            $this->postJson('/api/agents/from-template', [
                'templateSlug' => 'maja',
                'name' => 'Agent '.$i,
                'slug' => 'agent-'.$i,
            ], $token);
            self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        }

        $this->postJson('/api/agents/from-template', [
            'templateSlug' => 'maja',
            'slug' => 'overflow',
        ], $token);

        self::assertSame(Response::HTTP_CONFLICT, $this->client->getResponse()->getStatusCode());
        self::assertSame('AGENT_LIMIT_REACHED', $this->decode()['error']['code']);
        self::assertSame($max, $this->agents()->countActiveFor(
            $this->organizationIdFor($userId),
        ));
    }

    public function testShouldGrantOwnerAgentMembershipOnFromTemplate(): void
    {
        [$userId, $token] = $this->seedUserWithOrganizationAndId();
        $this->seedTemplate('maja', 'Maja', 'Project Manager');

        $this->postJson('/api/agents/from-template', ['templateSlug' => 'maja'], $token);
        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        $agentId = AgentId::fromString((string) $this->decode()['id']);

        self::assertTrue($this->agentMembers()->exists($agentId, $userId));
    }

    private function seedTemplate(string $slug, string $name, string $role): AgentTemplate
    {
        $template = AgentTemplate::create(
            id: AgentTemplateId::generate(),
            slug: $slug,
            name: $name,
            role: $role,
            roleDescription: 'You are '.$name.'.',
            expertise: '- Expertise bullet.',
            workflow: '- Workflow bullet.',
            style: '- Style bullet.',
            outputFormat: '- Output format bullet.',
            now: new DateTimeImmutable('2026-11-15T00:00:00+00:00'),
        );

        /** @var AgentTemplateRepository $repository */
        $repository = self::getContainer()->get(AgentTemplateRepository::class);
        $repository->save($template);

        return $template;
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private function validBody(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Maja PM',
            'role' => 'Project Manager',
            'roleDescription' => 'You are Maja, the PM.',
            'expertise' => '- Coordination.',
            'workflow' => '- Open with briefing.',
            'style' => '- Terse and direct.',
            'outputFormat' => '- One-line confirmations.',
        ], $overrides);
    }

    private function seedUserWithOrganization(): string
    {
        [, $token] = $this->seedUserWithOrganizationAndId();

        return $token;
    }

    /**
     * @return array{0: UserId, 1: string}
     */
    private function seedUserWithOrganizationAndId(): array
    {
        $user = $this->seedAdmin(self::DEFAULT_EMAIL, self::DEFAULT_PASSWORD);
        $token = $this->login(self::DEFAULT_EMAIL, self::DEFAULT_PASSWORD);
        $this->postJson('/api/organizations', ['name' => 'Acme'], $token);
        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());

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
            new DateTimeImmutable('2026-11-13T15:00:00+00:00'),
        );

        /** @var UserRepository $users */
        $users = self::getContainer()->get(UserRepository::class);
        $users->save($user);

        return $user;
    }

    private function agents(): AgentRepository
    {
        /** @var AgentRepository $repo */
        $repo = self::getContainer()->get(AgentRepository::class);

        return $repo;
    }

    private function agentMembers(): AgentMemberRepository
    {
        /** @var AgentMemberRepository $repo */
        $repo = self::getContainer()->get(AgentMemberRepository::class);

        return $repo;
    }

    private function organizationIdFor(UserId $userId): OrganizationId
    {
        /** @var OrganizationsApi $api */
        $api = self::getContainer()->get(OrganizationsApi::class);
        $view = $api->findByOwner($userId);
        self::assertNotNull($view);

        return $view->id;
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
     * @return array<string, mixed>
     */
    private function decode(): array
    {
        /** @var array<string, mixed> $decoded */
        $decoded = json_decode((string) $this->client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);

        return $decoded;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function decodeList(): array
    {
        /** @var list<array<string, mixed>> $decoded */
        $decoded = json_decode((string) $this->client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);

        return $decoded;
    }
}
