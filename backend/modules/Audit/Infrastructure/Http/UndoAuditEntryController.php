<?php

declare(strict_types=1);

namespace Modules\Audit\Infrastructure\Http;

use Modules\Audit\Api\AuditApi;
use Modules\Auth\Infrastructure\Security\SecurityUser;
use Modules\Shared\Kernel\AuditEntryId;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final readonly class UndoAuditEntryController
{
    private const string ID_REQUIREMENT = '[0-9a-fA-F-]{36}';

    public function __construct(
        private AuditApi $audit,
    ) {
    }

    #[Route(
        '/api/audit/entries/{id}/undo',
        name: 'audit_undo',
        requirements: ['id' => self::ID_REQUIREMENT],
        methods: ['POST'],
    )]
    #[IsGranted('ROLE_ADMIN', message: 'Only super admins can undo audit entries.')]
    #[OA\Post(
        path: '/api/audit/entries/{id}/undo',
        summary: 'Undo an audit entry (super admin only).',
        tags: ['audit'],
        responses: [
            new OA\Response(response: 200, description: 'Undo applied, returns new entry id'),
            new OA\Response(response: 401, description: 'Missing or expired token'),
            new OA\Response(response: 403, description: 'Caller is not a super admin'),
            new OA\Response(response: 404, description: 'Entry not found'),
            new OA\Response(response: 409, description: 'Undo conflict with current state'),
            new OA\Response(response: 410, description: 'Entry already undone'),
            new OA\Response(response: 422, description: 'Action is not reversible'),
        ],
    )]
    public function __invoke(string $id, #[CurrentUser] SecurityUser $user): JsonResponse
    {
        $newId = $this->audit->undo(
            AuditEntryId::fromString($id),
            $user->domainUser()->id(),
        );

        return new JsonResponse(['new_entry_id' => (string) $newId], Response::HTTP_OK);
    }
}
