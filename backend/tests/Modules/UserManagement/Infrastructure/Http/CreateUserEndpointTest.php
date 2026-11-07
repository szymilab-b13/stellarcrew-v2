<?php

declare(strict_types=1);

namespace App\Tests\Modules\UserManagement\Infrastructure\Http;

use App\Tests\Fixtures\Auth\SpyAuthApi;
use DateTimeImmutable;

use function json_decode;
use function json_encode;

use Modules\Auth\Domain\User;
use Modules\Auth\Domain\UserRepository;
use Modules\Auth\Domain\UserRole;
use Modules\Auth\Infrastructure\Security\HashablePlaintextUser;
use Modules\Shared\Kernel\UserId;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;

final class CreateUserEndpointTest extends WebTestCase
{
    private const string ADMIN_EMAIL = 'admin@example.com';

    private const string MEMBER_EMAIL = 'member@example.com';

    private const string PLAIN_PASSWORD = 'secret-pass-long-enough';

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->client->disableReboot();
    }

    public function testShould401WhenUnauthenticated(): void
    {
        $this->postJson('/api/user-management/users', [
            'email' => 'new@example.com',
            'password' => self::PLAIN_PASSWORD,
        ], null);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function testShould403WhenAuthenticatedUserIsNotSuperAdmin(): void
    {
        $this->seedUser(self::MEMBER_EMAIL, self::PLAIN_PASSWORD, UserRole::User);
        $token = $this->login(self::MEMBER_EMAIL, self::PLAIN_PASSWORD);

        $this->postJson('/api/user-management/users', [
            'email' => 'new@example.com',
            'password' => self::PLAIN_PASSWORD,
        ], $token);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testShould201WhenSuperAdminCreatesUser(): void
    {
        $this->seedUser(self::ADMIN_EMAIL, self::PLAIN_PASSWORD, UserRole::Admin);
        $token = $this->login(self::ADMIN_EMAIL, self::PLAIN_PASSWORD);

        $this->postJson('/api/user-management/users', [
            'email' => 'new@example.com',
            'password' => self::PLAIN_PASSWORD,
            'isSuperAdmin' => false,
            'displayName' => 'New Person',
        ], $token);

        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        $payload = $this->decode();
        self::assertArrayHasKey('id', $payload);
        self::assertSame('New Person', $payload['displayName']);
        self::assertStringStartsWith('#', $payload['avatarColor']);
        self::assertFalse($payload['isDeactivated']);
    }

    public function testShould422WhenEmailMissing(): void
    {
        $this->seedUser(self::ADMIN_EMAIL, self::PLAIN_PASSWORD, UserRole::Admin);
        $token = $this->login(self::ADMIN_EMAIL, self::PLAIN_PASSWORD);

        $this->postJson('/api/user-management/users', [
            'email' => 'not-an-email',
            'password' => self::PLAIN_PASSWORD,
        ], $token);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
    }

    public function testShould422WhenPasswordTooShort(): void
    {
        $this->seedUser(self::ADMIN_EMAIL, self::PLAIN_PASSWORD, UserRole::Admin);
        $token = $this->login(self::ADMIN_EMAIL, self::PLAIN_PASSWORD);

        $this->postJson('/api/user-management/users', [
            'email' => 'new@example.com',
            'password' => 'short',
        ], $token);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
    }

    public function testShould422WithEmailTakenCodeWhenEmailAlreadyRegistered(): void
    {
        $this->seedUser(self::ADMIN_EMAIL, self::PLAIN_PASSWORD, UserRole::Admin);
        $token = $this->login(self::ADMIN_EMAIL, self::PLAIN_PASSWORD);
        $this->spyAuth()->withTakenEmail('duplicate@example.com');

        $this->postJson('/api/user-management/users', [
            'email' => 'duplicate@example.com',
            'password' => self::PLAIN_PASSWORD,
        ], $token);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
        self::assertSame('EMAIL_TAKEN', $this->decode()['error']['code']);
    }

    private function seedUser(string $email, string $plaintextPassword, UserRole $role): User
    {
        /** @var UserPasswordHasherInterface $hasher */
        $hasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        $passwordHash = $hasher->hashPassword(new HashablePlaintextUser(), $plaintextPassword);

        $user = User::createWithRole(
            new UserId(Uuid::v7()),
            $email,
            $passwordHash,
            $role,
            new DateTimeImmutable('2026-11-07T14:00:00+00:00'),
        );

        /** @var UserRepository $users */
        $users = self::getContainer()->get(UserRepository::class);
        $users->save($user);

        return $user;
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

    private function spyAuth(): SpyAuthApi
    {
        /** @var SpyAuthApi $spy */
        $spy = self::getContainer()->get(SpyAuthApi::class);

        return $spy;
    }
}
