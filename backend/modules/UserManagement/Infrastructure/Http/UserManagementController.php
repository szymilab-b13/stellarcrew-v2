<?php

declare(strict_types=1);

namespace Modules\UserManagement\Infrastructure\Http;

use Modules\UserManagement\Api\UserManagementApi;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final readonly class UserManagementController
{
    public function __construct(
        private UserManagementApi $users,
    ) {
    }

    #[Route('/api/user-management/users', name: 'user_management_create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Only super admins can create users.')]
    #[OA\Post(
        path: '/api/user-management/users',
        summary: 'Create a new user (super admin only).',
        tags: ['user-management'],
        responses: [
            new OA\Response(response: 201, description: 'User created'),
            new OA\Response(response: 401, description: 'Missing or expired token'),
            new OA\Response(response: 403, description: 'Caller is not a super admin'),
            new OA\Response(response: 422, description: 'Validation error or email already taken'),
        ],
    )]
    public function create(#[MapRequestPayload] CreateUserRequest $input): JsonResponse
    {
        $profile = $this->users->createUser(
            $input->email,
            $input->password,
            $input->isSuperAdmin,
            $input->displayName,
        );

        return new JsonResponse(
            [
                'id' => $profile->id,
                'displayName' => $profile->displayName,
                'avatarColor' => $profile->avatarColor,
                'isDeactivated' => $profile->isDeactivated,
            ],
            Response::HTTP_CREATED,
        );
    }
}
