<?php

declare(strict_types=1);

namespace App\Tests\Modules\Core\Infrastructure\Http;

use Modules\Core\Api\Dto\HealthResult;
use Modules\Core\Api\HealthCheckInterface;
use Modules\Core\Infrastructure\Http\HealthController;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

final class HealthControllerTest extends TestCase
{
    public function testShouldReturn200AndAllChecksWhenEveryHealthCheckOk(): void
    {
        $db = $this->healthCheck('db', HealthResult::ok('db'));
        $redis = $this->healthCheck('redis', HealthResult::ok('redis'));
        $rabbit = $this->healthCheck('rabbitmq', HealthResult::ok('rabbitmq'));
        $sut = new HealthController([$db, $redis, $rabbit], $db);

        $response = $sut->health();

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString(
            (string) json_encode(['status' => 'ok', 'checks' => ['db' => 'ok', 'redis' => 'ok', 'rabbitmq' => 'ok']]),
            (string) $response->getContent(),
        );
    }

    public function testShouldReturn503AndPointToFailedCheckWhenRedisFails(): void
    {
        $db = $this->healthCheck('db', HealthResult::ok('db'));
        $redis = $this->healthCheck('redis', HealthResult::fail('redis', 'connection refused'));
        $rabbit = $this->healthCheck('rabbitmq', HealthResult::ok('rabbitmq'));
        $sut = new HealthController([$db, $redis, $rabbit], $db);

        $response = $sut->health();

        self::assertSame(Response::HTTP_SERVICE_UNAVAILABLE, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString(
            (string) json_encode(['status' => 'fail', 'checks' => ['db' => 'ok', 'redis' => 'fail', 'rabbitmq' => 'ok']]),
            (string) $response->getContent(),
        );
    }

    public function testShouldExposeExternalModuleCheckWhenRegisteredViaTag(): void
    {
        $db = $this->healthCheck('db', HealthResult::ok('db'));
        $listener = $this->healthCheck('listener-mcp', HealthResult::ok('listener-mcp'));
        $sut = new HealthController([$db, $listener], $db);

        $response = $sut->health();

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString(
            (string) json_encode(['status' => 'ok', 'checks' => ['db' => 'ok', 'listener-mcp' => 'ok']]),
            (string) $response->getContent(),
        );
    }

    public function testShouldReturn200WhenReadinessDbCheckOk(): void
    {
        $db = $this->healthCheck('db', HealthResult::ok('db'));
        $sut = new HealthController([$db], $db);

        $response = $sut->ready();

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testShouldReturn503WhenReadinessDbCheckFails(): void
    {
        $db = $this->healthCheck('db', HealthResult::fail('db', 'unable to connect'));
        $sut = new HealthController([$db], $db);

        $response = $sut->ready();

        self::assertSame(Response::HTTP_SERVICE_UNAVAILABLE, $response->getStatusCode());
    }

    public function testShouldLogErrorWithReasonWhenAnyHealthCheckFails(): void
    {
        $db = $this->healthCheck('db', HealthResult::ok('db'));
        $redis = $this->healthCheck('redis', HealthResult::fail('redis', 'connection refused'));
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('error')
            ->with('Health check failed', ['check' => 'redis', 'reason' => 'connection refused']);
        $sut = new HealthController([$db, $redis], $db, $logger);

        $sut->health();
    }

    public function testShouldLogErrorWhenReadinessCheckFails(): void
    {
        $db = $this->healthCheck('db', HealthResult::fail('db', 'unable to connect'));
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('error')
            ->with('Readiness check failed', ['check' => 'db', 'reason' => 'unable to connect']);
        $sut = new HealthController([$db], $db, $logger);

        $sut->ready();
    }

    private function healthCheck(string $name, HealthResult $result): HealthCheckInterface
    {
        return new readonly class ($name, $result) implements HealthCheckInterface {
            public function __construct(
                private string $name,
                private HealthResult $result,
            ) {
            }

            public function name(): string
            {
                return $this->name;
            }

            public function check(): HealthResult
            {
                return $this->result;
            }
        };
    }
}
