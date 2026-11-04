<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Http;

use Modules\Core\Api\HealthCheckInterface;
use Modules\Core\Infrastructure\HealthCheck\DbHealthCheck;
use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final readonly class HealthController
{
    /**
     * @param iterable<HealthCheckInterface> $checks
     */
    public function __construct(
        #[AutowireIterator('core.health_check')]
        private iterable $checks,
        #[Autowire(service: DbHealthCheck::class)]
        private HealthCheckInterface $readinessCheck,
        private LoggerInterface $logger = new NullLogger(),
    ) {
    }

    #[Route('/health', name: 'core_health', methods: ['GET'])]
    #[OA\Get(
        path: '/health',
        summary: 'Full health check (DB, Redis, RabbitMQ, registered modules).',
        tags: ['core'],
        responses: [
            new OA\Response(response: 200, description: 'All checks passed'),
            new OA\Response(response: 503, description: 'At least one check failed'),
        ],
    )]
    public function health(): JsonResponse
    {
        $checks = [];
        $overallOk = true;

        foreach ($this->checks as $check) {
            $result = $check->check();
            $checks[$result->name] = $result->isOk() ? 'ok' : 'fail';
            if (!$result->isOk()) {
                $overallOk = false;
                $this->logger->error(
                    'Health check failed',
                    ['check' => $result->name, 'reason' => $result->reason],
                );
            }
        }

        return new JsonResponse(
            [
                'status' => $overallOk ? 'ok' : 'fail',
                'checks' => $checks,
            ],
            $overallOk ? Response::HTTP_OK : Response::HTTP_SERVICE_UNAVAILABLE,
        );
    }

    #[Route('/ready', name: 'core_ready', methods: ['GET'])]
    #[OA\Get(
        path: '/ready',
        summary: 'Kubernetes readiness probe (database reachability).',
        tags: ['core'],
        responses: [
            new OA\Response(response: 200, description: 'Database is reachable'),
            new OA\Response(response: 503, description: 'Database is unreachable'),
        ],
    )]
    public function ready(): JsonResponse
    {
        $result = $this->readinessCheck->check();

        if (!$result->isOk()) {
            $this->logger->error(
                'Readiness check failed',
                ['check' => $result->name, 'reason' => $result->reason],
            );
        }

        return new JsonResponse(
            ['status' => $result->isOk() ? 'ok' : 'fail'],
            $result->isOk() ? Response::HTTP_OK : Response::HTTP_SERVICE_UNAVAILABLE,
        );
    }
}
