<?php

declare(strict_types=1);

namespace Modules\UserManagement\Infrastructure\Http;

use Modules\Shared\Kernel\UserId;
use Modules\UserManagement\Api\Exception\PasswordTooShortException;
use Modules\UserManagement\Api\Exception\UserProfileNotFoundException;
use Modules\UserManagement\Api\UserManagementApi;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final readonly class ResetPasswordController
{
    private const string ID_REQUIREMENT = '[0-9a-fA-F-]{36}';

    public function __construct(
        private UserManagementApi $users,
    ) {
    }

    #[Route(
        '/api/user-management/users/{id}/reset-password',
        name: 'user_management_reset_password',
        requirements: ['id' => self::ID_REQUIREMENT],
        methods: ['POST'],
    )]
    #[IsGranted('ROLE_ADMIN', message: 'Only super admins can reset passwords.')]
    #[OA\Post(
        path: '/api/user-management/users/{id}/reset-password',
        summary: 'Reset a user password (super admin only). Non-reversible.',
        tags: ['user-management'],
        responses: [
            new OA\Response(response: 204, description: 'Password reset'),
            new OA\Response(response: 401, description: 'Missing or expired token'),
            new OA\Response(response: 403, description: 'Caller is not a super admin'),
            new OA\Response(response: 404, description: 'User not found'),
            new OA\Response(response: 422, description: 'Password too short'),
        ],
    )]
    public function __invoke(
        string $id,
        #[MapRequestPayload]
        ResetPasswordRequest $input,
    ): JsonResponse {
        try {
            $this->users->resetUserPassword(UserId::fromString($id), $input->newPassword);
        } catch (UserProfileNotFoundException) {
            return new JsonResponse(['error' => 'not_found'], Response::HTTP_NOT_FOUND);
        } catch (PasswordTooShortException) {
            return new JsonResponse(['error' => 'password_too_short'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse(status: Response::HTTP_NO_CONTENT);
    }
}
