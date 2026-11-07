<?php

declare(strict_types=1);

namespace Modules\UserManagement\Infrastructure\Http;

use function array_map;
use function filter_var;
use function is_string;

use Modules\Shared\Kernel\WorkspaceId;
use Modules\UserManagement\Api\Dto\UserListItemDto;
use Modules\UserManagement\Api\UserManagementApi;
use Modules\UserManagement\Application\Query\UserListFilters;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

final readonly class ListUsersController
{
    public function __construct(
        private UserManagementApi $users,
    ) {
    }

    #[Route('/api/user-management/users', name: 'user_management_list', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN', message: 'Only super admins can list users.')]
    #[OA\Get(
        path: '/api/user-management/users',
        summary: 'List all users (super admin only) with filters + pagination.',
        tags: ['user-management'],
        parameters: [
            new OA\Parameter(name: 'q', description: 'Email ILIKE filter', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'workspace_id', description: 'Only members of this workspace', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'has_super_admin', description: 'true/false role filter', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'only_deactivated', description: 'true/false deactivation filter', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'page', description: 'Page number, default 1', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', description: 'Page size, default 20, max 100', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated users'),
            new OA\Response(response: 401, description: 'Missing or expired token'),
            new OA\Response(response: 403, description: 'Caller is not a super admin'),
        ],
    )]
    public function list(Request $request): JsonResponse
    {
        $filters = new UserListFilters(
            q: $this->nullableString($request->query->get('q')),
            workspaceId: $this->workspaceId($request->query->get('workspace_id')),
            hasSuperAdmin: $this->nullableBool($request->query->get('has_super_admin')),
            onlyDeactivated: $this->nullableBool($request->query->get('only_deactivated')),
            page: $request->query->getInt('page', 1),
            perPage: $request->query->getInt('per_page', 20),
        );

        $page = $this->users->listAllUsers($filters);

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
    private function serialise(UserListItemDto $dto): array
    {
        return [
            'id' => $dto->id,
            'email' => $dto->email,
            'is_super_admin' => $dto->isSuperAdmin,
            'workspace_count' => $dto->workspaceCount,
            'created_at' => $dto->createdAt->format(DATE_ATOM),
            'deactivated_at' => $dto->deactivatedAt?->format(DATE_ATOM),
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

    private function workspaceId(mixed $value): ?WorkspaceId
    {
        if (!is_string($value) || $value === '' || !Uuid::isValid($value)) {
            return null;
        }

        return new WorkspaceId(Uuid::fromString($value));
    }
}
