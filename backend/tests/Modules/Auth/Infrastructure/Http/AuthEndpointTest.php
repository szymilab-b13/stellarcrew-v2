<?php

declare(strict_types=1);

namespace App\Tests\Modules\Auth\Infrastructure\Http;

use DateTimeImmutable;
use Modules\Auth\Domain\User;
use Modules\Auth\Domain\UserRepository;
use Modules\Auth\Infrastructure\Security\HashablePlaintextUser;
use Modules\Shared\Kernel\UserId;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;

final class AuthEndpointTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->client->disableReboot();
    }

    public function testShouldReturnTokenAndUserOnValidCredentials(): void
    {
        $this->seedAdmin('admin@example.com', 'secret-pass-long-enough', displayName: 'Tomek');

        $this->postJson('/api/auth/login', ['email' => 'admin@example.com', 'password' => 'secret-pass-long-enough']);

        $response = $this->client->getResponse();
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $payload = $this->decode($response->getContent());
        self::assertArrayHasKey('token', $payload);
        self::assertNotEmpty($payload['token']);
        self::assertSame('admin@example.com', $payload['user']['email']);
        self::assertSame('Tomek', $payload['user']['display_name']);
        self::assertSame('admin', $payload['user']['role']);
    }

    public function testShouldReturn401OnUnknownEmail(): void
    {
        $this->postJson('/api/auth/login', ['email' => 'nobody@example.com', 'password' => 'anything-at-all']);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn401OnWrongPassword(): void
    {
        $this->seedAdmin('admin@example.com', 'secret-pass-long-enough');

        $this->postJson('/api/auth/login', ['email' => 'admin@example.com', 'password' => 'completely-wrong-pass']);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn422OnMissingEmail(): void
    {
        $this->postJson('/api/auth/login', ['password' => 'secret-pass-long-enough']);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturnMeWhenAuthenticated(): void
    {
        $this->seedAdmin('admin@example.com', 'secret-pass-long-enough', displayName: 'Tomek');
        $token = $this->login('admin@example.com', 'secret-pass-long-enough');

        $this->client->request('GET', '/api/auth/me', server: ['HTTP_AUTHORIZATION' => 'Bearer '.$token]);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $payload = $this->decode($this->client->getResponse()->getContent());
        self::assertSame('admin@example.com', $payload['email']);
        self::assertSame('Tomek', $payload['display_name']);
    }

    public function testShouldReturn401OnMeWithoutToken(): void
    {
        $this->client->request('GET', '/api/auth/me');

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn204OnLogout(): void
    {
        $this->client->request('POST', '/api/auth/logout');

        self::assertSame(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());
    }

    private function seedAdmin(string $email, string $plaintextPassword, ?string $displayName = null): void
    {
        /** @var UserPasswordHasherInterface $hasher */
        $hasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        $passwordHash = $hasher->hashPassword(new HashablePlaintextUser(), $plaintextPassword);

        $user = User::createAdmin(
            new UserId(Uuid::v7()),
            $email,
            $passwordHash,
            new DateTimeImmutable('2026-11-06T09:00:00+00:00'),
            $displayName,
        );

        /** @var UserRepository $users */
        $users = self::getContainer()->get(UserRepository::class);
        $users->save($user);
    }

    private function login(string $email, string $plaintextPassword): string
    {
        $this->postJson('/api/auth/login', ['email' => $email, 'password' => $plaintextPassword]);

        $payload = $this->decode($this->client->getResponse()->getContent());

        return (string) $payload['token'];
    }

    /**
     * @param array<string, mixed> $body
     */
    private function postJson(string $path, array $body): void
    {
        $this->client->request(
            'POST',
            $path,
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($body, JSON_THROW_ON_ERROR),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(string|false $content): array
    {
        return json_decode((string) $content, true, flags: JSON_THROW_ON_ERROR);
    }
}
