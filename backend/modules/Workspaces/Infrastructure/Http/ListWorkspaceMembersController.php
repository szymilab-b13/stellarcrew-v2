<?php

declare(strict_types=1);

namespace Modules\Workspaces\Infrastructure\Http;

use function array_map;
use function array_values;

use Modules\Auth\Domain\UserRole;
use Modules\Auth\Infrastructure\Security\SecurityUser;
use Modules\Shared\Kernel\WorkspaceId;
use Modules\Workspaces\Api\Dto\MembershipView;
use Modules\Workspaces\Api\Dto\WorkspaceView;
use Modules\Workspaces\Api\WorkspacesApi;
use Modules\Workspaces\Application\ReadModel\WorkspaceMemberListItem;
use Modules\Workspaces\Application\ReadModel\WorkspaceMembersReadModel;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

final readonly class ListWorkspaceMembersController
{
    private const string UUID_REQUIREMENT = '[0-9a-fA-F-]{36}';

    public function __construct(
        private WorkspacesApi $workspaces,
        private WorkspaceMembersReadModel $members,
    ) {
    }

    #[Route(
        '/api/workspaces/{id}/members',
        name: 'workspaces_list_members',
        requirements: ['id' => self::UUID_REQUIREMENT],
        methods: ['GET'],
    )]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[OA\Get(
        path: '/api/workspaces/{id}/members',
        summary: 'List members of a workspace with role and joined-at.',
        tags: ['workspaces'],
        responses: [
            new OA\Response(response: 200, description: 'Members list'),
            new OA\Response(response: 401, description: 'Missing or expired token'),
            new OA\Response(response: 403, description: 'Caller is not member or super admin'),
            new OA\Response(response: 404, description: 'Workspace not found'),
        ],
    )]
    public function __invoke(string $id, #[CurrentUser] SecurityUser $user): JsonResponse
    {
        $workspaceId = new WorkspaceId(Uuid::fromString($id));
        $workspace = $this->workspaces->findById($workspaceId);
        if (!$workspace instanceof WorkspaceView) {
            throw new NotFoundHttpException('Workspace not found.');
        }

        $callerId = $user->domainUser()->id();
        $isSuperAdmin = $user->domainUser()->role() === UserRole::Admin;
        $membership = $this->workspaces->getMembership($workspaceId, $callerId);
        if (!$isSuperAdmin && !$membership instanceof MembershipView) {
            throw new AccessDeniedException('Only members or super admins can view workspace members.');
        }

        $items = array_values(array_map(
            $this->serialise(...),
            $this->members->listForWorkspace($workspaceId),
        ));

        return new JsonResponse(['items' => $items], Response::HTTP_OK);
    }

    /**
     * @return array<string, mixed>
     */
    private function serialise(WorkspaceMemberListItem $item): array
    {
        return [
            'userId' => $item->userId,
            'email' => $item->email,
            'role' => $item->role,
            'joinedAt' => $item->joinedAt->format(DATE_ATOM),
        ];
    }
}
