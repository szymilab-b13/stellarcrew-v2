<?php

declare(strict_types=1);

namespace Modules\Audit\Infrastructure\Http;

use Modules\Audit\Api\AuditApi;
use Modules\Shared\Kernel\AuditEntryId;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final readonly class DescribeUndoAuditEntryController
{
    private const string ID_REQUIREMENT = '[0-9a-fA-F-]{36}';

    public function __construct(
        private AuditApi $audit,
    ) {
    }

    #[Route(
        '/api/audit/entries/{id}/describe-undo',
        name: 'audit_describe_undo',
        requirements: ['id' => self::ID_REQUIREMENT],
        methods: ['GET'],
    )]
    #[IsGranted('ROLE_ADMIN', message: 'Only super admins can describe audit undo.')]
    #[OA\Get(
        path: '/api/audit/entries/{id}/describe-undo',
        summary: 'Describe the effect of undoing an audit entry (super admin only).',
        tags: ['audit'],
        responses: [
            new OA\Response(response: 200, description: 'Human-readable description of the undo operation'),
            new OA\Response(response: 401, description: 'Missing or expired token'),
            new OA\Response(response: 403, description: 'Caller is not a super admin'),
            new OA\Response(response: 404, description: 'Entry not found'),
        ],
    )]
    public function __invoke(string $id): JsonResponse
    {
        $description = $this->audit->describeUndo(AuditEntryId::fromString($id));

        return new JsonResponse(['description' => $description], Response::HTTP_OK);
    }
}
