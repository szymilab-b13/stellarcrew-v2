<?php

declare(strict_types=1);

namespace App\Tests\Modules\UserManagement\Infrastructure\Http;

use App\Tests\Fixtures\UserManagement\InMemoryUserProfileReadModel;
use DateTimeImmutable;

use function json_decode;

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

final class ListUsersEndpointTest extends WebTestCase
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
        $this->client->request('GET', '/api/user-management/users');

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function testShould403WhenAuthenticatedUserIsNotSuperAdmin(): void
    {
        $this->seedAuthUser(self::MEMBER_EMAIL, UserRole::User);
        $token = $this->login(self::MEMBER_EMAIL);

        $this->getJson('/api/user-management/users', $token);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testShould200WithPaginatedPayloadWhenSuperAdminCalls(): void
    {
        $this->seedAuthUser(self::ADMIN_EMAIL, UserRole::Admin);
        $token = $this->login(self::ADMIN_EMAIL);
        $readModel = $this->readModel();
        $readModel->seedUser((string) Uuid::v7(), 'alice@example.com', false, new DateTimeImmutable('2026-11-01T10:00:00+00:00'));
        $readModel->seedUser((string) Uuid::v7(), 'bob@example.com', true, new DateTimeImmutable('2026-11-02T10:00:00+00:00'));

        $this->getJson('/api/user-management/users', $token);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $payload = $this->decode();
        self::assertSame(2, $payload['total']);
        self::assertSame(1, $payload['page']);
        self::assertSame(20, $payload['per_page']);
        self::assertCount(2, $payload['items']);
        self::assertSame('bob@example.com', $payload['items'][0]['email']);
        self::assertTrue($payload['items'][0]['is_super_admin']);
    }

    public function testShould200FilteringByQEmailWhenProvided(): void
    {
        $this->seedAuthUser(self::ADMIN_EMAIL, UserRole::Admin);
        $token = $this->login(self::ADMIN_EMAIL);
        $readModel = $this->readModel();
        $readModel->seedUser((string) Uuid::v7(), 'alice@example.com', false, new DateTimeImmutable('2026-11-01T10:00:00+00:00'));
        $readModel->seedUser((string) Uuid::v7(), 'bob@example.com', false, new DateTimeImmutable('2026-11-02T10:00:00+00:00'));

        $this->getJson('/api/user-management/users?q=alice', $token);

        $payload = $this->decode();
        self::assertSame(1, $payload['total']);
        self::assertSame('alice@example.com', $payload['items'][0]['email']);
    }

    public function testShould200WithOnlyDeactivatedUsersWhenFlagTrue(): void
    {
        $this->seedAuthUser(self::ADMIN_EMAIL, UserRole::Admin);
        $token = $this->login(self::ADMIN_EMAIL);
        $readModel = $this->readModel();
        $readModel->seedUser((string) Uuid::v7(), 'active@example.com', false, new DateTimeImmutable('2026-11-01T10:00:00+00:00'));
        $readModel->seedUser((string) Uuid::v7(), 'gone@example.com', false, new DateTimeImmutable('2026-11-02T10:00:00+00:00'), deactivatedAt: new DateTimeImmutable('2026-11-04T10:00:00+00:00'));

        $this->getJson('/api/user-management/users?only_deactivated=true', $token);

        $payload = $this->decode();
        self::assertSame(1, $payload['total']);
        self::assertSame('gone@example.com', $payload['items'][0]['email']);
        self::assertNotNull($payload['items'][0]['deactivated_at']);
    }

    private function seedAuthUser(string $email, UserRole $role): void
    {
        /** @var UserPasswordHasherInterface $hasher */
        $hasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        $hash = $hasher->hashPassword(new HashablePlaintextUser(), self::PLAIN_PASSWORD);

        $user = User::createWithRole(
            new UserId(Uuid::v7()),
            $email,
            $hash,
            $role,
            new DateTimeImmutable('2026-11-07T14:00:00+00:00'),
        );

        /** @var UserRepository $users */
        $users = self::getContainer()->get(UserRepository::class);
        $users->save($user);
    }

    private function login(string $email): string
    {
        $this->client->request(
            'POST',
            '/api/auth/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['email' => $email, 'password' => self::PLAIN_PASSWORD], JSON_THROW_ON_ERROR),
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

    private function readModel(): InMemoryUserProfileReadModel
    {
        /** @var InMemoryUserProfileReadModel $readModel */
        $readModel = self::getContainer()->get(InMemoryUserProfileReadModel::class);

        return $readModel;
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
