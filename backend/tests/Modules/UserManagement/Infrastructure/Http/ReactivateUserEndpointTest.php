<?php

declare(strict_types=1);

namespace App\Tests\Modules\UserManagement\Infrastructure\Http;

use App\Tests\Fixtures\Auth\SpyAuthApi;
use App\Tests\Fixtures\UserManagement\InMemoryUserProfileRepository;
use DateTimeImmutable;

use function json_decode;
use function json_encode;

use Modules\Auth\Domain\User;
use Modules\Auth\Domain\UserRepository;
use Modules\Auth\Domain\UserRole;
use Modules\Auth\Infrastructure\Security\HashablePlaintextUser;
use Modules\Shared\Kernel\UserId;
use Modules\UserManagement\Domain\UserProfile;
use Modules\UserManagement\Domain\UserProfileRepository;
use stdClass;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;

final class ReactivateUserEndpointTest extends WebTestCase
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
        $userId = new UserId(Uuid::v7());

        $this->postJson('/api/user-management/users/'.$userId.'/reactivate', null);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function testShould403WhenAuthenticatedUserIsNotSuperAdmin(): void
    {
        $this->seedUser(self::MEMBER_EMAIL, self::PLAIN_PASSWORD, UserRole::User);
        $token = $this->login(self::MEMBER_EMAIL, self::PLAIN_PASSWORD);
        $targetId = new UserId(Uuid::v7());

        $this->postJson('/api/user-management/users/'.$targetId.'/reactivate', $token);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testShould404WhenUserProfileDoesNotExist(): void
    {
        $this->seedUser(self::ADMIN_EMAIL, self::PLAIN_PASSWORD, UserRole::Admin);
        $token = $this->login(self::ADMIN_EMAIL, self::PLAIN_PASSWORD);
        $targetId = new UserId(Uuid::v7());

        $this->postJson('/api/user-management/users/'.$targetId.'/reactivate', $token);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testShould204AndClearDeactivatedAtWhenSuperAdminReactivates(): void
    {
        $this->seedUser(self::ADMIN_EMAIL, self::PLAIN_PASSWORD, UserRole::Admin);
        $token = $this->login(self::ADMIN_EMAIL, self::PLAIN_PASSWORD);
        $targetId = $this->seedDeactivatedProfile();

        $this->postJson('/api/user-management/users/'.$targetId.'/reactivate', $token);

        self::assertSame(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());
        $profile = $this->profilesRepo()->findById($targetId);
        self::assertNotNull($profile);
        self::assertFalse($profile->isDeactivated());
        self::assertCount(1, $this->spyAuth()->markReactivatedCalls);
    }

    public function testShould204AndNoOpWhenAlreadyActive(): void
    {
        $this->seedUser(self::ADMIN_EMAIL, self::PLAIN_PASSWORD, UserRole::Admin);
        $token = $this->login(self::ADMIN_EMAIL, self::PLAIN_PASSWORD);
        $id = new UserId(Uuid::v7());
        $this->profilesRepo()->save(UserProfile::create(
            $id,
            null,
            '#abcdef',
            new DateTimeImmutable('2026-11-07T14:00:00+00:00'),
        ));

        $this->postJson('/api/user-management/users/'.$id.'/reactivate', $token);

        self::assertSame(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());
        self::assertSame([], $this->spyAuth()->markReactivatedCalls);
    }

    private function seedDeactivatedProfile(): UserId
    {
        $id = new UserId(Uuid::v7());
        $previousDeactivation = new DateTimeImmutable('2026-11-01T08:00:00+00:00');
        $this->profilesRepo()->save(new UserProfile(
            $id,
            null,
            '#abcdef',
            new DateTimeImmutable('2026-11-01T08:00:00+00:00'),
            $previousDeactivation,
            $previousDeactivation,
        ));

        return $id;
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
        $this->postJson('/api/auth/login', null, body: ['email' => $email, 'password' => $plaintextPassword]);
        /** @var array<string, mixed> $payload */
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);

        return (string) $payload['token'];
    }

    /**
     * @param array<string, mixed>|null $body
     */
    private function postJson(string $path, ?string $token, ?array $body = null): void
    {
        $server = ['CONTENT_TYPE' => 'application/json'];
        if ($token !== null) {
            $server['HTTP_AUTHORIZATION'] = 'Bearer '.$token;
        }

        $this->client->request(
            'POST',
            $path,
            server: $server,
            content: json_encode($body ?? new stdClass(), JSON_THROW_ON_ERROR),
        );
    }

    private function profilesRepo(): InMemoryUserProfileRepository
    {
        /** @var InMemoryUserProfileRepository $repo */
        $repo = self::getContainer()->get(UserProfileRepository::class);

        return $repo;
    }

    private function spyAuth(): SpyAuthApi
    {
        /** @var SpyAuthApi $spy */
        $spy = self::getContainer()->get(SpyAuthApi::class);

        return $spy;
    }
}
