<?php

declare(strict_types=1);

namespace Modules\UserManagement\Infrastructure\Http;

use function array_map;
use function is_string;

use Modules\Auth\Infrastructure\Security\SecurityUser;
use Modules\UserManagement\Api\Dto\UserListItemDto;
use Modules\UserManagement\Api\UserManagementApi;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final readonly class ListVisibleUsersController
{
    public function __construct(
        private UserManagementApi $users,
    ) {
    }

    #[Route('/api/user-management/users/visible', name: 'user_management_list_visible', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[OA\Get(
        path: '/api/user-management/users/visible',
        summary: 'List users visible to the current viewer (members of shared workspaces).',
        tags: ['user-management'],
        parameters: [
            new OA\Parameter(name: 'q', description: 'Email ILIKE filter', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Visible users'),
            new OA\Response(response: 401, description: 'Missing or expired token'),
        ],
    )]
    public function list(Request $request, #[CurrentUser] SecurityUser $viewer): JsonResponse
    {
        $q = $request->query->get('q');
        $q = is_string($q) && $q !== '' ? $q : null;

        $items = $this->users->listUsersVisibleTo($viewer->domainUser()->id(), $q);

        return new JsonResponse([
            'items' => array_map($this->serialise(...), $items),
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
}
