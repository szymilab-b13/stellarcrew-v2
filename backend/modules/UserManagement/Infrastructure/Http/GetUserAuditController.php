<?php

declare(strict_types=1);

namespace Modules\UserManagement\Infrastructure\Http;

use function array_map;

use Modules\Audit\Api\AuditApi;
use Modules\Audit\Api\Dto\AuditEntryDto;
use Modules\Shared\Kernel\AuditEntryId;
use Modules\Shared\Kernel\UserId;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final readonly class GetUserAuditController
{
    private const string ID_REQUIREMENT = '[0-9a-fA-F-]{36}';

    private const int DEFAULT_LIMIT = 20;

    public function __construct(
        private AuditApi $audit,
    ) {
    }

    #[Route(
        '/api/user-management/users/{id}/audit',
        name: 'user_management_audit',
        requirements: ['id' => self::ID_REQUIREMENT],
        methods: ['GET'],
    )]
    #[IsGranted('ROLE_ADMIN', message: 'Only super admins can view user audit history.')]
    #[OA\Get(
        path: '/api/user-management/users/{id}/audit',
        summary: 'List recent audit entries related to the user (actor or target). Super admin only.',
        tags: ['user-management'],
        responses: [
            new OA\Response(response: 200, description: 'Recent audit entries'),
            new OA\Response(response: 401, description: 'Missing or expired token'),
            new OA\Response(response: 403, description: 'Caller is not a super admin'),
        ],
    )]
    public function get(string $id): JsonResponse
    {
        $entries = $this->audit->listRecentForUser(UserId::fromString($id), self::DEFAULT_LIMIT);

        return new JsonResponse([
            'items' => array_map($this->serialise(...), $entries),
        ], Response::HTTP_OK);
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
