<?php

declare(strict_types=1);

namespace App\Tests\Modules\Organizations\Infrastructure\Http;

use DateTimeImmutable;
use Modules\Auth\Domain\User;
use Modules\Auth\Domain\UserRepository;
use Modules\Auth\Infrastructure\Security\HashablePlaintextUser;
use Modules\Organizations\Domain\OrganizationRepository;
use Modules\Shared\Kernel\OrganizationId;
use Modules\Shared\Kernel\UserId;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;

final class OrganizationsEndpointTest extends WebTestCase
{
    private const string DEFAULT_EMAIL = 'org-admin@example.com';

    private const string DEFAULT_PASSWORD = 'secret-pass-long-enough';

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->client->disableReboot();
    }

    public function testShouldReturn401OnCreateWithoutToken(): void
    {
        $this->postJson('/api/organizations', ['name' => 'Acme'], null);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn422WhenNameMissing(): void
    {
        $token = $this->seedAdminAndLogin();

        $this->postJson('/api/organizations', [], $token);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn422WhenSlugIsNotKebabCase(): void
    {
        $token = $this->seedAdminAndLogin();

        $this->postJson('/api/organizations', ['name' => 'Acme', 'slug' => 'Not Kebab'], $token);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldCreateOrganizationWithAutoSlugAsCurrentUser(): void
    {
        [$userId, $token] = $this->seedAdminAndLoginWithId();

        $this->postJson('/api/organizations', ['name' => "Alice's Workspace"], $token);

        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        $payload = $this->decode();
        self::assertSame("Alice's Workspace", $payload['name']);
        self::assertSame('alice-s-workspace', $payload['slug']);
        self::assertSame((string) $userId, $payload['ownerUserId']);

        $stored = $this->organizations()->findById(OrganizationId::fromString((string) $payload['id']));
        self::assertNotNull($stored);
        self::assertTrue($stored->ownerUserId()->equals($userId));
    }

    public function testShouldCreateOrganizationWithProvidedSlug(): void
    {
        $token = $this->seedAdminAndLogin();

        $this->postJson('/api/organizations', ['name' => 'Acme', 'slug' => 'acme-inc'], $token);

        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        $payload = $this->decode();
        self::assertSame('acme-inc', $payload['slug']);
    }

    public function testShouldReturn409WhenCurrentUserAlreadyOwnsOrganization(): void
    {
        $token = $this->seedAdminAndLogin();
        $this->postJson('/api/organizations', ['name' => 'First'], $token);
        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());

        $this->postJson('/api/organizations', ['name' => 'Second'], $token);

        self::assertSame(Response::HTTP_CONFLICT, $this->client->getResponse()->getStatusCode());
        $payload = $this->decode();
        self::assertSame('ORGANIZATION_ALREADY_EXISTS', $payload['error']['code']);
    }

    public function testShouldReturn401OnUpdateWithoutToken(): void
    {
        $this->putJson('/api/organizations/'.Uuid::v7(), ['name' => 'Renamed'], null);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn404OnUpdateWhenOrganizationMissing(): void
    {
        $token = $this->seedAdminAndLogin();

        $this->putJson('/api/organizations/'.Uuid::v7(), ['name' => 'Renamed'], $token);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn403OnUpdateWhenActorIsNotOwner(): void
    {
        $ownerToken = $this->seedAdminAndLogin();
        $this->postJson('/api/organizations', ['name' => 'Owned'], $ownerToken);
        $organizationId = (string) $this->decode()['id'];

        $this->seedAdmin('intruder@example.com', self::DEFAULT_PASSWORD);
        $intruderToken = $this->login('intruder@example.com', self::DEFAULT_PASSWORD);

        $this->putJson('/api/organizations/'.$organizationId, ['name' => 'Hijacked'], $intruderToken);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn422OnUpdateWhenSlugIsNotKebabCase(): void
    {
        $token = $this->seedAdminAndLogin();
        $this->postJson('/api/organizations', ['name' => 'Acme'], $token);
        $organizationId = (string) $this->decode()['id'];

        $this->putJson('/api/organizations/'.$organizationId, ['slug' => 'Not Kebab'], $token);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn422OnUpdateWhenNameIsBlank(): void
    {
        $token = $this->seedAdminAndLogin();
        $this->postJson('/api/organizations', ['name' => 'Acme'], $token);
        $organizationId = (string) $this->decode()['id'];

        $this->putJson('/api/organizations/'.$organizationId, ['name' => ''], $token);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldUpdateOrganizationNameAndSlugAsOwner(): void
    {
        $token = $this->seedAdminAndLogin();
        $this->postJson('/api/organizations', ['name' => 'Acme'], $token);
        $created = $this->decode();
        $organizationId = (string) $created['id'];

        $this->putJson('/api/organizations/'.$organizationId, ['name' => 'Renamed', 'slug' => 'renamed-slug'], $token);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $payload = $this->decode();
        self::assertSame('Renamed', $payload['name']);
        self::assertSame('renamed-slug', $payload['slug']);
        self::assertSame($organizationId, $payload['id']);

        $stored = $this->organizations()->findById(OrganizationId::fromString($organizationId));
        self::assertNotNull($stored);
        self::assertSame('Renamed', $stored->name());
        self::assertSame('renamed-slug', $stored->slug());
    }

    public function testShouldAppendRandomSuffixOnUpdateWhenSlugClashesWithAnotherOrganization(): void
    {
        $ownerToken = $this->seedAdminAndLogin();
        $this->postJson('/api/organizations', ['name' => 'Acme', 'slug' => 'acme'], $ownerToken);
        $ownerOrgId = (string) $this->decode()['id'];

        $this->seedAdmin('other@example.com', self::DEFAULT_PASSWORD);
        $otherToken = $this->login('other@example.com', self::DEFAULT_PASSWORD);
        $this->postJson('/api/organizations', ['name' => 'Beta', 'slug' => 'beta'], $otherToken);

        $this->putJson('/api/organizations/'.$ownerOrgId, ['slug' => 'beta'], $ownerToken);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $payload = $this->decode();
        self::assertNotSame('beta', $payload['slug']);
        self::assertStringStartsWith('beta-', $payload['slug']);
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
            new DateTimeImmutable('2026-11-10T14:00:00+00:00'),
        );

        /** @var UserRepository $users */
        $users = self::getContainer()->get(UserRepository::class);
        $users->save($user);

        return $user;
    }

    private function organizations(): OrganizationRepository
    {
        /** @var OrganizationRepository $repo */
        $repo = self::getContainer()->get(OrganizationRepository::class);

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
