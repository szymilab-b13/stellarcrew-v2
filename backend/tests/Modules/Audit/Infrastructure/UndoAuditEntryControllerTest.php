<?php

declare(strict_types=1);

namespace App\Tests\Modules\Audit\Infrastructure;

use App\Tests\Fixtures\Audit\StubTestUndoer;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;

use function json_decode;

use Modules\Audit\Api\AuditApi;
use Modules\Audit\Api\AuditEntryWithState;
use Modules\Auth\Domain\User;
use Modules\Auth\Domain\UserRepository;
use Modules\Auth\Domain\UserRole;
use Modules\Auth\Infrastructure\Security\HashablePlaintextUser;
use Modules\Shared\Kernel\AuditEntryId;
use Modules\Shared\Kernel\UserId;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;

final class UndoAuditEntryControllerTest extends WebTestCase
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

    public function testShouldReturn401WhenNoToken(): void
    {
        $this->client->request('POST', '/api/audit/entries/' . Uuid::v7() . '/undo');

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn403WhenCallerIsNotSuperAdmin(): void
    {
        $this->seedAuthUser(self::MEMBER_EMAIL, UserRole::User);
        $token = $this->login(self::MEMBER_EMAIL);

        $this->client->request(
            'POST',
            '/api/audit/entries/' . Uuid::v7() . '/undo',
            server: $this->auth($token),
        );

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn404WhenAuditEntryMissing(): void
    {
        $this->seedAuthUser(self::ADMIN_EMAIL, UserRole::Admin);
        $token = $this->login(self::ADMIN_EMAIL);

        $this->client->request(
            'POST',
            '/api/audit/entries/' . Uuid::v7() . '/undo',
            server: $this->auth($token),
        );

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn422WhenActionHasNoUndoer(): void
    {
        $admin = $this->seedAuthUser(self::ADMIN_EMAIL, UserRole::Admin);
        $token = $this->login(self::ADMIN_EMAIL);

        $entryId = $this->seedAuditEntry($admin->id(), 'test.unsupported');

        $this->client->request(
            'POST',
            '/api/audit/entries/' . $entryId . '/undo',
            server: $this->auth($token),
        );

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
        $payload = $this->decode();
        self::assertSame('UNDO_NOT_SUPPORTED', $payload['error']['code']);
    }

    public function testShouldReturn200AndNewEntryIdOnHappyPath(): void
    {
        $admin = $this->seedAuthUser(self::ADMIN_EMAIL, UserRole::Admin);
        $token = $this->login(self::ADMIN_EMAIL);

        $entryId = $this->seedAuditEntry($admin->id(), StubTestUndoer::HAPPY_ACTION);

        $this->client->request(
            'POST',
            '/api/audit/entries/' . $entryId . '/undo',
            server: $this->auth($token),
        );

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $payload = $this->decode();
        self::assertArrayHasKey('new_entry_id', $payload);
        self::assertTrue(Uuid::isValid($payload['new_entry_id']));
    }

    public function testShouldReturn409WhenUndoerThrowsConflict(): void
    {
        $admin = $this->seedAuthUser(self::ADMIN_EMAIL, UserRole::Admin);
        $token = $this->login(self::ADMIN_EMAIL);

        $entryId = $this->seedAuditEntry($admin->id(), StubTestUndoer::CONFLICT_ACTION);

        $this->client->request(
            'POST',
            '/api/audit/entries/' . $entryId . '/undo',
            server: $this->auth($token),
        );

        self::assertSame(Response::HTTP_CONFLICT, $this->client->getResponse()->getStatusCode());
        $payload = $this->decode();
        self::assertSame('UNDO_CONFLICT', $payload['error']['code']);
        self::assertSame('test_conflict', $payload['error']['details']['conflict_code']);
    }

    public function testShouldReturn410WhenEntryAlreadyUndone(): void
    {
        $admin = $this->seedAuthUser(self::ADMIN_EMAIL, UserRole::Admin);
        $token = $this->login(self::ADMIN_EMAIL);

        $entryId = $this->seedAuditEntry($admin->id(), StubTestUndoer::HAPPY_ACTION);

        $this->client->request(
            'POST',
            '/api/audit/entries/' . $entryId . '/undo',
            server: $this->auth($token),
        );
        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $this->client->request(
            'POST',
            '/api/audit/entries/' . $entryId . '/undo',
            server: $this->auth($token),
        );

        self::assertSame(Response::HTTP_GONE, $this->client->getResponse()->getStatusCode());
        $payload = $this->decode();
        self::assertSame('ALREADY_UNDONE', $payload['error']['code']);
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

    private function seedAuditEntry(UserId $actor, string $action): string
    {
        /** @var AuditApi $audit */
        $audit = self::getContainer()->get(AuditApi::class);

        $entryId = $audit->record(new AuditEntryWithState(
            actorUserId: $actor,
            action: $action,
            occurredAt: new DateTimeImmutable('2026-11-08T10:00:00+00:00'),
            resourceType: 'TestResource',
            resourceId: (string) Uuid::v7(),
        ));

        self::assertInstanceOf(AuditEntryId::class, $entryId);

        return (string) $entryId;
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
