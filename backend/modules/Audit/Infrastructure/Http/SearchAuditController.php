<?php

declare(strict_types=1);

namespace Modules\Audit\Infrastructure\Http;

use function array_map;

use DateTimeImmutable;
use Exception;

use function filter_var;
use function is_string;

use Modules\Audit\Api\AuditApi;
use Modules\Audit\Api\Dto\AuditEntryDto;
use Modules\Audit\Api\Dto\AuditQuery;
use Modules\Shared\Kernel\AuditEntryId;
use Modules\Shared\Kernel\UserId;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

final readonly class SearchAuditController
{
    public function __construct(
        private AuditApi $audit,
    ) {
    }

    #[Route('/api/audit/entries', name: 'audit_search', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN', message: 'Only super admins can read audit log.')]
    #[OA\Get(
        path: '/api/audit/entries',
        summary: 'Search audit log entries (super admin only). Supports filters + pagination.',
        tags: ['audit'],
        parameters: [
            new OA\Parameter(name: 'actor_user_id', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'action', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'resource_type', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'resource_id', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'from', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date-time')),
            new OA\Parameter(name: 'to', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date-time')),
            new OA\Parameter(name: 'include_undone', in: 'query', required: false, schema: new OA\Schema(type: 'boolean', default: true)),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 50)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated audit entries'),
            new OA\Response(response: 401, description: 'Missing or expired token'),
            new OA\Response(response: 403, description: 'Caller is not a super admin'),
        ],
    )]
    public function search(Request $request): JsonResponse
    {
        $includeUndone = $this->nullableBool($request->query->get('include_undone'));
        $query = new AuditQuery(
            actorUserId: $this->userId($request->query->get('actor_user_id')),
            action: $this->nullableString($request->query->get('action')),
            resourceType: $this->nullableString($request->query->get('resource_type')),
            resourceId: $this->nullableString($request->query->get('resource_id')),
            from: $this->dateTime($request->query->get('from')),
            to: $this->dateTime($request->query->get('to')),
            includeUndone: $includeUndone ?? true,
            page: $request->query->getInt('page', 1),
            perPage: $request->query->getInt('per_page', 50),
        );

        $page = $this->audit->search($query);

        return new JsonResponse([
            'items' => array_map($this->serialise(...), $page->items),
            'total' => $page->total,
            'page' => $page->page,
            'per_page' => $page->perPage,
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

    private function nullableString(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        return $value === '' ? null : $value;
    }

    private function nullableBool(mixed $value): ?bool
    {
        if ($value === null) {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }

    private function userId(mixed $value): ?UserId
    {
        if (!is_string($value) || $value === '' || !Uuid::isValid($value)) {
            return null;
        }

        return UserId::fromString($value);
    }

    private function dateTime(mixed $value): ?DateTimeImmutable
    {
        if (!is_string($value) || $value === '') {
            return null;
        }

        try {
            return new DateTimeImmutable($value);
        } catch (Exception) {
            return null;
        }
    }
}
