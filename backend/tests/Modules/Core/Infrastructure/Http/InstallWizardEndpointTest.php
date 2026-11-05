<?php

declare(strict_types=1);

namespace App\Tests\Modules\Core\Infrastructure\Http;

use App\Tests\Fixtures\Auth\SpyAuthApi;
use App\Tests\Fixtures\Core\InMemorySystemSettingRepository;
use Modules\Auth\Api\AuthApi;
use Modules\Core\Application\Install\IsInstalledQueryHandler;
use Modules\Core\Domain\SystemSetting;
use Modules\Core\Domain\SystemSettingRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class InstallWizardEndpointTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();
    }

    public function testShouldReturnIsInstalledFalseWhenFlagMissing(): void
    {
        $this->client->request('GET', '/api/install/status');

        $response = $this->client->getResponse();
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        $payload = $this->decode($response->getContent());
        self::assertFalse($payload['is_installed']);
    }

    public function testShouldReturnIsInstalledTrueWhenFlagSet(): void
    {
        $this->settings()->markInstalled();

        $this->client->request('GET', '/api/install/status');

        $response = $this->client->getResponse();
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        $payload = $this->decode($response->getContent());
        self::assertTrue($payload['is_installed']);
    }

    public function testShouldCreateAdminAndFlipFlagWhenInstallPayloadValid(): void
    {
        $this->postInstall(['email' => 'admin@example.com', 'password' => 'secret-pass-long-enough']);

        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        self::assertCount(1, $this->auth()->calls);
        self::assertSame('admin@example.com', $this->auth()->calls[0]['email']);
        self::assertSame('secret-pass-long-enough', $this->auth()->calls[0]['password']);
        self::assertTrue($this->isInstalledInRepo());
    }

    public function testShouldReturn404WhenInstallAlreadyPerformed(): void
    {
        $this->settings()->markInstalled();

        $this->postInstall(['email' => 'admin@example.com', 'password' => 'secret-pass-long-enough']);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
        self::assertCount(0, $this->auth()->calls);
    }

    public function testShouldReturn400WhenEmailInvalid(): void
    {
        $this->postInstall(['email' => 'not-an-email', 'password' => 'secret-pass-long-enough']);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        self::assertFalse($this->isInstalledInRepo());
    }

    public function testShouldReturn400WhenPasswordTooShort(): void
    {
        $this->postInstall(['email' => 'admin@example.com', 'password' => 'short']);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        self::assertFalse($this->isInstalledInRepo());
    }

    public function testShouldReturn400WhenPayloadMissingFields(): void
    {
        $this->postInstall([]);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn400WhenPayloadNotJson(): void
    {
        $this->client->request(
            'POST',
            '/api/install',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: '{not-json',
        );

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function postInstall(array $payload): void
    {
        $this->client->request(
            'POST',
            '/api/install',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload, JSON_THROW_ON_ERROR),
        );
    }

    private function settings(): InMemorySystemSettingRepository
    {
        /** @var InMemorySystemSettingRepository $repo */
        $repo = $this->client->getContainer()->get(SystemSettingRepository::class);

        return $repo;
    }

    private function auth(): SpyAuthApi
    {
        /** @var SpyAuthApi $auth */
        $auth = $this->client->getContainer()->get(AuthApi::class);

        return $auth;
    }

    private function isInstalledInRepo(): bool
    {
        $flag = $this->settings()->get(IsInstalledQueryHandler::FLAG_NAME);

        return $flag instanceof SystemSetting && $flag->value() === IsInstalledQueryHandler::TRUE_VALUE;
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(string|false $content): array
    {
        /** @var array<string, mixed> $decoded */
        $decoded = json_decode((string) $content, true, flags: JSON_THROW_ON_ERROR);

        return $decoded;
    }
}
