<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Http;

use Modules\Core\Infrastructure\Realtime\MercureJwtFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class MercureTokenController
{
    public function __construct(
        private MercureJwtFactory $jwtFactory,
        private string $hubPublicUrl,
    ) {
    }

    #[Route('/api/mercure/token', name: 'mercure_token', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $token = $this->jwtFactory->issue(
            publishTopics: [],
            subscribeTopics: ['chat/thread/{id}', 'workers/list'],
        );

        return new JsonResponse([
            'token' => $token,
            'hubUrl' => $this->hubPublicUrl,
        ]);
    }
}
