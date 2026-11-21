<?php

declare(strict_types=1);

namespace Modules\Workers\Infrastructure\Http;

use function array_map;

use DateTimeInterface;
use Modules\Auth\Infrastructure\Security\SecurityUser;
use Modules\Shared\Kernel\WorkerId;
use Modules\Workers\Application\AcceptFingerprintCommand;
use Modules\Workers\Application\AcceptFingerprintCommandHandler;
use Modules\Workers\Application\CancelPairingCodeCommand;
use Modules\Workers\Application\CancelPairingCodeCommandHandler;
use Modules\Workers\Application\ClaimPairingCodeCommand;
use Modules\Workers\Application\ClaimPairingCodeCommandHandler;
use Modules\Workers\Application\GeneratePairingCodeCommand;
use Modules\Workers\Application\GeneratePairingCodeCommandHandler;
use Modules\Workers\Application\GetWorkerDetailQuery;
use Modules\Workers\Application\GetWorkerDetailQueryHandler;
use Modules\Workers\Application\ListUserWorkersQuery;
use Modules\Workers\Application\ListUserWorkersQueryHandler;
use Modules\Workers\Application\RevokeAllOwnerWorkersCommand;
use Modules\Workers\Application\RevokeAllOwnerWorkersCommandHandler;
use Modules\Workers\Application\RevokeWorkerCommand;
use Modules\Workers\Application\RevokeWorkerCommandHandler;
use Modules\Workers\Application\WorkerDetailView;
use Modules\Workers\Application\WorkerView;
use Modules\Workers\Domain\WorkerNotFoundException;
use Modules\Workers\Infrastructure\Support\PairCommandFormatter;
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
        private CancelPairingCodeCommandHandler $cancelPairingCode,
        private ListUserWorkersQueryHandler $listUserWorkers,
        private GetWorkerDetailQueryHandler $getWorkerDetail,
        private RevokeWorkerCommandHandler $revokeWorker,
        private RevokeAllOwnerWorkersCommandHandler $revokeAllOwnerWorkers,
        private AcceptFingerprintCommandHandler $acceptFingerprint,
        private PairCommandFormatter $pairCommandFormatter,
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
            'pairCommand' => $this->pairCommandFormatter->format($code->code()),
        ], Response::HTTP_CREATED);
    }

    #[Route(
        '/api/workers/pairing-codes/{code}',
        name: 'workers_pairing_code_cancel',
        requirements: ['code' => '[A-HJ-NP-Z2-9]{4}-[A-HJ-NP-Z2-9]{4}'],
        methods: ['DELETE'],
    )]
    #[OA\Delete(
        path: '/api/workers/pairing-codes/{code}',
        summary: 'Cancel an unclaimed pairing code',
        tags: ['workers'],
        responses: [
            new OA\Response(response: 204, description: 'Pairing code cancelled'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Not the code owner'),
            new OA\Response(response: 404, description: 'Pairing code not found'),
            new OA\Response(response: 409, description: 'Pairing code already claimed'),
        ],
    )]
    public function cancelPairingCode(string $code, #[CurrentUser] SecurityUser $user): JsonResponse
    {
        ($this->cancelPairingCode)(new CancelPairingCodeCommand(
            code: $code,
            actorUserId: $user->domainUser()->id(),
        ));

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
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
            hostInfo: $request->hostInfo,
        ));

        return new JsonResponse([
            'workerId' => (string) $result->workerId,
            'secretToken' => $result->plaintextSecret,
            'rabbitmqUser' => $result->rabbitmqUser,
            'refreshToken' => $result->refreshToken,
        ], Response::HTTP_CREATED);
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
        name: 'workers_detail',
        requirements: ['id' => '[0-9a-fA-F-]{36}'],
        methods: ['GET'],
    )]
    #[OA\Get(
        path: '/api/workers/{id}',
        summary: 'Get detailed info about a worker (fingerprint hash + meta + change history)',
        tags: ['workers'],
        responses: [
            new OA\Response(response: 200, description: 'Worker detail view'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Not the worker owner'),
            new OA\Response(response: 404, description: 'Worker not found'),
        ],
    )]
    public function detail(string $id, #[CurrentUser] SecurityUser $user): JsonResponse
    {
        $view = ($this->getWorkerDetail)(new GetWorkerDetailQuery(
            workerId: WorkerId::fromString($id),
            actorUserId: $user->domainUser()->id(),
        ));

        return new JsonResponse($this->serialiseWorkerDetail($view));
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

    #[Route(
        '/api/workers/{id}/fingerprint/accept',
        name: 'workers_fingerprint_accept',
        requirements: ['id' => '[0-9a-fA-F-]{36}'],
        methods: ['POST'],
    )]
    #[OA\Post(
        path: '/api/workers/{id}/fingerprint/accept',
        summary: 'Accept a new fingerprint for a worker after a mismatch warning',
        tags: ['workers'],
        responses: [
            new OA\Response(response: 204, description: 'Fingerprint accepted and persisted as the stored baseline'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Not the worker owner'),
            new OA\Response(response: 404, description: 'Worker not found'),
        ],
    )]
    public function acceptFingerprint(
        string $id,
        #[MapRequestPayload]
        AcceptFingerprintRequest $request,
        #[CurrentUser]
        SecurityUser $user,
    ): JsonResponse {
        ($this->acceptFingerprint)(new AcceptFingerprintCommand(
            workerId: WorkerId::fromString($id),
            actorId: $user->domainUser()->id(),
            fingerprintHash: $request->fingerprintHash,
            fingerprintMeta: $request->fingerprintMeta,
        ));

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @return array<string, mixed>
     */
    private function serialiseWorkerDetail(WorkerDetailView $view): array
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
            'fingerprintHash' => $view->fingerprintHash,
            'fingerprintHashShort' => $view->fingerprintHashShort,
            'fingerprintStatus' => $view->fingerprintStatus,
            'fingerprintMeta' => $view->fingerprintMeta,
            'fingerprintChangeHistory' => $view->fingerprintChangeHistory,
        ];
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
            'fingerprintHashShort' => $view->fingerprintHashShort,
            'fingerprintStatus' => $view->fingerprintStatus,
        ];
    }
}
