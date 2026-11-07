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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Uid\Uuid;

final readonly class RemoveWorkspaceMemberController
{
    private const string UUID_REQUIREMENT = '[0-9a-fA-F-]{36}';

    public function __construct(
        private WorkspacesApi $workspaces,
    ) {
    }

    #[Route(
        '/api/user-management/users/{id}/workspace-assignments/{wsId}',
        name: 'user_management_workspace_member_remove',
        requirements: ['id' => self::UUID_REQUIREMENT, 'wsId' => self::UUID_REQUIREMENT],
        methods: ['DELETE'],
    )]
    #[OA\Delete(
        path: '/api/user-management/users/{id}/workspace-assignments/{wsId}',
        summary: 'Remove a workspace member (must be downgraded first if admin).',
        tags: ['user-management'],
        responses: [
            new OA\Response(response: 204, description: 'Member removed'),
            new OA\Response(response: 401, description: 'Missing or expired token'),
            new OA\Response(response: 403, description: 'Caller cannot manage members of this workspace'),
            new OA\Response(response: 404, description: 'User is not a member of the workspace'),
            new OA\Response(response: 422, description: 'Admin must be downgraded first'),
        ],
    )]
    public function __invoke(
        string $id,
        string $wsId,
        #[CurrentUser]
        SecurityUser $user,
    ): JsonResponse {
        $workspaceId = new WorkspaceId(Uuid::fromString($wsId));
        $this->ensureCanManageMembers($user, $workspaceId);

        $targetUserId = new UserId(Uuid::fromString($id));
        $this->workspaces->removeMember($user->domainUser()->id(), $workspaceId, $targetUserId);

        return new JsonResponse(status: Response::HTTP_NO_CONTENT);
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
