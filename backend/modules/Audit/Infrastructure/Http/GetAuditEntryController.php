<?php

declare(strict_types=1);

namespace Modules\Audit\Infrastructure\Http;

use Modules\Audit\Api\AuditApi;
use Modules\Audit\Api\Dto\AuditEntryDto;
use Modules\Shared\Kernel\AuditEntryId;
use Modules\Shared\Kernel\UserId;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final readonly class GetAuditEntryController
{
    private const string ID_REQUIREMENT = '[0-9a-fA-F-]{36}';

    public function __construct(
        private AuditApi $audit,
    ) {
    }

    #[Route(
        '/api/audit/entries/{id}',
        name: 'audit_get',
        requirements: ['id' => self::ID_REQUIREMENT],
        methods: ['GET'],
    )]
    #[IsGranted('ROLE_ADMIN', message: 'Only super admins can read audit log.')]
    #[OA\Get(
        path: '/api/audit/entries/{id}',
        summary: 'Get a single audit log entry (super admin only).',
        tags: ['audit'],
        responses: [
            new OA\Response(response: 200, description: 'Audit entry'),
            new OA\Response(response: 401, description: 'Missing or expired token'),
            new OA\Response(response: 403, description: 'Caller is not a super admin'),
            new OA\Response(response: 404, description: 'Entry not found'),
        ],
    )]
    public function get(string $id): JsonResponse
    {
        $entry = $this->audit->get(AuditEntryId::fromString($id));
        if (!$entry instanceof AuditEntryDto) {
            return new JsonResponse(['error' => 'not_found'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($this->serialise($entry), Response::HTTP_OK);
    }

    /**
     * @return array<string, mixed>
     */
    private function serialise(AuditEntryDto $dto): array
    {
        return [
            'id' => (string) $dto->id,
            'actor_user_id' => (string) $dto->actorUserId,
            'actor_email' => $dto->actorEmail,
            'action' => $dto->action,
            'resource_type' => $dto->resourceType,
            'resource_id' => $dto->resourceId,
            'before_state' => $dto->beforeState,
            'after_state' => $dto->afterState,
            'metadata' => $dto->metadata,
            'occurred_at' => $dto->occurredAt->format(DATE_ATOM),
            'undone_at' => $dto->undoneAt?->format(DATE_ATOM),
            'undone_by' => $dto->undoneBy instanceof UserId ? (string) $dto->undoneBy : null,
            'undone_by_email' => $dto->undoneByEmail,
            'reverts_entry_id' => $dto->revertsEntryId instanceof AuditEntryId ? (string) $dto->revertsEntryId : null,
        ];
    }
}
