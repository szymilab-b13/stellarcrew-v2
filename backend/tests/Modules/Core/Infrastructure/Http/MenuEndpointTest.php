<?php

declare(strict_types=1);

namespace App\Tests\Modules\Core\Infrastructure\Http;

use DateTimeImmutable;
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

final class MenuEndpointTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->client->disableReboot();
    }

    public function testShouldReturn401WhenMenuRequestedWithoutToken(): void
    {
        $this->client->request('GET', '/api/menu');

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturnOverviewWorkspacesAndAdminSectionsForAdmin(): void
    {
        $this->seedAdmin('admin@example.com', 'secret-pass-long-enough');
        $token = $this->login('admin@example.com', 'secret-pass-long-enough');

        $this->client->request('GET', '/api/menu', server: ['HTTP_AUTHORIZATION' => 'Bearer '.$token]);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);

        $sectionIds = array_map(static fn (array $section): string => $section['id'], $payload['sections']);
        self::assertSame(['overview', 'workspaces', 'admin'], $sectionIds);

        $overview = $payload['sections'][0];
        self::assertSame('menu.sections.overview', $overview['labelKey']);
        self::assertSame('dashboard', $overview['items'][0]['id']);
        self::assertSame('/dashboard', $overview['items'][0]['path']);
        self::assertSame('LayoutDashboard', $overview['items'][0]['icon']);

        $workspaces = $payload['sections'][1];
        self::assertSame('workspaces.list', $workspaces['items'][0]['id']);
        self::assertSame('/workspaces', $workspaces['items'][0]['path']);

        $admin = $payload['sections'][2];
        self::assertSame('menu.sections.admin', $admin['labelKey']);

        $adminItemIds = array_map(static fn (array $item): string => $item['id'], $admin['items']);
        self::assertSame(['admin.users', 'admin.audit'], $adminItemIds);

        self::assertSame('menu.items.users', $admin['items'][0]['labelKey']);
        self::assertSame('/admin/users', $admin['items'][0]['path']);
        self::assertSame('Users', $admin['items'][0]['icon']);

        self::assertSame('menu.items.auditLog', $admin['items'][1]['labelKey']);
        self::assertSame('/admin/audit', $admin['items'][1]['path']);
        self::assertSame('ScrollText', $admin['items'][1]['icon']);
    }

    public function testShouldHideAdminSectionForNonAdmin(): void
    {
        $this->seedRegular('user@example.com', 'secret-pass-long-enough');
        $token = $this->login('user@example.com', 'secret-pass-long-enough');

        $this->client->request('GET', '/api/menu', server: ['HTTP_AUTHORIZATION' => 'Bearer '.$token]);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);

        $sectionIds = array_map(static fn (array $section): string => $section['id'], $payload['sections']);
        self::assertNotContains('admin', $sectionIds);
    }

    private function seedAdmin(string $email, string $plaintextPassword): void
    {
        /** @var UserPasswordHasherInterface $hasher */
        $hasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        $passwordHash = $hasher->hashPassword(new HashablePlaintextUser(), $plaintextPassword);

        $user = User::createAdmin(
            new UserId(Uuid::v7()),
            $email,
            $passwordHash,
            new DateTimeImmutable('2026-11-06T09:00:00+00:00'),
            'Tomek',
        );

        /** @var UserRepository $users */
        $users = self::getContainer()->get(UserRepository::class);
        $users->save($user);
    }

    private function seedRegular(string $email, string $plaintextPassword): void
    {
        /** @var UserPasswordHasherInterface $hasher */
        $hasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        $passwordHash = $hasher->hashPassword(new HashablePlaintextUser(), $plaintextPassword);

        $user = User::createWithRole(
            new UserId(Uuid::v7()),
            $email,
            $passwordHash,
            UserRole::User,
            new DateTimeImmutable('2026-11-06T09:00:00+00:00'),
            'Regular',
        );

        /** @var UserRepository $users */
        $users = self::getContainer()->get(UserRepository::class);
        $users->save($user);
    }

    private function login(string $email, string $plaintextPassword): string
    {
        $this->client->request(
            'POST',
            '/api/auth/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['email' => $email, 'password' => $plaintextPassword], JSON_THROW_ON_ERROR),
        );

        $payload = json_decode((string) $this->client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);

        return (string) $payload['token'];
    }
}
