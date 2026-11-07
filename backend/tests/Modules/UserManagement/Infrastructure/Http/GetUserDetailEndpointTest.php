<?php

declare(strict_types=1);

namespace App\Tests\Modules\UserManagement\Infrastructure\Http;

use App\Tests\Fixtures\UserManagement\InMemoryUserProfileReadModel;
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

final class GetUserDetailEndpointTest extends WebTestCase
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

    public function testShouldReturn401WhenFetchingUserDetailWithoutToken(): void
    {
        $this->client->request('GET', '/api/user-management/users/' . Uuid::v7());

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn403WhenFetchingUserDetailAsNonSuperAdmin(): void
    {
        $this->seedAuthUser(self::MEMBER_EMAIL, UserRole::User);
        $token = $this->login(self::MEMBER_EMAIL);

        $this->client->request(
            'GET',
            '/api/user-management/users/' . Uuid::v7(),
            server: $this->auth($token),
        );

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn404WhenUserProfileDoesNotExist(): void
    {
        $this->seedAuthUser(self::ADMIN_EMAIL, UserRole::Admin);
        $token = $this->login(self::ADMIN_EMAIL);

        $this->client->request(
            'GET',
            '/api/user-management/users/' . Uuid::v7(),
            server: $this->auth($token),
        );

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn200WithProfileAndWorkspaceAssignments(): void
    {
        $this->seedAuthUser(self::ADMIN_EMAIL, UserRole::Admin);
        $token = $this->login(self::ADMIN_EMAIL);
        $targetId = (string) Uuid::v7();
        $workspaceId = (string) Uuid::v7();
        $readModel = $this->readModel();
        $readModel->seedUser(
            id: $targetId,
            email: 'target@example.com',
            isSuperAdmin: false,
            createdAt: new DateTimeImmutable('2026-11-01T10:00:00+00:00'),
            workspaceIds: [$workspaceId],
        );

        $this->client->request(
            'GET',
            '/api/user-management/users/' . $targetId,
            server: $this->auth($token),
        );

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $payload = $this->decode();
        self::assertSame($targetId, $payload['id']);
        self::assertSame('target@example.com', $payload['email']);
        self::assertFalse($payload['is_super_admin']);
        self::assertNull($payload['deactivated_at']);
        self::assertCount(1, $payload['workspace_assignments']);
        self::assertSame($workspaceId, $payload['workspace_assignments'][0]['workspace_id']);
        self::assertSame('member', $payload['workspace_assignments'][0]['role']);
    }

    public function testShouldReturn401WhenFetchingUserAuditWithoutToken(): void
    {
        $this->client->request('GET', '/api/user-management/users/' . Uuid::v7() . '/audit');

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn403WhenFetchingUserAuditAsNonSuperAdmin(): void
    {
        $this->seedAuthUser(self::MEMBER_EMAIL, UserRole::User);
        $token = $this->login(self::MEMBER_EMAIL);

        $this->client->request(
            'GET',
            '/api/user-management/users/' . Uuid::v7() . '/audit',
            server: $this->auth($token),
        );

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturnEmptyItemsWhenUserHasNoAuditHistory(): void
    {
        $this->seedAuthUser(self::ADMIN_EMAIL, UserRole::Admin);
        $token = $this->login(self::ADMIN_EMAIL);

        /** @var Connection $connection */
        $connection = self::getContainer()->get('doctrine.dbal.default_connection');
        $connection->executeStatement('TRUNCATE audit.audit_log');

        $this->client->request(
            'GET',
            '/api/user-management/users/' . Uuid::v7() . '/audit',
            server: $this->auth($token),
        );

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $payload = $this->decode();
        self::assertSame([], $payload['items']);
    }

    private function readModel(): InMemoryUserProfileReadModel
    {
        /** @var InMemoryUserProfileReadModel $readModel */
        $readModel = self::getContainer()->get(InMemoryUserProfileReadModel::class);

        return $readModel;
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
