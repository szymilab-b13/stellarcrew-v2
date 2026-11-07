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

final class ListVisibleUsersEndpointTest extends WebTestCase
{
    private const string PLAIN_PASSWORD = 'secret-pass-long-enough';

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->client->disableReboot();
    }

    public function testShould401WhenUnauthenticated(): void
    {
        $this->client->request('GET', '/api/user-management/users/visible');

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function testShould200WithPeersFromSharedWorkspacesWhenAuthenticated(): void
    {
        $viewerId = (string) Uuid::v7();
        $this->seedAuthUser($viewerId, 'viewer@example.com', UserRole::User);
        $token = $this->login('viewer@example.com');

        $sharedWorkspace = (string) Uuid::v7();
        $peerId = (string) Uuid::v7();
        $strangerId = (string) Uuid::v7();
        $readModel = $this->readModel();
        $readModel->seedUser($viewerId, 'viewer@example.com', false, new DateTimeImmutable('2026-11-01T10:00:00+00:00'), workspaceIds: [$sharedWorkspace]);
        $readModel->seedUser($peerId, 'peer@example.com', false, new DateTimeImmutable('2026-11-02T10:00:00+00:00'), workspaceIds: [$sharedWorkspace]);
        $readModel->seedUser($strangerId, 'stranger@example.com', false, new DateTimeImmutable('2026-11-03T10:00:00+00:00'), workspaceIds: [(string) Uuid::v7()]);

        $this->getJson('/api/user-management/users/visible', $token);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $payload = $this->decode();
        self::assertCount(1, $payload['items']);
        self::assertSame('peer@example.com', $payload['items'][0]['email']);
    }

    private function seedAuthUser(string $id, string $email, UserRole $role): void
    {
        /** @var UserPasswordHasherInterface $hasher */
        $hasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        $hash = $hasher->hashPassword(new HashablePlaintextUser(), self::PLAIN_PASSWORD);

        $user = User::createWithRole(
            new UserId(Uuid::fromString($id)),
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
