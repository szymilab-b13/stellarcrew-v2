<?php

declare(strict_types=1);

namespace Modules\UserManagement\Infrastructure\Http;

use Modules\Auth\Domain\UserRole;
use Modules\Auth\Infrastructure\Security\SecurityUser;
use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\WorkspaceId;
use Modules\Workspaces\Api\WorkspacesApi;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Uid\Uuid;

final readonly class ChangeWorkspaceMemberRoleController
{
    private const string UUID_REQUIREMENT = '[0-9a-fA-F-]{36}';

    public function __construct(
        private WorkspacesApi $workspaces,
    ) {
    }

    #[Route(
        '/api/user-management/users/{id}/workspace-assignments/{wsId}',
        name: 'user_management_workspace_member_change_role',
        requirements: ['id' => self::UUID_REQUIREMENT, 'wsId' => self::UUID_REQUIREMENT],
        methods: ['PATCH'],
    )]
    #[OA\Patch(
        path: '/api/user-management/users/{id}/workspace-assignments/{wsId}',
        summary: 'Change a workspace member role.',
        tags: ['user-management'],
        responses: [
            new OA\Response(response: 200, description: 'Role changed'),
            new OA\Response(response: 401, description: 'Missing or expired token'),
            new OA\Response(response: 403, description: 'Caller cannot manage members of this workspace'),
            new OA\Response(response: 404, description: 'User is not a member of the workspace'),
            new OA\Response(response: 422, description: 'Validation error or owner role attempted'),
        ],
    )]
    public function __invoke(
        string $id,
        string $wsId,
        #[CurrentUser]
        SecurityUser $user,
        #[MapRequestPayload]
        ChangeWorkspaceMemberRoleRequest $input,
    ): JsonResponse {
        $workspaceId = new WorkspaceId(Uuid::fromString($wsId));
        $this->ensureCanManageMembers($user, $workspaceId);

        $targetUserId = new UserId(Uuid::fromString($id));
        $view = $this->workspaces->changeMemberRole($workspaceId, $targetUserId, $input->role);

        return new JsonResponse([
            'workspaceId' => $view->workspaceId,
            'userId' => $view->userId,
            'role' => $view->role,
        ]);
    }

    private function ensureCanManageMembers(SecurityUser $user, WorkspaceId $workspaceId): void
    {
        if ($user->domainUser()->role() === UserRole::Admin) {
            return;
        }

        if (!$this->workspaces->hasPermission($workspaceId, $user->domainUser()->id(), 'members.manage')) {
            throw new AccessDeniedException('Only super admins or workspace owners/admins can manage members.');
        }
    }
}
