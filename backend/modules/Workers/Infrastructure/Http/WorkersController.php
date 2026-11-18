<?php

declare(strict_types=1);

namespace Modules\Workers\Infrastructure\Http;

use DateTimeInterface;
use Modules\Auth\Infrastructure\Security\SecurityUser;
use Modules\Workers\Application\ClaimPairingCodeCommand;
use Modules\Workers\Application\ClaimPairingCodeCommandHandler;
use Modules\Workers\Application\GeneratePairingCodeCommand;
use Modules\Workers\Application\GeneratePairingCodeCommandHandler;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final readonly class WorkersController
{
    public function __construct(
        private GeneratePairingCodeCommandHandler $generatePairingCode,
        private ClaimPairingCodeCommandHandler $claimPairingCode,
    ) {
    }

    #[Route('/api/workers/pairing-codes', name: 'workers_pairing_code_generate', methods: ['POST'])]
    #[OA\Post(
        path: '/api/workers/pairing-codes',
        summary: 'Generate a short-lived pairing code for a new worker',
        tags: ['workers'],
        responses: [
            new OA\Response(response: 201, description: 'Created pairing code'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ],
    )]
    public function generatePairingCode(#[CurrentUser] SecurityUser $user): JsonResponse
    {
        $code = ($this->generatePairingCode)(new GeneratePairingCodeCommand(
            ownerUserId: $user->domainUser()->id(),
        ));

        return new JsonResponse([
            'code' => $code->code(),
            'expiresAt' => $code->expiresAt()->format(DateTimeInterface::ATOM),
        ], Response::HTTP_CREATED);
    }

    #[Route('/api/workers/pair', name: 'workers_pair', methods: ['POST'])]
    #[OA\Post(
        path: '/api/workers/pair',
        summary: 'Claim a pairing code and register a new worker',
        tags: ['workers'],
        responses: [
            new OA\Response(response: 201, description: 'Worker paired with credentials (plaintext secret returned once)'),
            new OA\Response(response: 400, description: 'Invalid or expired code'),
            new OA\Response(response: 404, description: 'Code not found'),
        ],
    )]
    public function pair(#[MapRequestPayload] ClaimPairingCodeRequest $request): JsonResponse
    {
        $result = ($this->claimPairingCode)(new ClaimPairingCodeCommand(
            code: $request->code,
            name: $request->name,
            hostLabel: $request->hostLabel,
        ));

        return new JsonResponse([
            'workerId' => (string) $result->workerId,
            'secretToken' => $result->plaintextSecret,
            'rabbitmqUser' => $result->rabbitmqUser,
        ], Response::HTTP_CREATED);
    }
}
