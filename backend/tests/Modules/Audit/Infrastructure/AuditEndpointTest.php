<?php

declare(strict_types=1);

namespace App\Tests\Modules\Audit\Infrastructure;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;

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

final class AuditEndpointTest extends WebTestCase
{
    private const string ADMIN_EMAIL = 'admin@example.com';

    private const string MEMBER_EMAIL = 'member@example.com';

    private const string PLAIN_PASSWORD = 'secret-pass-long-enough';

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->client->disableReboot();

        /** @var Connection $connection */
        $connection = self::getContainer()->get('doctrine.dbal.default_connection');
        $connection->executeStatement('TRUNCATE audit.audit_log');
    }

    public function testShouldReturn401WhenListingAuditWithoutToken(): void
    {
        $this->client->request('GET', '/api/audit/entries');

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn403WhenListingAuditAsNonSuperAdmin(): void
    {
        $this->seedAuthUser(self::MEMBER_EMAIL, UserRole::User);
        $token = $this->login(self::MEMBER_EMAIL);

        $this->client->request('GET', '/api/audit/entries', server: $this->auth($token));

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn404WhenAuditEntryMissing(): void
    {
        $this->seedAuthUser(self::ADMIN_EMAIL, UserRole::Admin);
        $token = $this->login(self::ADMIN_EMAIL);

        $this->client->request(
            'GET',
            '/api/audit/entries/' . Uuid::v7(),
            server: $this->auth($token),
        );

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn200WithEmptyPageWhenAuditIsEmpty(): void
    {
        $this->seedAuthUser(self::ADMIN_EMAIL, UserRole::Admin);
        $token = $this->login(self::ADMIN_EMAIL);

        $this->client->request('GET', '/api/audit/entries?per_page=10', server: $this->auth($token));

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $payload = $this->decode();
        self::assertSame(0, $payload['total']);
        self::assertSame(1, $payload['page']);
        self::assertSame(10, $payload['per_page']);
        self::assertSame([], $payload['items']);
    }

    private function seedAuthUser(string $email, UserRole $role): User
    {
        /** @var UserPasswordHasherInterface $hasher */
        $hasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        $hash = $hasher->hashPassword(new HashablePlaintextUser(), self::PLAIN_PASSWORD);

        $user = User::createWithRole(
            new UserId(Uuid::v7()),
            $email,
            $hash,
            $role,
            new DateTimeImmutable('2026-11-07T10:00:00+00:00'),
        );

        /** @var UserRepository $users */
        $users = self::getContainer()->get(UserRepository::class);
        $users->save($user);

        return $user;
    }

    private function login(string $email): string
    {
        $this->client->request(
            'POST',
            '/api/auth/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['email' => $email, 'password' => self::PLAIN_PASSWORD], JSON_THROW_ON_ERROR),
        );
        /** @var array{token: string} $payload */
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);

        return $payload['token'];
    }

    /**
     * @return array<string, string>
     */
    private function auth(string $token): array
    {
        return ['HTTP_AUTHORIZATION' => 'Bearer ' . $token];
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
