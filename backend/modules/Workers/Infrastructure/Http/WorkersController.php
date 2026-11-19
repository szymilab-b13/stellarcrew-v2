<?php

declare(strict_types=1);

namespace Modules\Workers\Infrastructure\Http;

use function array_map;

use DateTimeInterface;
use Modules\Auth\Infrastructure\Security\SecurityUser;
use Modules\Shared\Kernel\WorkerId;
use Modules\Workers\Application\ClaimPairingCodeCommand;
use Modules\Workers\Application\ClaimPairingCodeCommandHandler;
use Modules\Workers\Application\GeneratePairingCodeCommand;
use Modules\Workers\Application\GeneratePairingCodeCommandHandler;
use Modules\Workers\Application\ListUserWorkersQuery;
use Modules\Workers\Application\ListUserWorkersQueryHandler;
use Modules\Workers\Application\RecordHeartbeatCommand;
use Modules\Workers\Application\RecordHeartbeatCommandHandler;
use Modules\Workers\Application\RevokeAllOwnerWorkersCommand;
use Modules\Workers\Application\RevokeAllOwnerWorkersCommandHandler;
use Modules\Workers\Application\RevokeWorkerCommand;
use Modules\Workers\Application\RevokeWorkerCommandHandler;
use Modules\Workers\Application\WorkerView;
use Modules\Workers\Domain\WorkerNotFoundException;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final readonly class WorkersController
{
    public function __construct(
        private GeneratePairingCodeCommandHandler $generatePairingCode,
        private ClaimPairingCodeCommandHandler $claimPairingCode,
        private RecordHeartbeatCommandHandler $recordHeartbeat,
        private ListUserWorkersQueryHandler $listUserWorkers,
        private RevokeWorkerCommandHandler $revokeWorker,
        private RevokeAllOwnerWorkersCommandHandler $revokeAllOwnerWorkers,
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
            fingerprintHash: $request->fingerprintHash,
            fingerprintMeta: $request->fingerprintMeta,
        ));

        return new JsonResponse([
            'workerId' => (string) $result->workerId,
            'secretToken' => $result->plaintextSecret,
            'rabbitmqUser' => $result->rabbitmqUser,
            'refreshToken' => $result->refreshToken,
        ], Response::HTTP_CREATED);
    }

    #[Route(
        '/api/workers/{id}/heartbeat',
        name: 'workers_heartbeat',
        requirements: ['id' => '[0-9a-fA-F-]{36}'],
        methods: ['POST'],
    )]
    #[OA\Post(
        path: '/api/workers/{id}/heartbeat',
        summary: 'Report worker heartbeat (authenticates by plaintext secret)',
        tags: ['workers'],
        responses: [
            new OA\Response(response: 204, description: 'Heartbeat recorded'),
            new OA\Response(response: 401, description: 'Invalid worker credentials'),
            new OA\Response(response: 404, description: 'Worker not found'),
        ],
    )]
    public function heartbeat(string $id, #[MapRequestPayload] RecordHeartbeatRequest $request): JsonResponse
    {
        ($this->recordHeartbeat)(new RecordHeartbeatCommand(
            workerId: WorkerId::fromString($id),
            plaintextSecret: $request->secret,
        ));

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/workers', name: 'workers_list', methods: ['GET'])]
    #[OA\Get(
        path: '/api/workers',
        summary: 'List workers owned by the current user',
        tags: ['workers'],
        responses: [
            new OA\Response(response: 200, description: 'Workers list'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ],
    )]
    public function list(#[CurrentUser] SecurityUser $user): JsonResponse
    {
        $views = ($this->listUserWorkers)(new ListUserWorkersQuery(
            ownerUserId: $user->domainUser()->id(),
        ));

        return new JsonResponse([
            'workers' => array_map($this->serialiseWorker(...), $views),
        ]);
    }

    #[Route(
        '/api/workers/{id}',
        name: 'workers_revoke',
        requirements: ['id' => '[0-9a-fA-F-]{36}'],
        methods: ['DELETE'],
    )]
    #[OA\Delete(
        path: '/api/workers/{id}',
        summary: 'Revoke a worker and clear its references across agents, threads, and sessions',
        tags: ['workers'],
        responses: [
            new OA\Response(response: 204, description: 'Worker revoked'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Not the worker owner'),
            new OA\Response(response: 404, description: 'Worker not found'),
        ],
    )]
    public function revoke(string $id, #[CurrentUser] SecurityUser $user): JsonResponse
    {
        try {
            ($this->revokeWorker)(new RevokeWorkerCommand(
                workerId: WorkerId::fromString($id),
                actorUserId: $user->domainUser()->id(),
            ));
        } catch (WorkerNotFoundException $workerNotFoundException) {
            throw new NotFoundHttpException($workerNotFoundException->getMessage(), $workerNotFoundException);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/workers', name: 'workers_revoke_all', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/workers',
        summary: 'Revoke all workers owned by the current user',
        tags: ['workers'],
        responses: [
            new OA\Response(response: 200, description: 'Returns the count of revoked workers'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ],
    )]
    public function revokeAll(#[CurrentUser] SecurityUser $user): JsonResponse
    {
        $count = ($this->revokeAllOwnerWorkers)(new RevokeAllOwnerWorkersCommand(
            ownerUserId: $user->domainUser()->id(),
        ));

        return new JsonResponse(['revoked' => $count]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serialiseWorker(WorkerView $view): array
    {
        return [
            'id' => (string) $view->id,
            'name' => $view->name,
            'hostLabel' => $view->hostLabel,
            'rabbitmqUser' => $view->rabbitmqUser,
            'lastHeartbeatAt' => $view->lastHeartbeatAt?->format(DateTimeInterface::ATOM),
            'isOnline' => $view->isOnline,
            'createdAt' => $view->createdAt->format(DateTimeInterface::ATOM),
            'updatedAt' => $view->updatedAt->format(DateTimeInterface::ATOM),
        ];
    }
}
