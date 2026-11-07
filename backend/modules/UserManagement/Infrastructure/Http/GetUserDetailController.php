<?php

declare(strict_types=1);

namespace Modules\UserManagement\Infrastructure\Http;

use function array_map;

use Modules\Shared\Kernel\UserId;
use Modules\UserManagement\Api\Dto\UserDetailDto;
use Modules\UserManagement\Api\Dto\UserWorkspaceAssignmentDto;
use Modules\UserManagement\Api\UserManagementApi;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final readonly class GetUserDetailController
{
    private const string ID_REQUIREMENT = '[0-9a-fA-F-]{36}';

    public function __construct(
        private UserManagementApi $users,
    ) {
    }

    #[Route(
        '/api/user-management/users/{id}',
        name: 'user_management_detail',
        requirements: ['id' => self::ID_REQUIREMENT],
        methods: ['GET'],
    )]
    #[IsGranted('ROLE_ADMIN', message: 'Only super admins can view user detail.')]
    #[OA\Get(
        path: '/api/user-management/users/{id}',
        summary: 'Get user detail including profile + workspace assignments (super admin only).',
        tags: ['user-management'],
        responses: [
            new OA\Response(response: 200, description: 'User detail'),
            new OA\Response(response: 401, description: 'Missing or expired token'),
            new OA\Response(response: 403, description: 'Caller is not a super admin'),
            new OA\Response(response: 404, description: 'User not found'),
        ],
    )]
    public function get(string $id): JsonResponse
    {
        $detail = $this->users->getUserDetail(UserId::fromString($id));
        if (!$detail instanceof UserDetailDto) {
            return new JsonResponse(['error' => 'not_found'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($this->serialise($detail), Response::HTTP_OK);
    }

    /**
     * @return array<string, mixed>
     */
    private function serialise(UserDetailDto $dto): array
    {
        return [
            'id' => $dto->id,
            'email' => $dto->email,
            'is_super_admin' => $dto->isSuperAdmin,
            'display_name' => $dto->displayName,
            'avatar_color' => $dto->avatarColor,
            'created_at' => $dto->createdAt->format(DATE_ATOM),
            'deactivated_at' => $dto->deactivatedAt?->format(DATE_ATOM),
            'workspace_assignments' => array_map($this->serialiseAssignment(...), $dto->workspaceAssignments),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serialiseAssignment(UserWorkspaceAssignmentDto $assignment): array
    {
        return [
            'workspace_id' => $assignment->workspaceId,
            'workspace_name' => $assignment->workspaceName,
            'workspace_slug' => $assignment->workspaceSlug,
            'icon_color' => $assignment->iconColor,
            'icon_initials' => $assignment->iconInitials,
            'role' => $assignment->role,
            'joined_at' => $assignment->joinedAt->format(DATE_ATOM),
        ];
    }
}
