<?php

declare(strict_types=1);

namespace App\Tests\Modules\Agents\Infrastructure\Http;

use DateTimeImmutable;
use Modules\Agents\Domain\AgentMemberRepository;
use Modules\Agents\Domain\AgentRepository;
use Modules\Auth\Domain\User;
use Modules\Auth\Domain\UserRepository;
use Modules\Auth\Infrastructure\Security\HashablePlaintextUser;
use Modules\Organizations\Api\OrganizationsApi;
use Modules\Shared\Kernel\AgentId;
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

    public function testShouldReturn422WhenInstructionsMissing(): void
    {
        $token = $this->seedUserWithOrganization();

        $body = $this->validBody();
        unset($body['instructions']);
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
            'instructions' => 'You are Maja, the PM.',
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

    /**
     * @return array<string, mixed>
     */
    private function decode(): array
    {
        /** @var array<string, mixed> $decoded */
        $decoded = json_decode((string) $this->client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);

        return $decoded;
    }
}
