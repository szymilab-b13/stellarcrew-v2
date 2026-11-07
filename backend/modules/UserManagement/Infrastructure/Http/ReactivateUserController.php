<?php

declare(strict_types=1);

namespace Modules\UserManagement\Infrastructure\Http;

use Modules\Auth\Infrastructure\Security\SecurityUser;
use Modules\Shared\Kernel\UserId;
use Modules\UserManagement\Api\Exception\UserProfileNotFoundException;
use Modules\UserManagement\Api\UserManagementApi;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final readonly class ReactivateUserController
{
    private const string ID_REQUIREMENT = '[0-9a-fA-F-]{36}';

    public function __construct(
        private UserManagementApi $users,
    ) {
    }

    #[Route(
        '/api/user-management/users/{id}/reactivate',
        name: 'user_management_reactivate',
        requirements: ['id' => self::ID_REQUIREMENT],
        methods: ['POST'],
    )]
    #[IsGranted('ROLE_ADMIN', message: 'Only super admins can reactivate users.')]
    #[OA\Post(
        path: '/api/user-management/users/{id}/reactivate',
        summary: 'Reactivate a user account (super admin only). Idempotent.',
        tags: ['user-management'],
        responses: [
            new OA\Response(response: 204, description: 'User reactivated or already active'),
            new OA\Response(response: 401, description: 'Missing or expired token'),
            new OA\Response(response: 403, description: 'Caller is not a super admin'),
            new OA\Response(response: 404, description: 'User not found'),
        ],
    )]
    public function reactivate(
        string $id,
        #[CurrentUser]
        SecurityUser $user,
    ): JsonResponse {
        try {
            $this->users->reactivateUser($user->domainUser()->id(), UserId::fromString($id));
        } catch (UserProfileNotFoundException) {
            return new JsonResponse(['error' => 'not_found'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(status: Response::HTTP_NO_CONTENT);
    }
}
