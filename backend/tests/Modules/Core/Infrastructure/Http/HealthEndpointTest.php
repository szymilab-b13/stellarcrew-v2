<?php

declare(strict_types=1);

namespace App\Tests\Modules\Core\Infrastructure\Http;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class HealthEndpointTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();
    }

    public function testShouldReturn200AndStatusOkWhenAllServicesReachable(): void
    {
        $this->client->request('GET', '/api/health');

        $response = $this->client->getResponse();
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $payload = json_decode((string) $response->getContent(), true);
        self::assertIsArray($payload);
        self::assertSame('ok', $payload['status']);
        self::assertSame('ok', $payload['checks']['db']);
        self::assertSame('ok', $payload['checks']['redis']);
        self::assertSame('ok', $payload['checks']['rabbitmq']);
    }

    public function testShouldReturn200OnReadinessWhenDatabaseReachable(): void
    {
        $this->client->request('GET', '/api/ready');

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }
}
